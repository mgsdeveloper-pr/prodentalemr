<?php

namespace App\Filament\Saas\Resources\TelephonyCalls;

use App\Filament\Saas\Resources\TelephonyCalls\Pages\ListTelephonyCalls;
use App\Models\TelephonyCall;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TelephonyCallResource extends Resource
{
    protected static ?string $model = TelephonyCall::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Calling';

    protected static ?string $navigationLabel = 'Call Usage';

    protected static ?string $pluralModelLabel = 'call usage';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Call date')->dateTime()->sortable(),
                TextColumn::make('organization.name')->label('Client')->searchable()->sortable(),
                TextColumn::make('clinic.clinic_name')->label('Clinic')->searchable()->toggleable(),
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('to_number')->label('Called number')->searchable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->headline()->toString()),
                TextColumn::make('duration_seconds')->label('Duration')->formatStateUsing(fn (int $state): string => sprintf('%d:%02d', intdiv($state, 60), $state % 60)),
                TextColumn::make('provider')->formatStateUsing(fn (): string => 'MightyCall')->badge()->color('info'),
                TextColumn::make('ai_review_status')->label('AI review')->badge()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('organization')->relationship('organization', 'name')->searchable()->preload(),
                SelectFilter::make('status')->options([
                    'initiated' => 'Initiated',
                    'ringing' => 'Ringing',
                    'connected' => 'Connected',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('calling') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListTelephonyCalls::route('/')];
    }
}
