<?php

namespace App\Support;

trait HasVerificationIntakePresentation
{
    public function getPageClasses(): array
    {
        return [...parent::getPageClasses(), 'pd-verification-intake'];
    }

    public function areFormActionsSticky(): bool
    {
        return false;
    }

    public function getFormContentComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getFormContentComponent()->extraAttributes(['class' => 'pd-intake-surface']);
    }

    public function getSubheading(): ?string
    {
        $clinic = \Filament\Facades\Filament::getCurrentPanel()?->getId() === 'clinic'
            ? ClinicPanelScope::selectedClinic() : AdminClinicScope::selectedClinic();

        return $clinic ? $clinic->clinic_name : 'Select a clinic location';
    }

    protected function getFormActions(): array
    {
        return [$this->getCancelFormAction()->button()->outlined(), $this->getCreateFormAction()];
    }

    public function getFormActionsContentComponent(): \Filament\Schemas\Components\Component
    {
        return parent::getFormActionsContentComponent()->extraAttributes(['class' => 'pd-intake-actions']);
    }
}
