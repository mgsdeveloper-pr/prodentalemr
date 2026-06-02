<?php

namespace App\Filament\Clinic\Resources\InsuranceCarriers\Pages;

use App\Filament\Clinic\Pages\VerificationQuestionArrangement;
use App\Filament\Clinic\Pages\VerificationSettings;
use App\Filament\Clinic\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Support\ClinicPanelScope;
use App\Support\VerificationManagedServiceAccess;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceCarriers extends ListRecords
{
    protected static string $resource = InsuranceCarrierResource::class;

    protected string $view = 'filament.clinic.resources.insurance-carriers.pages.list-insurance-carriers';

    public function getVerificationNavItems(): array
    {
        $items = [
            [
                'key' => 'settings',
                'label' => 'PDF Settings',
                'description' => 'Control PDF output and default verification template rules.',
                'url' => VerificationSettings::getUrl(),
            ],
            [
                'key' => 'insurance',
                'label' => 'Insurance Directory',
                'description' => 'Review the shared carrier master and maintain clinic-specific overrides.',
                'url' => InsuranceCarrierResource::getUrl('index'),
            ],
        ];

        if (VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()) {
            $items[] = [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'description' => 'Keep clinic-specific website and payer portal credentials without using spreadsheets.',
                'url' => PortalCredentialResource::getUrl('index'),
            ];
        }

        $items[] = [
            'key' => 'questions',
            'label' => 'Verification Questions',
            'description' => 'Manage prompts and section-specific question content.',
            'url' => VerificationQuestionResource::getUrl('index'),
        ];

        $items[] = [
            'key' => 'arrangement',
            'label' => 'Question Arrangement',
            'description' => 'Reorder questions inside each verification section.',
            'url' => VerificationQuestionArrangement::getUrl(),
        ];

        return $items;
    }

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
    }
}
