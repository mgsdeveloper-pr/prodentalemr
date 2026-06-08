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
        return [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => VerificationSettings::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Manage the global insurance carrier master used across clinics.',
                'url' => InsuranceCarrierResource::getUrl('index'),
            ],
            [
                'key' => 'participation',
                'label' => 'Provider Participation',
                'description' => 'Manage participating and non-participating payer guidance for verifiers.',
                'url' => InsuranceCarrierNetworkProfileResource::getUrl('index'),
            ],
            [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'description' => 'Maintain the shared portal credential vault clinics can inherit from.',
                'url' => PortalCredentialResource::getUrl('index'),
            ],
            [
                'key' => 'questions',
                'label' => 'Verification Questions',
                'description' => 'Manage prompts and section-specific question content.',
                'url' => VerificationFormQuestionResource::getUrl('index'),
            ],
            [
                'key' => 'arrangement',
                'label' => 'Question Arrangement',
                'description' => 'Reorder questions inside each verification section.',
                'url' => VerificationQuestionArrangement::getUrl(),
            ],
            [
                'key' => 'notifications',
                'label' => 'Notification Control',
                'description' => 'Manage verification events, recipients, and urgent alert behavior.',
                'url' => VerificationNotificationControl::getUrl(),
            ],
            [
                'key' => 'readiness',
                'label' => 'Verification Readiness',
                'description' => 'Review launch blockers, polish items, and readiness gaps.',
                'url' => VerificationReadiness::getUrl(),
            ],
        ];
    }
}
