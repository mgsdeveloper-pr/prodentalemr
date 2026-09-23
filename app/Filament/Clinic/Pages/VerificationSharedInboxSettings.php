<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Admin\Pages\VerificationInboxSettings;
use App\Models\Clinic;
use App\Support\ClinicPanelScope;

class VerificationSharedInboxSettings extends VerificationInboxSettings
{
    protected static ?string $title = 'Shared Inbox Settings';

    protected static ?string $slug = 'shared-inbox-settings';

    public static function canAccess(): bool
    {
        return (auth()->user()?->canManageClinicVerificationSettings() ?? false)
            && (ClinicPanelScope::selectedClinic() === null || VerificationSharedInbox::canAccess());
    }

    protected function selectedClinic(): ?Clinic
    {
        return ClinicPanelScope::selectedClinic();
    }

    public function getBreadcrumbs(): array
    {
        return [
            VerificationSettings::getUrl(panel: 'clinic') => 'Verification Settings',
            'Shared Inbox Settings',
        ];
    }

    public function getVerificationNavItems(): array
    {
        $items = [
            ['key' => 'general', 'label' => 'Verification Settings', 'icon' => 'heroicon-o-cog-6-tooth', 'url' => VerificationSettings::getUrl(panel: 'clinic')],
            ['key' => 'mailbox-clinic', 'label' => 'Shared Inbox Settings', 'icon' => 'heroicon-o-envelope', 'url' => static::getUrl(panel: 'clinic')],
        ];

        if (VerificationSharedInbox::canAccess()) {
            $items[] = ['key' => 'inbox', 'label' => 'Shared Inbox', 'icon' => 'heroicon-o-inbox-stack', 'url' => VerificationSharedInbox::getUrl(panel: 'clinic')];
        }

        return $items;
    }
}
