<?php

namespace App\Filament\Saas\Resources\Users\Schemas;

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
                    ->description('Identity, access level, and recent activity for this SaaS user.')
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
                                    ->color('warning'),
                                IconEntry::make('status')
                                    ->label('Active')
                                    ->boolean(),
                                TextEntry::make('email')
                                    ->copyable()
                                    ->columnSpan(2),
                                TextEntry::make('phone')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('creator.name')
                                    ->label('Created by')
                                    ->placeholder('-'),
                                TextEntry::make('last_login_at')
                                    ->label('Last login')
                                    ->dateTime()
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
