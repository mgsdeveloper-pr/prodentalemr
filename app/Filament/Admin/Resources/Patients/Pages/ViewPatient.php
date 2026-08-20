<?php

namespace App\Filament\Admin\Resources\Patients\Pages;

use App\Filament\Admin\Resources\Patients\PatientResource;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Models\Patient;
use App\Models\VerificationFormSubmission;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    protected string $view = 'filament.admin.resources.patients.pages.view-patient-manager';

    protected Width | string | null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => PatientResource::canEdit($this->getRecord())),
        ];
    }

    public function getPatientStats(): array
    {
        /** @var Patient $patient */
        $patient = $this->getRecord();

        return [
            [
                'label' => 'Verification Requests',
                'value' => (int) ($patient->billing_work_items_count ?? 0),
            ],
            [
                'label' => 'Open Requests',
                'value' => (int) ($patient->open_verifications_count ?? 0),
            ],
            [
                'label' => 'Completed',
                'value' => (int) ($patient->completed_verifications_count ?? 0),
            ],
            [
                'label' => 'Form Logs',
                'value' => $this->formLogCount(),
            ],
        ];
    }

    public function getVerificationRequests(): array
    {
        /** @var Patient $patient */
        $patient = $this->getRecord();

        return BillingWorkItem::query()
            ->with(['clinic', 'assignedTo', 'verificationTemplateVersion'])
            ->withCount(['activities', 'formSubmissions'])
            ->where('patient_id', $patient->getKey())
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (BillingWorkItem $request): array => [
                'reference' => $request->reference_number ?: 'Request #' . $request->getKey(),
                'title' => $request->title ?: 'Verification Request',
                'clinic' => $request->clinic?->clinic_name ?: '-',
                'status' => BillingWorkItem::STATUS_OPTIONS[$request->status] ?? str((string) $request->status)->headline()->toString(),
                'outcome' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$request->outcome_status] ?? str((string) $request->outcome_status)->headline()->toString(),
                'priority' => BillingWorkItem::PRIORITY_OPTIONS[$request->priority] ?? str((string) $request->priority)->headline()->toString(),
                'assigned_to' => $request->assignedTo?->name ?: 'Unassigned',
                'template' => $request->verificationTemplateVersion
                    ? 'v' . $request->verificationTemplateVersion->version_number
                    : '-',
                'activities' => (int) $request->activities_count,
                'submissions' => (int) $request->form_submissions_count,
                'created_at' => optional($request->created_at)->format('M d, Y h:i A') ?: '-',
                'url' => VerificationRequestResource::getUrl('view', ['record' => $request]),
            ])
            ->all();
    }

    public function getFormLogs(): array
    {
        /** @var Patient $patient */
        $patient = $this->getRecord();

        return VerificationFormSubmission::query()
            ->with(['workItem', 'user'])
            ->whereHas('workItem', fn ($query) => $query->where('patient_id', $patient->getKey()))
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (VerificationFormSubmission $submission): array => [
                'request' => $submission->workItem?->reference_number ?: '-',
                'version' => 'v' . $submission->version,
                'panel' => filled($submission->panel) ? str($submission->panel)->headline()->toString() : '-',
                'status' => BillingWorkItem::STATUS_OPTIONS[$submission->status] ?? str((string) $submission->status)->headline()->toString(),
                'submitted_by' => $submission->user?->name ?: 'System',
                'submitted_at' => optional($submission->created_at)->format('M d, Y h:i A') ?: '-',
            ])
            ->all();
    }

    public function getActivityTimeline(): array
    {
        /** @var Patient $patient */
        $patient = $this->getRecord();

        return BillingWorkItemActivity::query()
            ->with(['workItem', 'user'])
            ->whereHas('workItem', fn ($query) => $query->where('patient_id', $patient->getKey()))
            ->latest('created_at')
            ->limit(12)
            ->get()
            ->map(fn (BillingWorkItemActivity $activity): array => [
                'request' => $activity->workItem?->reference_number ?: '-',
                'type' => str($activity->activity_type)->replace('_', ' ')->title()->toString(),
                'description' => $activity->description,
                'author' => $activity->user?->name ?: 'System',
                'created_at' => optional($activity->created_at)->format('M d, Y h:i A') ?: '-',
            ])
            ->all();
    }

    protected function formLogCount(): int
    {
        /** @var Patient $patient */
        $patient = $this->getRecord();

        return VerificationFormSubmission::query()
            ->whereHas('workItem', fn ($query) => $query->where('patient_id', $patient->getKey()))
            ->count();
    }
}
