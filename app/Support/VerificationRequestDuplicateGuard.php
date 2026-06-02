<?php

namespace App\Support;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class VerificationRequestDuplicateGuard
{
    public static function ensureNotQueued(
        array $workItemData,
        array $verificationProfileData,
        array $planSnapshots = [],
        ?int $ignoreId = null,
        string $panel = 'verification',
    ): void
    {
        $existing = static::findExisting($workItemData, $verificationProfileData, $planSnapshots, $ignoreId);

        if (! $existing) {
            return;
        }

        throw ValidationException::withMessages([
            'vf_appointment_date' => static::duplicateMessage($existing, $verificationProfileData, $panel),
        ]);
    }

    public static function findExisting(array $workItemData, array $verificationProfileData, array $planSnapshots = [], ?int $ignoreId = null): ?BillingWorkItem
    {
        $clinicId = $workItemData['clinic_id'] ?? null;
        $patientId = $workItemData['patient_id'] ?? null;
        $appointmentDate = $verificationProfileData['appointment_date'] ?? null;
        $patientName = trim((string) ($verificationProfileData['patient_full_name'] ?? ''));
        $patientDob = $verificationProfileData['patient_dob'] ?? null;
        $incomingInsurance = static::extractIncomingInsuranceContext($verificationProfileData, $planSnapshots);

        if (blank($clinicId) || blank($appointmentDate)) {
            return null;
        }

        if (blank($patientId) && ($patientName === '' || blank($patientDob))) {
            return null;
        }

        $query = BillingWorkItem::query()
            ->with(['verificationProfile', 'verificationPlanSnapshots', 'insurancePolicy'])
            ->where('clinic_id', $clinicId)
            ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
            ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
            ->whereHas('verificationProfile', function (Builder $builder) use ($appointmentDate, $patientName, $patientDob, $patientId): void {
                $builder->whereDate('appointment_date', $appointmentDate);

                if (filled($patientId)) {
                    return;
                }

                $builder
                    ->where('patient_full_name', $patientName)
                    ->whereDate('patient_dob', $patientDob);
            })
            ->when(filled($patientId), fn (Builder $builder) => $builder->where('patient_id', $patientId))
            ->when(filled($ignoreId), fn (Builder $builder) => $builder->whereKeyNot($ignoreId))
            ->orderByDesc('created_at')
            ->get();

        return $query
            ->first(fn (BillingWorkItem $candidate): bool => static::matchesInsuranceContext($candidate, $incomingInsurance));
    }

    public static function duplicateMessage(BillingWorkItem $existing, array $verificationProfileData, string $panel = 'verification'): string
    {
        $details = static::duplicatePayload($existing, $verificationProfileData, $panel);

        return "A verification request is already in queue for {$details['appointment_date_label']}. "
            . "Reference: {$details['reference']}. "
            . "Payer: {$details['payer_label']}. "
            . "Status: {$details['status_label']}. "
            . "Open existing request: {$details['url']}";
    }

    public static function duplicatePayload(BillingWorkItem $existing, array $verificationProfileData, string $panel = 'verification'): array
    {
        $appointmentDate = $verificationProfileData['appointment_date'] ?? $existing->verificationProfile?->appointment_date?->format('Y-m-d');
        $insurance = static::extractExistingInsuranceContext($existing);
        $payer = $insurance['payer_name'] ?? null;

        return [
            'reference' => $existing->reference_number,
            'payer' => $payer,
            'payer_label' => filled($payer) ? str($payer)->title()->toString() : 'Not specified',
            'status' => $existing->status,
            'status_label' => BillingWorkItem::STATUS_OPTIONS[$existing->normalized_status]
                ?? str((string) $existing->normalized_status)->replace('_', ' ')->title()->toString(),
            'appointment_date' => $appointmentDate,
            'appointment_date_label' => filled($appointmentDate)
                ? date('M d, Y', strtotime((string) $appointmentDate))
                : 'this appointment',
            'url' => static::existingRecordUrl($existing, $panel),
        ];
    }

    protected static function extractIncomingInsuranceContext(array $verificationProfileData, array $planSnapshots): array
    {
        $primaryPlan = collect($planSnapshots)->first(fn ($plan) => filled($plan['payer_name'] ?? null) || filled($plan['member_id'] ?? null)) ?? [];

        return [
            'payer_name' => static::normalizeString($primaryPlan['payer_name'] ?? ($verificationProfileData['insurance_provider_name'] ?? null)),
            'member_id' => static::normalizeString($primaryPlan['member_id'] ?? ($verificationProfileData['patient_identifier'] ?? null)),
        ];
    }

    protected static function extractExistingInsuranceContext(BillingWorkItem $candidate): array
    {
        $primaryPlan = $candidate->verificationPlanSnapshots
            ->first(fn ($plan) => filled($plan->payer_name) || filled($plan->member_id));

        return [
            'payer_name' => static::normalizeString(
                $primaryPlan?->payer_name
                ?: $candidate->verificationProfile?->insurance_provider_name
                ?: $candidate->insurancePolicy?->insurance_company
            ),
            'member_id' => static::normalizeString(
                $primaryPlan?->member_id
                ?: $candidate->verificationProfile?->patient_identifier
                ?: $candidate->insurancePolicy?->member_id
            ),
        ];
    }

    protected static function matchesInsuranceContext(BillingWorkItem $candidate, array $incomingInsurance): bool
    {
        $incomingPayer = $incomingInsurance['payer_name'] ?? null;
        $incomingMember = $incomingInsurance['member_id'] ?? null;

        if (blank($incomingPayer) && blank($incomingMember)) {
            return true;
        }

        $existingInsurance = static::extractExistingInsuranceContext($candidate);
        $existingPayer = $existingInsurance['payer_name'] ?? null;
        $existingMember = $existingInsurance['member_id'] ?? null;

        $hasPositiveMatch = false;

        if (filled($incomingMember) && filled($existingMember)) {
            if ($incomingMember !== $existingMember) {
                return false;
            }

            $hasPositiveMatch = true;
        }

        if (filled($incomingPayer) && filled($existingPayer)) {
            if ($incomingPayer !== $existingPayer) {
                return false;
            }

            $hasPositiveMatch = true;
        }

        if ($hasPositiveMatch) {
            return true;
        }

        if (filled($incomingMember) && blank($existingMember)) {
            return false;
        }

        if (filled($incomingPayer) && blank($existingPayer)) {
            return false;
        }

        return false;
    }

    protected static function normalizeString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : mb_strtolower($normalized);
    }

    protected static function existingRecordUrl(BillingWorkItem $existing, string $panel): string
    {
        return match ($panel) {
            'clinic' => VerificationRequestResource::getUrl(
                $existing->clinicUserCanEditVerification(auth()->user()) ? 'edit' : 'view',
                ['record' => $existing]
            ),
            'verification', 'admin' => VerificationWorkItemResource::getUrl('edit', ['record' => $existing]),
            default => url('/verification/verifications/' . $existing->getKey() . '/edit'),
        };
    }
}
