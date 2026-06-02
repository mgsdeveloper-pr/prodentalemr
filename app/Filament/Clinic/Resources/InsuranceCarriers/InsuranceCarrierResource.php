<?php

namespace App\Filament\Clinic\Resources\InsuranceCarriers;

use App\Filament\Clinic\Resources\InsuranceCarriers\Pages\EditInsuranceCarrier;
use App\Filament\Clinic\Resources\InsuranceCarriers\Pages\ListInsuranceCarriers;
use App\Models\InsuranceCarrier;
use App\Support\ClinicPanelScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
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

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Clinic Override')
                    ->description('Edit the values your clinic wants to override. Any field you leave alone will continue to inherit the global master value.')
                    ->schema([
                        Placeholder::make('clinic_scope')
                            ->label('Clinic scope')
                            ->content(fn (): string => ClinicPanelScope::selectedClinic()?->clinic_name ?: 'Select a clinic from the Workspace menu first.'),
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
                                    ->label('Active for this clinic')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(3),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $selectedClinicId = ClinicPanelScope::selectedClinicId();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['overrides' => fn ($overrideQuery) => $overrideQuery->when(
                    filled($selectedClinicId),
                    fn ($builder) => $builder->where('clinic_id', $selectedClinicId),
                    fn ($builder) => $builder->whereRaw('1 = 0')
                )])
                ->orderByDesc('is_active')
                ->orderBy('insurance_name'))
            ->columns([
                TextColumn::make('insurance_name')
                    ->label('Insurance')
                    ->state(fn (InsuranceCarrier $record): string => $record->effectiveAttributesForClinic($selectedClinicId)['insurance_name'])
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('payer_id')
                    ->label('Payer ID')
                    ->state(fn (InsuranceCarrier $record): string => $record->effectiveAttributesForClinic($selectedClinicId)['payer_id'] ?: '-')
                    ->toggleable(),
                TextColumn::make('payer_phone')
                    ->label('Phone')
                    ->state(fn (InsuranceCarrier $record): string => $record->effectiveAttributesForClinic($selectedClinicId)['payer_phone'] ?: '-')
                    ->toggleable(),
                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (InsuranceCarrier $record): string => $record->effectiveAttributesForClinic($selectedClinicId)['has_override'] ? 'warning' : 'gray')
                    ->state(fn (InsuranceCarrier $record): string => $record->effectiveAttributesForClinic($selectedClinicId)['has_override'] ? 'Clinic Override' : 'Global Master'),
                IconColumn::make('effective_status')
                    ->label('Active')
                    ->state(fn (InsuranceCarrier $record): bool => (bool) $record->effectiveAttributesForClinic($selectedClinicId)['is_active'])
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit Clinic Version'),
            ]);
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->canManageClinicVerificationSettings() ?? false)
            && (auth()->user()?->canAccessClinicModule('insurance_directory') ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return filled(ClinicPanelScope::selectedClinicId())
            && (auth()->user()?->canManageClinicVerificationSettings() ?? false)
            && (auth()->user()?->canPerformClinicModuleAction('insurance_directory', 'update') ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInsuranceCarriers::route('/'),
            'edit' => EditInsuranceCarrier::route('/{record}/edit'),
        ];
    }
}
