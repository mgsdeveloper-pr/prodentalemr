<?php

namespace App\Filament\Saas\Resources\PortalCredentials\Pages;

use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Models\PortalCredential;
use App\Support\AdminClinicScope;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListPortalCredentials extends ListRecords
{
    protected static string $resource = PortalCredentialResource::class;

    protected string $view = 'filament.saas.resources.portal-credentials.pages.list-portal-credentials';

    public string $search = '';
    public bool $passwordModalOpen = false;
    public ?int $editingCredentialId = null;
    public ?string $editingCredentialName = null;
    public ?string $editingCredentialLink = null;
    public ?string $editingCredentialUsername = null;
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public bool $createModalOpen = false;
    public string $createPortalName = '';
    public string $createPortalCategory = 'insurance';
    public string $createLoginUrl = '';
    public string $createSupportContact = '';
    public string $createUsername = '';
    public string $createPassword = '';
    public string $createAccountReference = '';
    public string $createRegistrationQaNotes = '';
    public string $createGeneralNotes = '';
    public bool $createMfaRequired = false;
    public string $createMfaMethod = 'none';
    public bool $createIsActive = true;
    public bool $createVisibleToClinic = false;
    public bool $infoModalOpen = false;
    public ?string $infoCredentialName = null;
    public ?string $infoCredentialRegistrationQaNotes = null;
    public ?string $infoCredentialGeneralNotes = null;

    public function getSelectedClinicName(): ?string
    {
        return AdminClinicScope::selectedClinic()?->clinic_name;
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
        return filled(AdminClinicScope::selectedClinicId())
            && (
                (auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'update') ?? false)
                || (auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'update') ?? false)
            );
    }

    public function canCreatePortalCredentials(): bool
    {
        return PortalCredentialResource::canCreate();
    }

    public function openCreatePortalCredentialModal(): void
    {
        abort_unless($this->canCreatePortalCredentials(), 403);

        $this->createModalOpen = true;
        $this->resetCreatePortalCredentialForm();
        $this->resetErrorBag();
    }

    public function closeCreatePortalCredentialModal(): void
    {
        $this->createModalOpen = false;
        $this->resetCreatePortalCredentialForm();
        $this->resetErrorBag();
    }

    public function createPortalCredential(): void
    {
        abort_unless($this->canCreatePortalCredentials(), 403);

        $clinic = AdminClinicScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->body('Choose a clinic from the Workspace menu before adding a portal credential.')
                ->danger()
                ->send();

            return;
        }

        $validated = $this->validate([
            'createPortalName' => ['required', 'string', 'max:255'],
            'createPortalCategory' => ['required', 'string', 'in:' . implode(',', array_keys(PortalCredential::CATEGORY_OPTIONS))],
            'createLoginUrl' => ['nullable', 'url', 'max:255'],
            'createSupportContact' => ['nullable', 'string', 'max:255'],
            'createUsername' => ['nullable', 'string', 'max:255'],
            'createPassword' => ['nullable', 'string', 'max:255'],
            'createAccountReference' => ['nullable', 'string', 'max:255'],
            'createRegistrationQaNotes' => ['nullable', 'string'],
            'createGeneralNotes' => ['nullable', 'string'],
            'createMfaMethod' => ['required', 'string', 'in:' . implode(',', array_keys(PortalCredential::MFA_METHOD_OPTIONS))],
            'createIsActive' => ['boolean'],
            'createMfaRequired' => ['boolean'],
            'createVisibleToClinic' => ['boolean'],
        ]);

        PortalCredential::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->getKey(),
            'portal_name' => $validated['createPortalName'],
            'portal_category' => $validated['createPortalCategory'],
            'login_url' => filled($validated['createLoginUrl']) ? $validated['createLoginUrl'] : null,
            'support_contact' => filled($validated['createSupportContact']) ? $validated['createSupportContact'] : null,
            'username' => filled($validated['createUsername']) ? $validated['createUsername'] : null,
            'password' => filled($validated['createPassword']) ? $validated['createPassword'] : null,
            'account_reference' => filled($validated['createAccountReference']) ? $validated['createAccountReference'] : null,
            'registration_qa_notes' => filled($validated['createRegistrationQaNotes']) ? $validated['createRegistrationQaNotes'] : null,
            'general_notes' => filled($validated['createGeneralNotes']) ? $validated['createGeneralNotes'] : null,
            'notes' => filled($validated['createRegistrationQaNotes'])
                ? $validated['createRegistrationQaNotes']
                : (filled($validated['createGeneralNotes']) ? $validated['createGeneralNotes'] : null),
            'mfa_required' => (bool) $validated['createMfaRequired'],
            'mfa_method' => $validated['createMfaRequired'] ? $validated['createMfaMethod'] : 'none',
            'is_active' => (bool) $validated['createIsActive'],
            'visible_to_clinic' => (bool) $validated['createVisibleToClinic'],
        ]);

        Notification::make()
            ->success()
            ->title('Portal credential created')
            ->body('The new portal credential is now available for the selected clinic.')
            ->send();

        $this->closeCreatePortalCredentialModal();
    }

    public function openCredentialInfo(int $credentialId): void
    {
        $credential = $this->getScopedPortalCredentialQuery()->findOrFail($credentialId);

        $this->infoCredentialName = $credential->portal_name;
        $this->infoCredentialRegistrationQaNotes = $credential->registration_qa_notes ?: $credential->notes;
        $this->infoCredentialGeneralNotes = $credential->general_notes;
        $this->infoModalOpen = true;
        $this->resetErrorBag();
    }

    public function closeCredentialInfo(): void
    {
        $this->infoModalOpen = false;
        $this->infoCredentialName = null;
        $this->infoCredentialRegistrationQaNotes = null;
        $this->infoCredentialGeneralNotes = null;
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
            filled(AdminClinicScope::selectedClinicId()),
            fn ($query) => $query->where('clinic_id', AdminClinicScope::selectedClinicId()),
            fn ($query) => $query->whereRaw('1 = 0')
        );
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function resetCreatePortalCredentialForm(): void
    {
        $this->createPortalName = '';
        $this->createPortalCategory = 'insurance';
        $this->createLoginUrl = '';
        $this->createSupportContact = '';
        $this->createUsername = '';
        $this->createPassword = '';
        $this->createAccountReference = '';
        $this->createRegistrationQaNotes = '';
        $this->createGeneralNotes = '';
        $this->createMfaRequired = false;
        $this->createMfaMethod = 'none';
        $this->createIsActive = true;
        $this->createVisibleToClinic = false;
    }
}
