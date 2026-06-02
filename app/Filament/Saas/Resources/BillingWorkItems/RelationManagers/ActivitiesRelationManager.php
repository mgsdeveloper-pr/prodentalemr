<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Timeline';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('activity_type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('description')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
