<?php

namespace App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\Pages;

use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Filament\Admin\Pages\VerificationQuestionArrangement;
use App\Filament\Admin\Pages\VerificationReadiness;
use App\Filament\Admin\Pages\VerificationAssignmentManagement;
use App\Filament\Admin\Pages\VerificationSettings;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource;
use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Support\VerificationSettingsNavigation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCarrierNetworkProfiles extends ListRecords
{
    protected static string $resource = InsuranceCarrierNetworkProfileResource::class;

    protected string $view = 'filament.saas.resources.insurance-carrier-network-profiles.pages.list-insurance-carrier-network-profiles';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Participation Rule')
                ->icon('heroicon-o-plus')
                ->color('warning'),
            Action::make('manageCarriers')
                ->label('Insurance Directory')
                ->icon('heroicon-o-building-library')
                ->color('gray')
                ->url(InsuranceCarrierResource::getUrl('index')),
        ];
    }

    public function getVerificationNavItems(): array
    {
        return VerificationSettingsNavigation::items();
    }
}
