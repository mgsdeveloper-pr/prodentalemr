<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Models\Clinic;
use App\Models\PortalCredential;
use App\Support\ClinicPanelScope;
use App\Support\VerificationManagedServiceAccess;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class PortalCredentialSettings extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Portal Credential Settings';

    protected static ?string $title = 'Portal Credentials';

    protected static ?string $slug = 'portal-credential-settings';

    protected string $view = 'filament.clinic.pages.portal-credential-settings';

    protected ?Clinic $clinicRecord = null;

    public string $search = '';

    public static function canAccess(): bool
    {
        return VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()
            && (auth()->user()?->canManageClinicVerificationSettings() ?? false);
    }

    public function getSelectedClinic(): ?Clinic
    {
        return $this->resolveClinic();
    }

    public function getPortalCredentials(): Collection
    {
        return PortalCredential::query()
            ->when(
                filled(ClinicPanelScope::selectedClinicId()),
                fn ($query) => $query->where('clinic_id', ClinicPanelScope::selectedClinicId()),
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
        return VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()
            && (auth()->user()?->canAccessClinicModule('portal_credentials') ?? false);
    }

    public function canEditPortalCredentials(): bool
    {
        return VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()
            && filled(ClinicPanelScope::selectedClinicId())
            && (auth()->user()?->canPerformClinicModuleAction('portal_credentials', 'update') ?? false);
    }

    public function canDeletePortalCredentials(): bool
    {
        return $this->canEditPortalCredentials();
    }

    public function editPortalCredential(int $credentialId)
    {
        return redirect()->to(PortalCredentialResource::getUrl('edit', ['record' => $credentialId]));
    }

    public function deletePortalCredential(int $credentialId): void
    {
        if (! $this->canDeletePortalCredentials()) {
            return;
        }

        PortalCredential::query()->findOrFail($credentialId)->delete();
    }

    protected function resolveClinic(): ?Clinic
    {
        if ($this->clinicRecord instanceof Clinic) {
            $selectedId = ClinicPanelScope::selectedClinicId();

            if ($selectedId && $this->clinicRecord->getKey() !== $selectedId) {
                $this->clinicRecord = null;
            }
        }

        if ($this->clinicRecord instanceof Clinic) {
            return $this->clinicRecord;
        }

        $selected = ClinicPanelScope::selectedClinic();

        if ($selected) {
            $this->clinicRecord = $selected;

            return $this->clinicRecord;
        }

        $user = auth()->user();

        if (! filled($user?->clinic_id)) {
            return null;
        }

        $this->clinicRecord = Clinic::query()
            ->with('organization')
            ->find($user->clinic_id);

        return $this->clinicRecord;
    }
}
