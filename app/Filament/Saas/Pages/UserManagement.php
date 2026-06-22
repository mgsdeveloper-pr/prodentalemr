<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\Users\UserResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class UserManagement extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'User Management';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = '';

    protected static ?string $slug = 'user-management';

    protected string $view = 'filament.saas.pages.user-management';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canAccessSaasModule('users') ?? false)
            || ($user?->hasRole('saas_admin') ?? false);
    }

    public function getCards(): array
    {
        return [
            [
                'title' => 'Users',
                'description' => 'Create, review, and manage SaaS user accounts.',
                'url' => UserResource::getUrl(),
                'icon' => 'users',
                'visible' => auth()->user()?->canAccessSaasModule('users') ?? false,
            ],
            [
                'title' => 'Roles & Permissions',
                'description' => 'Control module access and allowed actions by SaaS role.',
                'url' => RolesAndPermissions::getUrl(),
                'icon' => 'shield',
                'visible' => auth()->user()?->hasRole('saas_admin') ?? false,
            ],
        ];
    }
}
