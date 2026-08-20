<?php

namespace App\Filament\Clinic\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Overview')
                    ->description('Identity, role, and current account access.')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Full name')
                                    ->columnSpan(2),
                                TextEntry::make('primary_role')
                                    ->label('Role')
                                    ->state(fn (User $record): ?string => $record->getPrimaryRoleLabel())
                                    ->badge()
                                    ->color('info'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('email')
                                    ->copyable()
                                    ->columnSpan(2),
                                TextEntry::make('phone')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('last_login_at')
                                    ->label('Last login')
                                    ->dateTime('M d, Y h:i A')
                                    ->placeholder('Never'),
                            ]),
                    ]),
                Section::make('Workspace Assignment')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('organization.name')
                                    ->label('Organization')
                                    ->placeholder('-'),
                                TextEntry::make('clinic.clinic_name')
                                    ->label('Clinic')
                                    ->placeholder('-'),
                                TextEntry::make('location.location_name')
                                    ->label('Location')
                                    ->placeholder('All locations'),
                                TextEntry::make('creator.name')
                                    ->label('Created by')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
