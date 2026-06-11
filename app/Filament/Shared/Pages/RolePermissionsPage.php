<?php

namespace App\Filament\Shared\Pages;

use App\Support\PanelPermissionMatrix;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

abstract class RolePermissionsPage extends Page
{
    public ?string $selectedRole = null;

    public string $search = '';

    public array $matrix = [];

    public bool $showCreateRoleModal = false;

    public string $newRoleName = '';

    abstract protected static function panelKey(): string;

    abstract protected static function panelLabel(): string;

    public function mount(): void
    {
        $this->selectedRole = array_key_first($this->getRoleOptions());

        $this->loadPermissions();
    }

    public function updatedSelectedRole(): void
    {
        $this->loadPermissions();
    }

    public function getTitle(): string
    {
        return 'Roles & Permissions';
    }

    public function getPanelLabel(): string
    {
        return static::panelLabel();
    }

    public function getSubheading(): ?string
    {
        return 'Manage panel access by role with a module-by-module permission matrix.';
    }

    public function getRoleOptions(): array
    {
        return PanelPermissionMatrix::roles(static::panelKey());
    }

    public function canEditSelectedRole(): bool
    {
        return ! $this->isProtectedRole($this->selectedRole);
    }

    public function openCreateRoleModal(): void
    {
        $this->resetValidation();
        $this->newRoleName = '';
        $this->showCreateRoleModal = true;
    }

    public function closeCreateRoleModal(): void
    {
        $this->resetValidation();
        $this->newRoleName = '';
        $this->showCreateRoleModal = false;
    }

    public function getVisibleModulesProperty(): Collection
    {
        return collect(PanelPermissionMatrix::modules(static::panelKey()))
            ->filter(function (string $label): bool {
                if (blank($this->search)) {
                    return true;
                }

                return Str::of($label)->lower()->contains(Str::lower($this->search));
            });
    }

    public function getActionLabelsProperty(): array
    {
        return PanelPermissionMatrix::ACTIONS;
    }

    public function getNewRoleKeyPreviewProperty(): string
    {
        return $this->normalizedRoleName($this->newRoleName ?: 'new role');
    }

    public function createRole(): void
    {
        $this->validate([
            'newRoleName' => ['required', 'string', 'min:3', 'max:80'],
        ]);

        $roleName = $this->normalizedRoleName($this->newRoleName);

        if (array_key_exists($roleName, $this->getRoleOptions())) {
            $this->addError('newRoleName', 'A verification role with that name already exists.');

            return;
        }

        Role::query()->create([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $this->selectedRole = $roleName;
        $this->loadPermissions();
        $this->closeCreateRoleModal();

        Notification::make()
            ->title('Role created')
            ->body('The new role is ready. You can now set its permissions below.')
            ->success()
            ->send();
    }

    public function savePermissions(): void
    {
        if (blank($this->selectedRole)) {
            return;
        }

        if (! $this->canEditSelectedRole()) {
            Notification::make()
                ->title('Role protected')
                ->body('This role is locked and its permissions cannot be changed from this screen.')
                ->warning()
                ->send();

            $this->loadPermissions();

            return;
        }

        $role = Role::findByName($this->selectedRole, 'web');
        $panel = static::panelKey();
        $panelPermissionNames = PanelPermissionMatrix::permissionNamesForPanel($panel);

        $selectedPermissions = collect($this->matrix)
            ->flatMap(function (array $actions, string $module) use ($panel) {
                return collect($actions)
                    ->filter(fn (bool $enabled): bool => $enabled)
                    ->keys()
                    ->map(fn (string $action): string => PanelPermissionMatrix::permissionName($panel, $module, $action));
            })
            ->values()
            ->all();

        $preservedPermissions = $role->permissions
            ->pluck('name')
            ->reject(fn (string $name): bool => in_array($name, $panelPermissionNames, true))
            ->values()
            ->all();

        $role->syncPermissions([...$preservedPermissions, ...$selectedPermissions]);

        Notification::make()
            ->title('Permissions updated')
            ->body('Role permissions have been saved successfully.')
            ->success()
            ->send();

        $this->loadPermissions();
    }

    public function resetRolePermissions(): void
    {
        $this->loadPermissions();

        Notification::make()
            ->title('Changes discarded')
            ->body('Unsaved permission changes were reset.')
            ->success()
            ->send();
    }

    public function setAllForAction(string $action, bool $enabled): void
    {
        if (! $this->canEditSelectedRole()) {
            return;
        }

        foreach (array_keys(PanelPermissionMatrix::modules(static::panelKey())) as $module) {
            $this->matrix[$module][$action] = $enabled;
        }
    }

    public function setAllForModule(string $module, bool $enabled): void
    {
        if (! $this->canEditSelectedRole()) {
            return;
        }

        foreach (array_keys(PanelPermissionMatrix::ACTIONS) as $action) {
            $this->matrix[$module][$action] = $enabled;
        }
    }

    protected function loadPermissions(): void
    {
        $modules = PanelPermissionMatrix::modules(static::panelKey());
        $actions = array_keys(PanelPermissionMatrix::ACTIONS);

        $this->matrix = [];

        foreach (array_keys($modules) as $module) {
            $this->matrix[$module] = array_fill_keys($actions, false);
        }

        if (blank($this->selectedRole)) {
            return;
        }

        $role = Role::findByName($this->selectedRole, 'web');

        foreach (array_keys($modules) as $module) {
            foreach ($actions as $action) {
                $this->matrix[$module][$action] = $role->hasPermissionTo(
                    PanelPermissionMatrix::permissionName(static::panelKey(), $module, $action)
                );
            }
        }
    }

    protected function isProtectedRole(?string $role): bool
    {
        return match (static::panelKey()) {
            'saas' => $role === 'saas_admin',
            'verification' => $role === 'verification_admin',
            default => false,
        };
    }

    protected function normalizedRoleName(string $label): string
    {
        $prefix = static::panelKey() . '_';
        $slug = Str::of($label)
            ->trim()
            ->replaceMatches('/[^A-Za-z0-9]+/', ' ')
            ->slug('_')
            ->value();

        $slug = Str::of($slug)->ltrim('_')->value();

        if (blank($slug)) {
            return $prefix . 'role';
        }

        if (! Str::startsWith($slug, $prefix)) {
            $slug = $prefix . $slug;
        }

        return $slug;
    }
}
