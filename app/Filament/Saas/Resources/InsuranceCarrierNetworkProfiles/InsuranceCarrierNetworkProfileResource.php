<?php

namespace App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles;

use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\Pages\CreateInsuranceCarrierNetworkProfile;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\Pages\EditInsuranceCarrierNetworkProfile;
use App\Filament\Saas\Resources\InsuranceCarrierNetworkProfiles\Pages\ListInsuranceCarrierNetworkProfiles;
use App\Models\InsuranceCarrier;
use App\Models\InsuranceCarrierNetworkProfile;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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

class InsuranceCarrierNetworkProfileResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = InsuranceCarrierNetworkProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Provider Participation';

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Carrier Link')
                    ->description('Attach one participation profile to one insurance carrier from the shared master.')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('insurance_carrier_id')
                                    ->label('Insurance Carrier')
                                    ->options(fn (): array => InsuranceCarrier::query()
                                        ->orderBy('insurance_name')
                                        ->pluck('insurance_name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(8),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(4),
                            ]),
                    ]),
                Section::make('Provider Participation Guidance')
                    ->description('Capture how this payer typically treats participating and non-participating providers so verifiers can reference it during eligibility review.')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Textarea::make('participating_provider_summary')
                                    ->label('Participating Provider Summary')
                                    ->placeholder('Example: PPO-contracted offices are paid on the negotiated fee schedule and cannot bill above the allowed amount for covered services.')
                                    ->rows(4)
                                    ->columnSpan(6),
                                Textarea::make('non_participating_provider_summary')
                                    ->label('Non-Participating Provider Summary')
                                    ->placeholder('Example: Out-of-network offices are reimbursed on plan allowance and patient may owe the balance above plan payment.')
                                    ->rows(4)
                                    ->columnSpan(6),
                                Select::make('out_of_network_coverage')
                                    ->label('Out-of-Network Coverage')
                                    ->options(InsuranceCarrierNetworkProfile::OUT_OF_NETWORK_OPTIONS)
                                    ->native(false)
                                    ->columnSpan(4),
                                Textarea::make('assignment_of_benefits')
                                    ->label('Assignment of Benefits')
                                    ->placeholder('Example: Assignment accepted for participating providers only; non-participating payment may be sent to member.')
                                    ->rows(3)
                                    ->columnSpan(4),
                                Select::make('reimbursement_destination')
                                    ->label('Payment Destination')
                                    ->options(InsuranceCarrierNetworkProfile::REIMBURSEMENT_DESTINATION_OPTIONS)
                                    ->native(false)
                                    ->columnSpan(4),
                                Textarea::make('participating_reimbursement_basis')
                                    ->label('Participating Reimbursement Basis')
                                    ->placeholder('Example: PPO fee schedule / contracted allowable.')
                                    ->rows(3)
                                    ->columnSpan(6),
                                Textarea::make('non_participating_reimbursement_basis')
                                    ->label('Non-Participating Reimbursement Basis')
                                    ->placeholder('Example: UCR / MAC / plan allowance based reimbursement.')
                                    ->rows(3)
                                    ->columnSpan(6),
                                Textarea::make('balance_billing_note')
                                    ->label('Patient Responsibility / Balance Billing Note')
                                    ->placeholder('Example: Member may owe the difference between office fee and out-of-network allowed amount.')
                                    ->rows(3)
                                    ->columnSpan(6),
                                Textarea::make('specialist_rule_notes')
                                    ->label('Specialist / General Dentist Rules')
                                    ->placeholder('Example: Specialist benefits differ from GP benefits; verify specialty network separately.')
                                    ->rows(3)
                                    ->columnSpan(6),
                                TextInput::make('fee_schedule_reference_name')
                                    ->label('Fee Schedule Reference Name')
                                    ->placeholder('Example: Aetna PPO contracted fee schedule 2026')
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                FileUpload::make('fee_schedule_reference_file_path')
                                    ->label('Fee Schedule PDF')
                                    ->disk('public')
                                    ->directory('verification/fee-schedules')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->downloadable()
                                    ->openable()
                                    ->columnSpan(4),
                                TextInput::make('fee_schedule_reference_external_url')
                                    ->label('External Fee Schedule Link')
                                    ->url()
                                    ->placeholder('https://...')
                                    ->columnSpan(4),
                                Textarea::make('verification_tips')
                                    ->label('Verification Tips')
                                    ->placeholder('Example: Always verify plan-level network, reimbursement basis, and whether payment is sent to provider or patient.')
                                    ->rows(4)
                                    ->columnSpan(12),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('insuranceCarrier')
                ->orderByDesc('is_active')
                ->orderBy(
                    InsuranceCarrier::query()
                        ->select('insurance_name')
                        ->whereColumn('insurance_carriers.id', 'insurance_carrier_network_profiles.insurance_carrier_id')
                        ->limit(1)
                ))
            ->columns([
                TextColumn::make('insuranceCarrier.insurance_name')
                    ->label('Insurance')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('insuranceCarrier.payer_id')
                    ->label('Payer ID')
                    ->toggleable(),
                TextColumn::make('out_of_network_coverage')
                    ->label('Out-of-Network')
                    ->formatStateUsing(fn (?string $state): string => InsuranceCarrierNetworkProfile::OUT_OF_NETWORK_OPTIONS[$state] ?? ($state ?: '-'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'yes' => 'success',
                        'limited' => 'warning',
                        'no' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('reimbursement_destination')
                    ->label('Payment Destination')
                    ->formatStateUsing(fn (?string $state): string => InsuranceCarrierNetworkProfile::REIMBURSEMENT_DESTINATION_OPTIONS[$state] ?? ($state ?: '-'))
                    ->wrap(),
                TextColumn::make('fee_schedule_reference_name')
                    ->label('Fee Schedule Ref')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(),
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
            'index' => ListInsuranceCarrierNetworkProfiles::route('/'),
            'create' => CreateInsuranceCarrierNetworkProfile::route('/create'),
            'edit' => EditInsuranceCarrierNetworkProfile::route('/{record}/edit'),
        ];
    }
}
