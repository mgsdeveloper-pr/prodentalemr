<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('assignClinic')
                ->label('Assign Clinic')
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->canManageVerificationUsers() ?? false)
                ->modalHeading('Assign Clinic')
                ->modalDescription('Select a user and clinic, then choose whether to assign or remove access.')
                ->modalSubmitActionLabel('Apply')
                ->modalCancelActionLabel('Close')
                ->form([
                    Select::make('user_id')
                        ->label('User')
                        ->options(fn (): array => $this->assignmentUserOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    Select::make('clinic_id')
                        ->label('Clinic')
                        ->options(fn (): array => auth()->user()?->assignableVerificationClinicOptions() ?? [])
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    Select::make('mode')
                        ->label('Action')
                        ->options([
                            'assign' => 'Select',
                            'remove' => 'Deselect',
                        ])
                        ->default('assign')
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $actor = auth()->user();
                    $user = User::query()
                        ->whereKey((int) $data['user_id'])
                        ->whereHas('roles', fn ($query) => $query
                            ->whereIn('name', array_keys(User::verificationRoleOptions()))
                            ->where('name', '!=', 'verification_admin'))
                        ->firstOrFail();

                    abort_unless(UserResource::canManageRecord($user), 403);
                    abort_unless($actor?->canAssignVerificationClinics([(int) $data['clinic_id']]), 403);

                    if (($data['mode'] ?? 'assign') === 'remove') {
                        $user->verificationClinics()->detach((int) $data['clinic_id']);

                        Notification::make()
                            ->title('Clinic access removed')
                            ->body("{$user->name} no longer has access to the selected clinic.")
                            ->success()
                            ->send();

                        return;
                    }

                    $user->verificationClinics()->syncWithoutDetaching([(int) $data['clinic_id']]);

                    Notification::make()
                        ->title('Clinic assigned')
                        ->body("{$user->name} now has access to the selected clinic.")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function assignmentUserOptions(): array
    {
        return UserResource::getEloquentQuery()
            ->get()
            ->filter(fn (User $user): bool => User::isVerificationRole($user->getPrimaryRoleName()) && $user->getPrimaryRoleName() !== 'verification_admin')
            ->mapWithKeys(function (User $user): array {
                $role = $user->getPrimaryRoleLabel() ?: 'Verification User';

                return [
                    $user->getKey() => "{$user->name} ({$role})",
                ];
            })
            ->all();
    }
}
