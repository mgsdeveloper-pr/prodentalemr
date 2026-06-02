<?php

namespace App\Filament\Clinic\Widgets;

use App\Models\Appointment;
use App\Models\PatientInsuranceClaim;
use App\Models\PatientLedgerEntry;
use App\Models\PatientStatement;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClinicOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user?->organization_id || ! $user?->clinic_id) {
            return [];
        }

        $todayAppointments = Appointment::query()
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id)
            ->whereDate('appointment_date', today())
            ->count();

        $openBalance = (float) PatientLedgerEntry::query()
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id)
            ->where('status', '!=', 'void')
            ->selectRaw('COALESCE(SUM(debit_amount - credit_amount), 0) as balance')
            ->value('balance');

        $pendingClaims = PatientInsuranceClaim::query()
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id)
            ->whereIn('status', ['ready', 'submitted', 'pending', 'partially_paid'])
            ->count();

        $statementsSentThisMonth = PatientStatement::query()
            ->where('organization_id', $user->organization_id)
            ->where('clinic_id', $user->clinic_id)
            ->whereNotNull('sent_at')
            ->whereMonth('sent_at', now()->month)
            ->whereYear('sent_at', now()->year)
            ->count();

        return [
            Stat::make("Today's appointments", number_format($todayAppointments))
                ->description('Scheduled visits on the books today'),
            Stat::make('Open patient balance', '$' . number_format($openBalance, 2))
                ->description('Current patient ledger balance across the clinic'),
            Stat::make('Claims needing follow-up', number_format($pendingClaims))
                ->description('Ready, submitted, pending, or partial claims'),
            Stat::make('Statements sent this month', number_format($statementsSentThisMonth))
                ->description('Patient statements already delivered this month'),
        ];
    }
}
