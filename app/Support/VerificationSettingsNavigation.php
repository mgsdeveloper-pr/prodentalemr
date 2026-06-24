<?php

namespace App\Support;

use App\Filament\Admin\Pages\PortalCredentialSettings;
use App\Filament\Admin\Pages\VerificationInboxSettings;
use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Filament\Admin\Pages\VerificationQuestionArrangement;
use App\Filament\Admin\Pages\VerificationReadiness;
use App\Filament\Admin\Pages\VerificationSettings;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\InsuranceCarrierNetworkProfileResource;
use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;

class VerificationSettingsNavigation
{
    public static function items(): array
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
                'description' => 'Maintain the shared insurance carrier master and clinic-specific defaults.',
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
                'url' => PortalCredentialSettings::getUrl(),
            ],
            [
                'key' => 'inbox',
                'label' => 'Inbox Configuration',
                'description' => 'Connect the shared mailbox, control sync frequency, and define cleanup rules.',
                'url' => VerificationInboxSettings::getUrl(),
            ],
            [
                'key' => 'questions',
                'label' => 'Template Management',
                'description' => 'Manage Template 1 and Template 2 questions, datatypes, dropdowns, notes, and section placement.',
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
                'label' => 'Notification Settings',
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
