<?php

namespace App\Filament\Clinic\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->copyable(),
                TextEntry::make('phone')
                    ->placeholder('-')
                    ->copyable(),
                TextEntry::make('primary_role')
                    ->label('Role')
                    ->state(fn (User $record): ?string => $record->getPrimaryRoleLabel())
                    ->badge(),
                TextEntry::make('organization.name')
                    ->label('Organization')
                    ->placeholder('-'),
                TextEntry::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->placeholder('-'),
                TextEntry::make('location.location_name')
                    ->label('Location')
                    ->placeholder('-'),
                TextEntry::make('creator.name')
                    ->label('Created by')
                    ->placeholder('-'),
                IconEntry::make('status')
                    ->label('Active')
                    ->boolean(),
                TextEntry::make('last_login_at')
                    ->label('Last login')
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->columns(2);
    }
}
