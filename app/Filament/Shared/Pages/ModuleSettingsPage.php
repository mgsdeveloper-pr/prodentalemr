<?php

namespace App\Filament\Shared\Pages;

use App\Support\PanelPermissionMatrix;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

abstract class ModuleSettingsPage extends Page
{
    public ?string $selectedRole = null;

    public string $search = '';

    public array $modules = [];

    abstract protected static function panelKey(): string;

    public function mount(): void
    {
        $this->selectedRole = array_key_first($this->getRoleOptions());

        $this->loadModules();
    }

    public function updatedSelectedRole(): void
    {
        $this->loadModules();
    }

    public function getTitle(): string
    {
        return 'Module Settings';
    }

    public function getSubheading(): ?string
    {
        return 'Choose which modules each role is allowed to see inside this panel.';
    }

    public function getRoleOptions(): array
    {
        return PanelPermissionMatrix::roles(static::panelKey());
    }

    public function canEditSelectedRole(): bool
    {
        return ! $this->isProtectedRole($this->selectedRole);
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

    public function saveModuleSettings(): void
    {
        if (blank($this->selectedRole)) {
            return;
        }

        if (! $this->canEditSelectedRole()) {
            Notification::make()
                ->title('Role protected')
                ->body('This role is locked and its module visibility cannot be changed from this screen.')
                ->warning()
                ->send();

            $this->loadModules();

            return;
        }

        $role = Role::findByName($this->selectedRole, 'web');
        $panel = static::panelKey();
        $modulePermissionNames = collect(array_keys(PanelPermissionMatrix::modules($panel)))
            ->map(fn (string $module): string => PanelPermissionMatrix::permissionName($panel, $module, 'view'))
            ->all();

        $selectedPermissions = collect($this->modules)
            ->filter(fn (bool $enabled): bool => $enabled)
            ->keys()
            ->map(fn (string $module): string => PanelPermissionMatrix::permissionName($panel, $module, 'view'))
            ->values()
            ->all();

        $preservedPermissions = $role->permissions
            ->pluck('name')
            ->reject(fn (string $name): bool => in_array($name, $modulePermissionNames, true))
            ->values()
            ->all();

        $role->syncPermissions([...$preservedPermissions, ...$selectedPermissions]);

        Notification::make()
            ->title('Module settings saved')
            ->body('Module visibility has been updated for the selected role.')
            ->success()
            ->send();

        $this->loadModules();
    }

    public function resetModuleSettings(): void
    {
        $this->loadModules();

        Notification::make()
            ->title('Changes discarded')
            ->body('Unsaved module changes were reset.')
            ->success()
            ->send();
    }

    public function setAllModules(bool $enabled): void
    {
        if (! $this->canEditSelectedRole()) {
            return;
        }

        foreach (array_keys(PanelPermissionMatrix::modules(static::panelKey())) as $module) {
            $this->modules[$module] = $enabled;
        }
    }

    protected function loadModules(): void
    {
        $this->modules = array_fill_keys(array_keys(PanelPermissionMatrix::modules(static::panelKey())), false);

        if (blank($this->selectedRole)) {
            return;
        }

        $role = Role::findByName($this->selectedRole, 'web');

        foreach (array_keys($this->modules) as $module) {
            $this->modules[$module] = $role->hasPermissionTo(
                PanelPermissionMatrix::permissionName(static::panelKey(), $module, 'view')
            );
        }
    }

    protected function isProtectedRole(?string $role): bool
    {
        return match (static::panelKey()) {
            'saas' => $role === 'saas_admin',
            'dso' => $role === 'dso_admin',
            default => false,
        };
    }
}
