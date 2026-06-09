<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationClinicAssignments extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Access Management';

    protected static ?string $navigationLabel = 'Assign Clinic';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = '';

    protected static ?string $slug = 'assign-clinic';

    protected string $view = 'filament.admin.pages.verification-clinic-assignments';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageVerificationUsers() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Clinic Access')
                    ->schema([
                        Select::make('user_id')
                            ->label('User')
                            ->options(fn (): array => $this->assignmentUserOptions())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state): void {
                                $user = filled($state) ? User::query()->find((int) $state) : null;

                                $set(
                                    'clinic_id',
                                    $user ? $this->managedServiceClinicIdsFor($user) : []
                                );
                            })
                            ->native(false),
                        Select::make('clinic_id')
                            ->label('Clinics')
                            ->options(fn (): array => auth()->user()?->assignableVerificationClinicOptions() ?? [])
                            ->searchable()
                            ->preload()
                            ->multiple()
                            ->helperText('Only clinics with active verification managed services are listed here.')
                            ->native(false),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $actor = auth()->user();
        $user = User::query()
            ->whereKey((int) $state['user_id'])
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['verification_manager', 'verification_user']))
            ->firstOrFail();
        $clinicIds = collect($state['clinic_id'] ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        abort_unless(UserResource::canManageRecord($user), 403);
        abort_unless($actor?->canAssignVerificationClinics($clinicIds), 403);

        $user->verificationClinics()->sync($clinicIds);

        Notification::make()
            ->title('Clinic access updated')
            ->body("{$user->name}'s clinic access was updated.")
            ->success()
            ->send();
    }

    public function closeUrl(): string
    {
        return UserResource::getUrl('index');
    }

    protected function assignmentUserOptions(): array
    {
        return UserResource::getEloquentQuery()
            ->get()
            ->filter(fn (User $user): bool => in_array($user->getPrimaryRoleName(), ['verification_manager', 'verification_user'], true))
            ->mapWithKeys(function (User $user): array {
                $role = $user->getPrimaryRoleLabel() ?: 'Verification User';

                return [
                    $user->getKey() => "{$user->name} ({$role})",
                ];
            })
            ->all();
    }

    protected function managedServiceClinicIdsFor(User $user): array
    {
        $allowedClinicIds = array_map('intval', array_keys(auth()->user()?->assignableVerificationClinicOptions() ?? []));

        return $user->verificationClinics()
            ->whereIn('clinics.id', $allowedClinicIds)
            ->whereHas('serviceEnrollments', function ($query): void {
                $query
                    ->where('status', 'active')
                    ->whereHas('managedBillingService', function ($serviceQuery): void {
                        $serviceQuery->where('category', 'verification');
                    });
            })
            ->pluck('clinics.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
