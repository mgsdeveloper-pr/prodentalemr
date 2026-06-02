<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Filament\Saas\Resources\Verifications\Schemas\VerificationRequestForm;
use App\Models\ClientServiceEnrollment;
use App\Support\VerificationAutoAssigner;
use App\Support\VerificationRequestDuplicateGuard;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateVerificationWorkItem extends CreateRecord
{
    protected static string $resource = VerificationWorkItemResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $verificationProfileData = [];

    protected array $verificationPlanSnapshotData = [];

    public function form(Schema $schema): Schema
    {
        return VerificationRequestForm::configure($schema);
    }

    public function getTitle(): string
    {
        return 'Create Insurance Verification Request';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->verificationPlanSnapshotData = $data['verification_plan_snapshots'] ?? [];
        unset($data['verification_plan_snapshots']);

        [$data, $this->verificationProfileData] = static::splitVerificationProfileData($data);

        $patientName = $this->verificationProfileData['patient_full_name'] ?? null;
        $appointmentDate = $this->verificationProfileData['appointment_date'] ?? null;

        $data['title'] = trim(collect([
            'Insurance Verification',
            $patientName,
            filled($appointmentDate) ? date('M d, Y', strtotime((string) $appointmentDate)) : null,
        ])->filter()->implode(' - '));

        $data['due_at'] = static::resolveDueAt($data);
        $data['assigned_to'] = $data['assigned_to'] ?: VerificationAutoAssigner::resolve(
            $data['source'] ?? null,
            filled($data['clinic_id'] ?? null) ? (int) $data['clinic_id'] : null,
        )?->id;

        VerificationRequestDuplicateGuard::ensureNotQueued(
            $data,
            $this->verificationProfileData,
            $this->verificationPlanSnapshotData,
            null,
            'verification',
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->verificationProfile()->updateOrCreate([], $this->verificationProfileData);
        $this->record->verificationPlanSnapshots()->delete();
        $this->record->verificationPlanSnapshots()->createMany($this->verificationPlanSnapshotData);
        $this->record->recordActivity('verification_profile_saved', 'Structured verification details captured.');
        $this->record->recordActivity('verification_request_created', 'Verification request intake captured.');
    }

    protected static function splitVerificationProfileData(array $data): array
    {
        $verificationData = [];

        foreach ($data as $key => $value) {
            if (! str_starts_with($key, 'vf_')) {
                continue;
            }

            $verificationData[str_replace('vf_', '', $key)] = $value;
            unset($data[$key]);
        }

        return [$data, $verificationData];
    }

    protected static function resolveDueAt(array $data)
    {
        $enrollment = filled($data['client_service_enrollment_id'] ?? null)
            ? ClientServiceEnrollment::query()->find($data['client_service_enrollment_id'])
            : null;

        if ($enrollment) {
            return $enrollment->calculateDueAt((string) ($data['priority'] ?? 'normal'));
        }

        return ($data['priority'] ?? 'normal') === 'urgent'
            ? now()->addHours(24)
            : now()->addDays(3);
    }
}
