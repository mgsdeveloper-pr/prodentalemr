<?php

namespace App\Support;

use App\Filament\Admin\Pages\PortalCredentialSettings;
use App\Filament\Admin\Pages\UserMailboxSettingsPage;
use App\Filament\Admin\Pages\VerificationAssignmentManagement;
use App\Filament\Admin\Pages\VerificationGeneralSettings;
use App\Filament\Admin\Pages\VerificationInboxSettings;
use App\Filament\Admin\Pages\VerificationNotificationControl;
use App\Filament\Admin\Pages\VerificationSettings;
use App\Filament\Saas\Resources\InsuranceCarriers\InsuranceCarrierResource;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use Filament\Facades\Filament;

class VerificationSettingsNavigation
{
    public static function items(): array
    {
        if (Filament::getCurrentPanel()?->getId() === 'saas') {
            return [
                [
                    'key' => 'insurance',
                    'label' => 'Insurance Directory',
                    'description' => 'Maintain the shared insurance carrier master and clinic-specific defaults.',
                    'url' => InsuranceCarrierResource::getUrl('index'),
                ],
                [
                    'key' => 'questions',
                    'label' => 'Master Template',
                    'description' => 'Manage the global template that client and clinic copies are created from.',
                    'url' => VerificationFormQuestionResource::getUrl('index'),
                ],
            ];
        }

        $items = [];

        if (VerificationGeneralSettings::canAccess()) {
            $items[] = [
                'key' => 'general',
                'label' => 'General',
                'icon' => 'heroicon-o-cog-6-tooth',
                'url' => VerificationGeneralSettings::getUrl(),
            ];
        }

        if (VerificationSettings::canAccess()) {
            $items[] = [
                'key' => 'pdf',
                'label' => 'PDF & Output',
                'icon' => 'heroicon-o-document-text',
                'url' => VerificationSettings::getUrl(),
            ];
        }

        if (PortalCredentialSettings::canAccess()) {
            $items[] = [
                'key' => 'credentials',
                'label' => 'Portal Credentials',
                'icon' => 'heroicon-o-key',
                'url' => PortalCredentialSettings::getUrl(),
            ];
        }

        $mailboxItems = [];

        if (UserMailboxSettingsPage::canAccess()) {
            $mailboxItems[] = [
                'key' => 'mailbox-personal',
                'label' => 'My Mailbox',
                'url' => UserMailboxSettingsPage::getUrl(),
            ];
        }

        if (VerificationInboxSettings::canAccess()) {
            $mailboxItems[] = [
                'key' => 'mailbox-clinic',
                'label' => 'Clinic Inbox',
                'url' => VerificationInboxSettings::getUrl(),
            ];
        }

        if ($mailboxItems !== []) {
            $items[] = [
                'key' => 'mailbox',
                'label' => 'Mailbox',
                'icon' => 'heroicon-o-envelope',
                'children' => $mailboxItems,
            ];
        }

        if (VerificationNotificationControl::canAccess()) {
            $items[] = [
                'key' => 'notifications',
                'label' => 'Notifications',
                'icon' => 'heroicon-o-bell-alert',
                'url' => VerificationNotificationControl::getUrl(),
            ];
        }

        if (VerificationAssignmentManagement::canAccess()) {
            $items[] = [
                'key' => 'assignment',
                'label' => 'Assignment Rules',
                'icon' => 'heroicon-o-arrows-right-left',
                'url' => VerificationAssignmentManagement::getUrl(),
            ];
        }

        return $items;
    }
}
