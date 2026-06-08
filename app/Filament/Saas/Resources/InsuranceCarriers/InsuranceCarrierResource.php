<?php

namespace App\Filament\Saas\Resources\InsuranceCarriers;

use App\Filament\Saas\Resources\InsuranceCarriers\Pages\CreateInsuranceCarrier;
use App\Filament\Saas\Resources\InsuranceCarriers\Pages\EditInsuranceCarrier;
use App\Filament\Saas\Resources\InsuranceCarriers\Pages\ListInsuranceCarriers;
use App\Models\InsuranceCarrier;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class InsuranceCarrierResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = InsuranceCarrier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Insurance Directory';

    protected static string|UnitEnum|null $navigationGroup = 'Verification Workspace';

    public static function getModelLabel(): string
    {
        return 'insurance directory entry';
    }

    public static function getPluralModelLabel(): string
    {
        return 'insurance directory';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Carrier Details')
                    ->description('Maintain the global insurance carrier master that every clinic can inherit from by default.')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                TextInput::make('insurance_name')
                                    ->label('Insurance Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(6),
                                TextInput::make('payer_id')
                                    ->label('Payer ID')
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                TextInput::make('payer_phone')
                                    ->label('Payer Phone')
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                Textarea::make('claims_address')
                                    ->label('Claims Address')
                                    ->rows(3)
                                    ->columnSpan(6),
                                TextInput::make('website')
                                    ->label('Website')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpan(6),
                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(4)
                                    ->columnSpan(12),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(3),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount('overrides')
                ->orderByDesc('is_active')
                ->orderBy('insurance_name'))
            ->columns([
                TextColumn::make('insurance_name')
                    ->label('Insurance')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('payer_id')
                    ->label('Payer ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('payer_phone')
                    ->label('Phone')
                    ->toggleable(),
                TextColumn::make('overrides_count')
                    ->label('Clinic Overrides')
                    ->badge()
                    ->color(fn (?string $state): string => ((int) $state) > 0 ? 'warning' : 'gray')
                    ->alignCenter()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->alignCenter()
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete'),
            ]);
    }

    public static function canViewAny(): bool
    {
        return (bool) (
            auth()->user()?->canAccessVerificationModule('insurance_directory')
            || auth()->user()?->canAccessSaasModule('insurance_directory')
        );
    }

    public static function canCreate(): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('insurance_directory', 'add')
            || auth()->user()?->canPerformSaasModuleAction('insurance_directory', 'add')
        );
    }

    public static function canEdit(Model $record): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('insurance_directory', 'update')
            || auth()->user()?->canPerformSaasModuleAction('insurance_directory', 'update')
        );
    }

    public static function canDelete(Model $record): bool
    {
        return (bool) (
            auth()->user()?->canPerformVerificationModuleAction('insurance_directory', 'delete')
            || auth()->user()?->canPerformSaasModuleAction('insurance_directory', 'delete')
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsuranceCarriers::route('/'),
            'create' => CreateInsuranceCarrier::route('/create'),
            'edit' => EditInsuranceCarrier::route('/{record}/edit'),
        ];
    }
}
