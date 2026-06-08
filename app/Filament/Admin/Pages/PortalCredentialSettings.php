<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Models\Clinic;
use App\Models\PortalCredential;
use App\Support\AdminClinicScope;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class PortalCredentialSettings extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Verification Workspace';

    protected static ?string $navigationLabel = 'Portal Credentials';

    protected static ?string $title = 'Portal Credentials';

    protected static ?string $slug = 'portal-credential-settings';

    protected string $view = 'filament.admin.pages.portal-credential-settings';

    protected ?Clinic $clinicRecord = null;

    public string $search = '';

    public static function canAccess(): bool
    {
        return (bool) (
            auth()->user()?->canManageVerificationSettings()
            || auth()->user()?->canAccessVerificationModule('portal_credentials')
            || auth()->user()?->canAccessSaasModule('portal_credentials')
        );
    }

    public function getSelectedClinic(): ?Clinic
    {
        return $this->resolveClinic();
    }

    public function getPortalCredentials(): Collection
    {
        return PortalCredential::query()
            ->when(
                filled(AdminClinicScope::selectedClinicId()),
                fn ($query) => $query->where('clinic_id', AdminClinicScope::selectedClinicId()),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->when(filled($this->search), function ($query): void {
                $query->where(function ($builder): void {
                    $builder
                        ->where('portal_name', 'like', '%' . $this->search . '%')
                        ->orWhere('login_url', 'like', '%' . $this->search . '%')
                        ->orWhere('portal_category', 'like', '%' . $this->search . '%')
                        ->orWhere('account_reference', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('portal_name')
            ->get();
    }

    public function canManagePortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canAccessVerificationModule('portal_credentials')
            || auth()->user()?->canAccessSaasModule('portal_credentials')
        );
    }

    public function canCreatePortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'add')
            || auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'add')
        );
    }

    public function canEditPortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'update')
            || auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'update')
        );
    }

    public function canDeletePortalCredentials(): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'delete')
            || auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'delete')
        );
    }

    public function createPortalCredential()
    {
        return redirect()->to(PortalCredentialResource::getUrl('create'));
    }

    public function editPortalCredential(int $credentialId)
    {
        return redirect()->to(PortalCredentialResource::getUrl('edit', ['record' => $credentialId]));
    }

    public function deletePortalCredential(int $credentialId): void
    {
        if (! $this->canDeletePortalCredentials()) {
            Notification::make()
                ->title('You do not have access')
                ->body('Your account cannot remove clinic portal credentials.')
                ->danger()
                ->send();

            return;
        }

        PortalCredential::query()->findOrFail($credentialId)->delete();

        Notification::make()
            ->title('Portal credential removed')
            ->body('The portal credential has been removed from the selected clinic.')
            ->success()
            ->send();
    }

    protected function resolveClinic(): ?Clinic
    {
        if ($this->clinicRecord instanceof Clinic) {
            $selectedId = AdminClinicScope::selectedClinicId();

            if ($selectedId && $this->clinicRecord->getKey() !== $selectedId) {
                $this->clinicRecord = null;
            }
        }

        if ($this->clinicRecord instanceof Clinic) {
            return $this->clinicRecord;
        }

        $selected = AdminClinicScope::selectedClinic();

        if ($selected) {
            $this->clinicRecord = $selected;

            return $this->clinicRecord;
        }

        return null;
    }
}
