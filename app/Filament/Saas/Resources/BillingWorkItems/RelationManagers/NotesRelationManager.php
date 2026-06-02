<?php

namespace App\Filament\Saas\Resources\BillingWorkItems\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
                Select::make('visibility')
                    ->options([
                        'internal' => 'Internal',
                        'client_visible' => 'Client Visible',
                    ])
                    ->default('internal')
                    ->native(false)
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->placeholder('-'),
                TextColumn::make('visibility')
                    ->badge(),
                TextColumn::make('body')
                    ->limit(90)
                    ->wrap(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
