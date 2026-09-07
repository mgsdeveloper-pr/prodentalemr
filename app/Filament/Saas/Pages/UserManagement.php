<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\TelephonyAccounts\TelephonyAccountResource;
use App\Filament\Saas\Resources\Users\UserResource;
use App\Models\TelephonyUserAssignment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\WithPagination;
use UnitEnum;

class UserManagement extends Page
{
    use WithPagination;

    protected static string|UnitEnum|null $navigationGroup = 'Administration';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Platform Users';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'User Management';

    protected static ?string $slug = 'user-management';

    protected string $view = 'filament.saas.pages.user-management';

    public string $search = '';

    public string $status = 'all';

    public function getSubheading(): ?string
    {
        return 'Manage platform users, access status, roles, and calling permissions.';
    }

    public function getBreadcrumbs(): array
    {
        return [static::getUrl() => 'User Management', 'List'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addUser')
                ->label('Add User')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url(UserResource::getUrl('create'))
                ->visible(fn (): bool => UserResource::canCreate()),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canAccessSaasModule('users') ?? false)
            || ($user?->hasRole('saas_admin') ?? false);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        if (! array_key_exists($this->status, $this->statusOptions())) {
            $this->status = 'all';
        }

        $this->resetPage();
    }

    public function getViewData(): array
    {
        $baseQuery = $this->baseUserQuery();

        return [
            'summary' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('status', true)->count(),
                'roles' => collect(array_keys(User::saasRoleOptions()))
                    ->filter(fn (string $role): bool => (clone $baseQuery)->role($role)->exists())
                    ->count(),
                'calling' => TelephonyUserAssignment::query()
                    ->where('is_active', true)
                    ->where('can_call', true)
                    ->whereIn('user_id', (clone $baseQuery)->select('users.id'))
                    ->distinct('user_id')
                    ->count('user_id'),
            ],
            'users' => $this->filteredUserQuery()->paginate(10),
            'statusOptions' => $this->statusOptions(),
            'createUrl' => UserResource::canCreate() ? UserResource::getUrl('create') : null,
            'rolesUrl' => RolesAndPermissions::canAccess() ? RolesAndPermissions::getUrl() : null,
            'callingUrl' => TelephonyAccountResource::canViewAny() ? TelephonyAccountResource::getUrl() : null,
        ];
    }

    public function initials(User $user): string
    {
        return collect(preg_split('/\s+/', trim($user->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: 'U';
    }

    public function accessScope(User $user): string
    {
        if ($user->hasRole('saas_admin')) {
            return 'All organizations';
        }

        return $user->organization?->name
            ?? $user->clinic?->clinic_name
            ?? 'Platform-wide';
    }

    public function statusLabel(User $user): string
    {
        if (! $user->status) {
            return 'Inactive';
        }

        return $user->email_verified_at === null ? 'Invited' : 'Active';
    }

    public function statusTone(User $user): string
    {
        return match ($this->statusLabel($user)) {
            'Active' => 'active',
            'Invited' => 'invited',
            default => 'inactive',
        };
    }

    public function userActionUrl(User $user): ?string
    {
        if (UserResource::canEdit($user)) {
            return UserResource::getUrl('edit', ['record' => $user]);
        }

        return UserResource::canView($user)
            ? UserResource::getUrl('view', ['record' => $user])
            : null;
    }

    public function canEditUser(User $user): bool
    {
        return UserResource::canEdit($user);
    }

    private function filteredUserQuery(): Builder
    {
        return $this->baseUserQuery()
            ->when(filled(trim($this->search)), function (Builder $query): void {
                $search = '%'.trim($this->search).'%';

                $query->where(fn (Builder $searchQuery): Builder => $searchQuery
                    ->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search));
            })
            ->when($this->status === 'active', fn (Builder $query): Builder => $query
                ->where('status', true)
                ->whereNotNull('email_verified_at'))
            ->when($this->status === 'invited', fn (Builder $query): Builder => $query
                ->where('status', true)
                ->whereNull('email_verified_at'))
            ->when($this->status === 'inactive', fn (Builder $query): Builder => $query->where('status', false))
            ->orderByDesc('status')
            ->orderBy('name');
    }

    private function baseUserQuery(): Builder
    {
        return UserResource::getEloquentQuery()
            ->whereNull('users.deleted_at')
            ->withCount([
                'telephonyAssignments as active_calling_assignments_count' => fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->where('can_call', true),
            ]);
    }

    private function statusOptions(): array
    {
        return [
            'all' => 'All statuses',
            'active' => 'Active',
            'invited' => 'Invited',
            'inactive' => 'Inactive',
        ];
    }
}
