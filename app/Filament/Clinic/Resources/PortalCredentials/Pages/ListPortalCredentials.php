<?php

namespace App\Filament\Clinic\Resources\PortalCredentials\Pages;

use App\Filament\Clinic\Resources\PortalCredentials\PortalCredentialResource;
use App\Support\ClinicPanelScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use App\Models\PortalCredential;
use App\Support\VerificationManagedServiceAccess;

class ListPortalCredentials extends ListRecords
{
    protected static string $resource = PortalCredentialResource::class;

    protected string $view = 'filament.clinic.resources.portal-credentials.pages.list-portal-credentials';

    public string $search = '';
    public bool $passwordModalOpen = false;
    public ?int $editingCredentialId = null;
    public ?string $editingCredentialName = null;
    public ?string $editingCredentialLink = null;
    public ?string $editingCredentialUsername = null;
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
    }

    public function getPortalCredentials(): Collection
    {
        return $this->getScopedPortalCredentialQuery()
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

    public function canUpdatePasswords(): bool
    {
        return VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()
            && filled(ClinicPanelScope::selectedClinicId())
            && (auth()->user()?->canPerformClinicModuleAction('portal_credentials', 'update') ?? false);
    }

    public function openPasswordEditor(int $credentialId): void
    {
        abort_unless($this->canUpdatePasswords(), 403);

        $credential = $this->getScopedPortalCredentialQuery()->findOrFail($credentialId);

        $this->editingCredentialId = $credential->getKey();
        $this->editingCredentialName = $credential->portal_name;
        $this->editingCredentialLink = $credential->login_url;
        $this->editingCredentialUsername = $credential->username;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->passwordModalOpen = true;
        $this->resetErrorBag();
    }

    public function closePasswordEditor(): void
    {
        $this->passwordModalOpen = false;
        $this->editingCredentialId = null;
        $this->editingCredentialName = null;
        $this->editingCredentialLink = null;
        $this->editingCredentialUsername = null;
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->resetErrorBag();
    }

    public function updateCredentialPassword(): void
    {
        abort_unless($this->canUpdatePasswords(), 403);

        $this->validate([
            'newPassword' => ['required', 'string', 'min:8', 'max:255', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required', 'string', 'min:8', 'max:255'],
        ], [
            'newPassword.same' => 'Password confirmation does not match.',
        ]);

        $credential = $this->getScopedPortalCredentialQuery()->findOrFail($this->editingCredentialId);
        $credential->update([
            'password' => $this->newPassword,
        ]);

        Notification::make()
            ->success()
            ->title('Password updated')
            ->body('The portal password was updated and added to audit history.')
            ->send();

        $this->closePasswordEditor();
    }

    protected function getScopedPortalCredentialQuery()
    {
        return PortalCredential::query()->when(
            filled(ClinicPanelScope::selectedClinicId()),
            fn ($query) => $query
                ->where('clinic_id', ClinicPanelScope::selectedClinicId())
                ->where('visible_to_clinic', true),
            fn ($query) => $query->whereRaw('1 = 0')
        );
    }

    public function getHeading(): string
    {
        return '';
    }
}
