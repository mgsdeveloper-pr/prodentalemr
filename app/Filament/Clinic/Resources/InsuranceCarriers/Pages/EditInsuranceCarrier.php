<?php

namespace App\Filament\Clinic\Resources\InsuranceCarriers\Pages;

use App\Filament\Clinic\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Models\ClinicInsuranceCarrierOverride;
use App\Models\InsuranceCarrier;
use App\Support\ClinicPanelScope;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditInsuranceCarrier extends EditRecord
{
    protected static string $resource = InsuranceCarrierResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var InsuranceCarrier $carrier */
        $carrier = $this->record;
        $effective = $carrier->effectiveAttributesForClinic(ClinicPanelScope::selectedClinicId());

        return array_merge($data, [
            'insurance_name' => $effective['insurance_name'],
            'payer_id' => $effective['payer_id'],
            'payer_phone' => $effective['payer_phone'],
            'claims_address' => $effective['claims_address'],
            'website' => $effective['website'],
            'notes' => $effective['notes'],
            'is_active' => $effective['is_active'],
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose a clinic from the Workspace menu before creating clinic-specific insurance overrides.')
                ->danger()
                ->send();

            return $record;
        }

        ClinicInsuranceCarrierOverride::updateOrCreate(
            [
                'clinic_id' => $clinic->getKey(),
                'insurance_carrier_id' => $record->getKey(),
            ],
            [
                'organization_id' => $clinic->organization_id,
                'insurance_name' => $data['insurance_name'] ?? null,
                'payer_id' => $data['payer_id'] ?? null,
                'payer_phone' => $data['payer_phone'] ?? null,
                'claims_address' => $data['claims_address'] ?? null,
                'website' => $data['website'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ],
        );

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetToMaster')
                ->label('Reset to Master')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->hasOverride())
                ->action(function (): void {
                    $override = $this->getOverride();

                    if ($override) {
                        $override->delete();
                    }

                    /** @var InsuranceCarrier $carrier */
                    $carrier = $this->record;
                    $effective = $carrier->effectiveAttributesForClinic(ClinicPanelScope::selectedClinicId());

                    $this->form->fill([
                        'insurance_name' => $effective['insurance_name'],
                        'payer_id' => $effective['payer_id'],
                        'payer_phone' => $effective['payer_phone'],
                        'claims_address' => $effective['claims_address'],
                        'website' => $effective['website'],
                        'notes' => $effective['notes'],
                        'is_active' => $effective['is_active'],
                    ]);

                    Notification::make()
                        ->title('Clinic override removed')
                        ->body('This clinic will now use the global insurance master values again.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function hasOverride(): bool
    {
        return $this->getOverride() instanceof ClinicInsuranceCarrierOverride;
    }

    protected function getOverride(): ?ClinicInsuranceCarrierOverride
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            return null;
        }

        /** @var InsuranceCarrier $carrier */
        $carrier = $this->record;

        return $carrier->overrideForClinic($clinicId);
    }
}
