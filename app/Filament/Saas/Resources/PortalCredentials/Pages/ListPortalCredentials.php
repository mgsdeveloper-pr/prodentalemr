<?php

namespace App\Filament\Saas\Resources\PortalCredentials\Pages;

use App\Filament\Concerns\ManagesPortalCredentialSecurityQuestions;
use App\Filament\Saas\Resources\PortalCredentials\PortalCredentialResource;
use App\Models\AuditLog;
use App\Models\PortalCredential;
use App\Support\AdminClinicScope;
use App\Support\SaasSupportAccess;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListPortalCredentials extends ListRecords
{
    use ManagesPortalCredentialSecurityQuestions;

    protected static string $resource = PortalCredentialResource::class;

    protected string $view = 'filament.saas.resources.portal-credentials.pages.list-portal-credentials';

    public string $search = '';

    public string $statusFilter = 'all';

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

    public ?int $infoCredentialId = null;

    public ?string $infoCredentialCategory = null;

    public ?string $infoCredentialUsername = null;

    public ?string $infoCredentialPassword = null;

    public ?string $infoCredentialAuthentication = null;

    public ?string $infoCredentialAuthenticationDetail = null;

    public ?string $infoCredentialStatus = null;

    public ?string $infoCredentialUpdatedAt = null;

    public function getSelectedClinicName(): ?string
    {
        return AdminClinicScope::selectedClinic()?->clinic_name;
    }

    public function getPortalCredentials(): Collection
    {
        return $this->getScopedPortalCredentialQuery()
            ->withCount('securityQuestions')
            ->when(filled($this->search), function ($query): void {
                $query->where(function ($builder): void {
                    $builder
                        ->where('portal_name', 'like', '%'.$this->search.'%')
                        ->orWhere('login_url', 'like', '%'.$this->search.'%')
                        ->orWhere('portal_category', 'like', '%'.$this->search.'%')
                        ->orWhere('account_reference', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->statusFilter === 'attention', fn ($query) => $query->where(function ($builder): void {
                $builder
                    ->where('is_active', false)
                    ->orWhereNull('login_url')
                    ->orWhere('login_url', '')
                    ->orWhereNull('username')
                    ->orWhereNull('password');
            }))
            ->orderByDesc('is_active')
            ->orderBy('portal_name')
            ->get();
    }

    public function getCredentialSummary(): array
    {
        $credentials = $this->getScopedPortalCredentialQuery()
            ->withCount('securityQuestions')
            ->get();

        return [
            'total' => $credentials->count(),
            'active' => $credentials->where('is_active', true)->count(),
            'security_questions' => $credentials
                ->where('mfa_required', true)
                ->where('mfa_method', 'security_question')
                ->count(),
            'attention' => $credentials->filter(fn (PortalCredential $credential): bool => $this->credentialNeedsAttention($credential))->count(),
        ];
    }

    public function credentialNeedsAttention(PortalCredential $credential): bool
    {
        return ! $credential->is_active
            || blank($credential->login_url)
            || blank($credential->username)
            || blank($credential->password);
    }

    public function credentialStatusLabel(PortalCredential $credential): string
    {
        if (! $credential->is_active) {
            return 'Inactive';
        }

        return $this->credentialNeedsAttention($credential) ? 'Needs attention' : 'Active';
    }

    public function credentialAuthenticationLabel(PortalCredential $credential): string
    {
        if (! $credential->mfa_required) {
            return 'Password only';
        }

        return PortalCredential::MFA_METHOD_OPTIONS[$credential->mfa_method ?: 'none'] ?? 'Additional verification';
    }

    public function credentialAuthenticationDetail(PortalCredential $credential): string
    {
        if ($credential->mfa_required && $credential->mfa_method === 'security_question') {
            return $credential->security_questions_count.' configured';
        }

        return $credential->mfa_required ? 'Required at sign-in' : 'No additional step';
    }

    public function canUpdatePasswords(): bool
    {
        $clinic = AdminClinicScope::selectedClinic();

        if (! $clinic) {
            return false;
        }

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'update') ?? false;
        }

        return SaasSupportAccess::matchesScope((int) $clinic->organization_id, (int) $clinic->getKey())
            && (auth()->user()?->canPerformSaasModuleAction('portal_credentials', 'update') ?? false);
    }

    public function canCreatePortalCredentials(): bool
    {
        return PortalCredentialResource::canCreate();
    }

    public function createCredentialUrl(): string
    {
        return PortalCredentialResource::getUrl('create');
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
            'createPortalCategory' => ['required', 'string', 'in:'.implode(',', array_keys(PortalCredential::CATEGORY_OPTIONS))],
            'createLoginUrl' => ['nullable', 'url', 'max:255'],
            'createSupportContact' => ['nullable', 'string', 'max:255'],
            'createUsername' => ['nullable', 'string', 'max:255'],
            'createPassword' => ['nullable', 'string', 'max:255'],
            'createAccountReference' => ['nullable', 'string', 'max:255'],
            'createRegistrationQaNotes' => ['nullable', 'string'],
            'createGeneralNotes' => ['nullable', 'string'],
            'createMfaMethod' => ['required', 'string', 'in:'.implode(',', array_keys(PortalCredential::MFA_METHOD_OPTIONS))],
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
        $credential = $this->resolveAccessibleCredential($credentialId)->loadCount('securityQuestions');

        $this->infoCredentialId = $credential->getKey();
        $this->infoCredentialName = $credential->portal_name;
        $this->infoCredentialCategory = PortalCredential::CATEGORY_OPTIONS[$credential->portal_category ?: 'other'] ?? 'Other';
        $this->infoCredentialUsername = PortalCredential::maskSecret($credential->username);
        $this->infoCredentialPassword = PortalCredential::maskSecret($credential->password);
        $this->infoCredentialAuthentication = $this->credentialAuthenticationLabel($credential);
        $this->infoCredentialAuthenticationDetail = $this->credentialAuthenticationDetail($credential);
        $this->infoCredentialStatus = $this->credentialStatusLabel($credential);
        $this->infoCredentialUpdatedAt = optional($credential->updated_at)->format('M d, Y');
        $this->editingCredentialLink = $credential->login_url;
        $this->infoCredentialRegistrationQaNotes = $credential->registration_qa_notes ?: $credential->notes;
        $this->infoCredentialGeneralNotes = $credential->general_notes;
        $this->infoModalOpen = true;
        $this->resetErrorBag();
    }

    public function closeCredentialInfo(): void
    {
        $this->infoModalOpen = false;
        $this->infoCredentialId = null;
        $this->infoCredentialName = null;
        $this->infoCredentialCategory = null;
        $this->infoCredentialUsername = null;
        $this->infoCredentialPassword = null;
        $this->infoCredentialAuthentication = null;
        $this->infoCredentialAuthenticationDetail = null;
        $this->infoCredentialStatus = null;
        $this->infoCredentialUpdatedAt = null;
        $this->editingCredentialLink = null;
        $this->infoCredentialRegistrationQaNotes = null;
        $this->infoCredentialGeneralNotes = null;
    }

    public function setCredentialActive(int $credentialId, bool $active): void
    {
        $credential = $this->resolveAccessibleCredential($credentialId);
        $credential->update(['is_active' => $active]);

        Notification::make()
            ->success()
            ->title($active ? 'Credential activated' : 'Credential disabled')
            ->send();
    }

    public function openPasswordEditor(int $credentialId): void
    {
        abort_unless($this->canUpdatePasswords(), 403);

        $credential = $this->getScopedPortalCredentialQuery()->findOrFail($credentialId);
        abort_unless(PortalCredentialResource::canEdit($credential), 403);

        $this->editingCredentialId = $credential->getKey();
        $this->editingCredentialName = $credential->portal_name;
        $this->editingCredentialLink = $credential->login_url;
        $this->editingCredentialUsername = PortalCredential::maskSecret($credential->username);
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
        abort_unless(PortalCredentialResource::canEdit($credential), 403);

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

    public function revealCredentialSecret(int $credentialId, string $field): void
    {
        $credential = $this->resolveAccessibleCredential($credentialId);
        $this->guardSecretField($field);
        $this->recordSecretAccess($credential, $field, 'revealed');

        $this->revealPortalCredentialValue(
            "portal-{$field}-{$credential->getKey()}",
            (string) ($credential->{$field} ?? ''),
        );
    }

    public function copyCredentialSecret(int $credentialId, string $field): string
    {
        $credential = $this->resolveAccessibleCredential($credentialId);
        $this->guardSecretField($field);
        $this->recordSecretAccess($credential, $field, 'copied');

        Notification::make()
            ->success()
            ->title(ucfirst($field).' copied')
            ->send();

        return (string) ($credential->{$field} ?? '');
    }

    public function editCredentialUrl(PortalCredential $credential): string
    {
        return PortalCredentialResource::getUrl('edit', ['record' => $credential]);
    }

    protected function getScopedPortalCredentialQuery()
    {
        return PortalCredential::query()->when(
            filled(AdminClinicScope::selectedClinicId()),
            fn ($query) => $query->where('clinic_id', AdminClinicScope::selectedClinicId()),
            fn ($query) => $query->whereRaw('1 = 0')
        );
    }

    protected function resolveAccessibleCredential(int $credentialId): PortalCredential
    {
        abort_unless($this->canUpdatePasswords(), 403);

        $credential = $this->getScopedPortalCredentialQuery()->findOrFail($credentialId);
        abort_unless(PortalCredentialResource::canEdit($credential), 403);

        return $credential;
    }

    protected function guardSecretField(string $field): void
    {
        abort_unless(in_array($field, ['username', 'password'], true), 422);
    }

    protected function recordSecretAccess(PortalCredential $credential, string $field, string $action): void
    {
        AuditLog::query()->forceCreate([
            'user_id' => auth()->id(),
            'organization_id' => $credential->organization_id,
            'clinic_id' => $credential->clinic_id,
            'module' => 'portal_credentials',
            'action' => "{$field}_{$action}",
            'old_values' => null,
            'new_values' => json_encode([
                'portal_credential_id' => $credential->getKey(),
                'portal_name' => $credential->portal_name,
                'field' => $field,
                'access' => $action,
            ], JSON_THROW_ON_ERROR),
            'ip_address' => request()->ip(),
            'device_info' => request()->userAgent(),
        ]);
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
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
