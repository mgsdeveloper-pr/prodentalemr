<?php

namespace App\Filament\Clinic\Resources\PortalCredentials;

use App\Filament\Clinic\Resources\PortalCredentials\Pages\EditPortalCredential;
use App\Filament\Clinic\Resources\PortalCredentials\Pages\ListPortalCredentials;
use App\Models\PortalCredential;
use App\Support\ClinicPanelScope;
use App\Support\VerificationManagedServiceAccess;
use BackedEnum;
use Filament\Forms\Components\Placeholder;
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

class PortalCredentialResource extends Resource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $model = PortalCredential::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Portal Credentials';

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Clinic Override')
                    ->description('Edit the portal credential assigned to your clinic. Other clinics will not see this credential.')
                    ->schema([
                        Placeholder::make('clinic_scope')
                            ->label('Clinic scope')
                            ->content(fn (): string => ClinicPanelScope::selectedClinic()?->clinic_name ?: 'Select a clinic from the Workspace menu first.'),
                        Grid::make(12)
                            ->schema([
                                TextInput::make('portal_name')
                                    ->label('Portal Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(4),
                                Select::make('portal_category')
                                    ->label('Category')
                                    ->options(PortalCredential::CATEGORY_OPTIONS)
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(4),
                                Placeholder::make('credential_scope_gap')
                                    ->hiddenLabel()
                                    ->content('')
                                    ->columnSpan(4),
                                TextInput::make('login_url')
                                    ->label('Login URL')
                                    ->url()
                                    ->maxLength(255)
                                    ->columnSpan(6),
                                TextInput::make('support_contact')
                                    ->label('Support Contact')
                                    ->maxLength(255)
                                    ->columnSpan(6),
                                TextInput::make('username')
                                    ->label('Username')
                                    ->maxLength(255)
                                    ->columnSpan(6),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255)
                                    ->columnSpan(6),
                                Toggle::make('mfa_required')
                                    ->label('MFA Required')
                                    ->inline(false)
                                    ->live()
                                    ->columnSpan(3),
                                Select::make('mfa_method')
                                    ->label('MFA Method')
                                    ->options(PortalCredential::MFA_METHOD_OPTIONS)
                                    ->native(false)
                                    ->default('none')
                                    ->visible(fn ($get): bool => (bool) $get('mfa_required'))
                                    ->columnSpan(3),
                                Toggle::make('is_active')
                                    ->label('Active for this clinic')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(3),
                                Textarea::make('notes')
                                    ->label('Notes')
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
                ->orderByDesc('is_active')
                ->orderBy('portal_name'))
            ->columns([
                TextColumn::make('portal_name')
                    ->label('Portal')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('portal_category')
                    ->label('Category')
                    ->badge()
                    ->state(fn (PortalCredential $record): string => PortalCredential::CATEGORY_OPTIONS[$record->portal_category ?: 'other'] ?? 'Other'),
                TextColumn::make('login_url')
                    ->label('Login URL')
                    ->state(fn (PortalCredential $record): string => $record->login_url ?: '-')
                    ->limit(36)
                    ->toggleable(),
                TextColumn::make('username')
                    ->label('Username')
                    ->state(fn (PortalCredential $record): string => PortalCredential::maskSecret($record->username))
                    ->toggleable(),
                IconColumn::make('mfa_required')
                    ->label('MFA')
                    ->state(fn (PortalCredential $record): bool => (bool) $record->mfa_required)
                    ->boolean(),
                IconColumn::make('effective_status')
                    ->label('Active')
                    ->state(fn (PortalCredential $record): bool => (bool) $record->is_active)
                    ->boolean(),
            ])
            ->recordActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $selectedClinicId = ClinicPanelScope::selectedClinicId();

        return parent::getEloquentQuery()
            ->when(
                filled($selectedClinicId),
                fn (Builder $query) => $query->where('clinic_id', $selectedClinicId),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            );
    }

    public static function canViewAny(): bool
    {
        return VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()
            && (auth()->user()?->canAccessClinicModule('portal_credentials') ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return VerificationManagedServiceAccess::selectedClinicHasActiveVerificationService()
            && filled(ClinicPanelScope::selectedClinicId())
            && (auth()->user()?->canPerformClinicModuleAction('portal_credentials', 'update') ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortalCredentials::route('/'),
            'edit' => EditPortalCredential::route('/{record}/edit'),
        ];
    }
}
