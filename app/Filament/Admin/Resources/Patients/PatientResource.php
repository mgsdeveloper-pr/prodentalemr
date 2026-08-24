<?php

namespace App\Filament\Admin\Resources\Patients;

use App\Filament\Admin\Resources\Patients\Pages\CreatePatient;
use App\Filament\Admin\Resources\Patients\Pages\EditPatient;
use App\Filament\Admin\Resources\Patients\Pages\ListPatients;
use App\Filament\Admin\Resources\Patients\Pages\ViewPatient;
use App\Filament\Clinic\Resources\Patients\Schemas\PatientInfolist;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\Patient;
use App\Support\AdminClinicScope;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PatientResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Patient Manager';

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $slug = 'patient-manager';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
                Section::make('Patient Identity')
                    ->description('General patient data used by verification workflows and future patient insights.')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('clinic_id')
                                    ->label('Clinic')
                                    ->options(fn (): array => AdminClinicScope::clinicOptions())
                                    ->default(fn (): ?int => AdminClinicScope::selectedClinicId())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->native(false)
                                    ->afterStateUpdated(function ($state, $set): void {
                                        $organizationId = filled($state)
                                            ? Clinic::query()->whereKey($state)->value('organization_id')
                                            : null;

                                        $set('organization_id', $organizationId);
                                        $set('location_id', null);
                                    })
                                    ->columnSpan(4),
                                Hidden::make('organization_id')
                                    ->default(fn (): ?int => AdminClinicScope::selectedClinic()?->organization_id),
                                Select::make('location_id')
                                    ->label('Primary location')
                                    ->options(fn (Get $get): array => Location::query()
                                        ->when($get('clinic_id'), fn (Builder $query, $clinicId) => $query->where('clinic_id', $clinicId))
                                        ->orderBy('location_name')
                                        ->pluck('location_name', 'id')
                                        ->all())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpan(4),
                                TextInput::make('pms_patient_id')
                                    ->label('PMS Patient ID')
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('first_name')
                                    ->label('First name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('last_name')
                                    ->label('Last name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                DatePicker::make('dob')
                                    ->label('Date of birth')
                                    ->native(false)
                                    ->maxDate(now())
                                    ->columnSpan(4),
                                Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                        'non_binary' => 'Non-binary',
                                        'prefer_not_to_say' => 'Prefer not to say',
                                    ])
                                    ->native(false)
                                    ->searchable()
                                    ->columnSpan(4),
                                Toggle::make('status')
                                    ->label('Active patient')
                                    ->default(true)
                                    ->required()
                                    ->columnSpan(4),
                            ]),
                    ]),
                Section::make('Contact & Coverage')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('insurance_provider')
                                    ->label('Insurance provider')
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('insurance_number')
                                    ->label('Insurance number')
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                TextInput::make('guarantor_name')
                                    ->label('Guarantor name')
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                Textarea::make('address')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PatientInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Patient')
                    ->state(fn (Patient $record): string => $record->full_name)
                    ->searchable(query: function (Builder $query, string $search): void {
                        $query->where(function (Builder $innerQuery) use ($search): void {
                            $innerQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('pms_patient_id', 'like', "%{$search}%")
                                ->orWhere('insurance_number', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->orderBy('last_name', $direction)
                        ->orderBy('first_name', $direction)),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('dob')
                    ->label('DOB')
                    ->date('M d, Y')
                    ->sortable(),
                TextColumn::make('insurance_provider')
                    ->label('Insurance')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('billing_work_items_count')
                    ->label('Requests')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('open_verifications_count')
                    ->label('Open')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                IconColumn::make('status')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('clinic_id')
                    ->label('Clinic')
                    ->options(fn (): array => AdminClinicScope::clinicOptions())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(fn (): array => Location::query()
                        ->whereIn('clinic_id', array_keys(AdminClinicScope::clinicOptions()))
                        ->orderBy('location_name')
                        ->pluck('location_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('last_name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (): bool => static::canCreate()),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return AdminClinicScope::apply(parent::getEloquentQuery(), 'clinic_id')
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['organization', 'clinic', 'location', 'creator'])
            ->withCount([
                'billingWorkItems',
                'billingWorkItems as open_verifications_count' => fn (Builder $query) => $query
                    ->whereIn('status', [
                        BillingWorkItem::STATUS_PENDING,
                        BillingWorkItem::STATUS_IN_PROGRESS,
                        BillingWorkItem::STATUS_REVIEW,
                        BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
                        BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                        BillingWorkItem::STATUS_INCOMPLETE,
                    ]),
                'billingWorkItems as completed_verifications_count' => fn (Builder $query) => $query
                    ->where('status', BillingWorkItem::STATUS_DONE),
            ])
            ->withSum('ledgerEntries as ledger_debit_total', 'debit_amount')
            ->withSum('ledgerEntries as ledger_credit_total', 'credit_amount');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessVerificationWorkspace() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canManageVerificationQueue() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view' => ViewPatient::route('/{record}'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
}
