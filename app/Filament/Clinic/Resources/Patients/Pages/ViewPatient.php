<?php

namespace App\Filament\Clinic\Resources\Patients\Pages;

use App\Filament\Clinic\Resources\Patients\PatientResource;
use App\Filament\Saas\Resources\Pages\Concerns\HasCleanViewPageLabels;
use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewPatient extends ViewRecord
{
    use HasCleanViewPageLabels;

    protected static string $resource = PatientResource::class;

    protected string $view = 'filament.clinic.resources.patients.pages.view-patient-profile';

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => auth()->user()?->canEditClinicPatients() ?? false),
        ];
    }

    public function getProfileStats(): array
    {
        /** @var Patient $record */
        $record = $this->getRecord();

        $completedAppointments = Appointment::query()
            ->where('patient_id', $record->getKey())
            ->where('status', 'completed')
            ->count();

        $encounters = Encounter::query()
            ->where('patient_id', $record->getKey())
            ->count();

        $billing = max(
            (float) ($record->ledger_debit_total ?? 0) - (float) ($record->ledger_credit_total ?? 0),
            0
        );

        return [
            [
                'label' => 'Total Appointments',
                'value' => (int) ($record->appointments_count ?? 0),
                'icon' => 'calendar',
            ],
            [
                'label' => 'Completed Appointments',
                'value' => $completedAppointments,
                'icon' => 'check-calendar',
            ],
            [
                'label' => 'Total Encounters',
                'value' => $encounters,
                'icon' => 'stethoscope',
            ],
            [
                'label' => 'Total Billing Amount',
                'value' => '$' . number_format($billing, 2),
                'icon' => 'currency',
            ],
        ];
    }

    public function getRecentAppointments(): array
    {
        /** @var Patient $record */
        $record = $this->getRecord();

        return Appointment::query()
            ->with(['provider.user', 'location'])
            ->where('patient_id', $record->getKey())
            ->latest('appointment_date')
            ->limit(5)
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'date' => $appointment->appointment_date?->format('M d, Y') ?: '-',
                'time' => collect([
                    $appointment->start_time ? date('g:i A', strtotime((string) $appointment->start_time)) : null,
                    $appointment->end_time ? date('g:i A', strtotime((string) $appointment->end_time)) : null,
                ])->filter()->implode(' - '),
                'provider' => $appointment->provider?->display_name ?: '-',
                'location' => $appointment->location?->location_name ?: '-',
                'status' => str($appointment->status)->replace('_', ' ')->title()->toString(),
            ])
            ->all();
    }

    public function getRecentTreatmentPlans(): array
    {
        /** @var Patient $record */
        $record = $this->getRecord();

        return TreatmentPlan::query()
            ->with(['provider.user'])
            ->where('patient_id', $record->getKey())
            ->latest('plan_date')
            ->limit(5)
            ->get()
            ->map(fn (TreatmentPlan $plan): array => [
                'title' => $plan->title ?: ($plan->plan_number ?: 'Treatment Plan'),
                'date' => $plan->plan_date?->format('M d, Y') ?: '-',
                'status' => filled($plan->status) ? str($plan->status)->replace('_', ' ')->title()->toString() : '-',
                'priority' => filled($plan->priority) ? str($plan->priority)->replace('_', ' ')->title()->toString() : '-',
                'provider' => $plan->provider?->display_name ?: '-',
                'estimate' => '$' . number_format((float) ($plan->estimated_total ?? 0), 2),
            ])
            ->all();
    }

    public function getOpenBalanceLabel(): string
    {
        /** @var Patient $record */
        $record = $this->getRecord();

        $balance = max(
            (float) ($record->ledger_debit_total ?? 0) - (float) ($record->ledger_credit_total ?? 0),
            0
        );

        return '$' . number_format($balance, 2);
    }
}
