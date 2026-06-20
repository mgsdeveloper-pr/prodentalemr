<?php

namespace App\Filament\Dso\Pages;

use App\Models\User;
use App\Support\PanelPermissionMatrix;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class Users extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected static ?string $slug = 'users';

    protected string $view = 'filament.dso.pages.users';

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public string $role = 'dso_viewer';

    public string $password = '';

    public string $password_confirmation = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canAccessDsoWorkspace() ?? false)
            && $user->hasPermissionTo(PanelPermissionMatrix::permissionName('dso', 'users', 'view'));
    }

    public function getUsers(): Collection
    {
        return User::query()
            ->with('roles')
            ->where('dso_id', auth()->user()?->dso_id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', array_keys(User::dsoRoleOptions())))
            ->orderBy('name')
            ->get();
    }

    public function getRoleOptions(): array
    {
        if (auth()->user()?->hasRole('dso_admin')) {
            return User::dsoRoleOptions();
        }

        return collect(User::dsoRoleOptions())
            ->except('dso_admin')
            ->all();
    }

    public function canCreateUsers(): bool
    {
        $user = auth()->user();

        return ($user?->canAccessDsoWorkspace() ?? false)
            && $user->hasPermissionTo(PanelPermissionMatrix::permissionName('dso', 'users', 'add'));
    }

    public function createUser(): void
    {
        if (! $this->canCreateUsers()) {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:' . implode(',', array_keys($this->getRoleOptions()))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'dso_id' => auth()->user()->dso_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'created_by' => auth()->id(),
            'status' => true,
            'password' => Hash::make($this->password),
            'allowed_workspaces' => ['dso'],
            'default_workspace' => 'dso',
        ]);

        $user->assignRole($this->role);

        $this->reset(['name', 'email', 'phone', 'password', 'password_confirmation']);
        $this->role = 'dso_viewer';

        Notification::make()
            ->title('DSO user invited')
            ->body("{$user->name} can now access the DSO workspace.")
            ->success()
            ->send();
    }
}
