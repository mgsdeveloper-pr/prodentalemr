<?php

namespace App\Filament\Saas\Resources\Users\Tables;

use App\Models\User;
use App\Support\SaasNotifications;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('primary_role')
                    ->label('Role')
                    ->state(fn (User $record): ?string => $record->getPrimaryRoleLabel())
                    ->badge(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Last login')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->after(function (User $record): void {
                        SaasNotifications::userDeleted($record->name, $record->email, auth()->user());
                    })
                    ->visible(fn (User $record): bool => ! $record->trashed() && $record->id !== auth()->id()),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
