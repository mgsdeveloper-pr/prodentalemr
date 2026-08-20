<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Actions\Verification\CreateVerificationRequestAction;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Filament\Saas\Resources\Verifications\Schemas\VerificationRequestForm;
use App\Support\VerificationRequestDuplicateGuard;
use App\Support\VerificationTemplateVersionService;
use App\Services\Verification\VerificationIntakeService;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateVerificationRequest extends CreateRecord
{
    protected static string $resource = VerificationRequestResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected array $verificationProfileData = [];

    protected array $verificationPlanSnapshotData = [];

    protected static bool $canCreateAnother = false;

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

        $data = app(VerificationIntakeService::class)->normalizeAndValidate(
            $data,
            $this->verificationProfileData,
            $this->verificationPlanSnapshotData,
        );
        $data = app(CreateVerificationRequestAction::class)->prepareData($data);

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
        $this->record = app(VerificationTemplateVersionService::class)->attachSnapshotToWorkItem($this->record);
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

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Create Request');
    }
}
