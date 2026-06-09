<?php

namespace App\Filament\Saas\Resources\InsuranceCarriers\Pages;

use App\Filament\Admin\Pages\VerificationQuestionArrangement;
use App\Filament\Admin\Pages\VerificationReadiness;
use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Filament\Admin\Pages\VerificationAssignmentManagement;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource;
use App\Filament\Admin\Pages\VerificationSettings;
use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Support\VerificationSettingsNavigation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCarriers extends ListRecords
{
    protected static string $resource = InsuranceCarrierResource::class;

    protected string $view = 'filament.saas.resources.insurance-carriers.pages.list-insurance-carriers';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Insurance')
                ->icon('heroicon-o-plus')
                ->color('warning'),
            Action::make('manageQuestions')
                ->label('Verification Questions')
                ->icon('heroicon-o-rectangle-stack')
                ->color('gray')
                ->url(VerificationFormQuestionResource::getUrl('index')),
        ];
    }

    public function getVerificationNavItems(): array
    {
        return VerificationSettingsNavigation::items();
    }
}
