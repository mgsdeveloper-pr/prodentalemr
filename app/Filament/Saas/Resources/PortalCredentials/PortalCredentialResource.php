<?php

namespace App\Filament\Saas\Resources\PortalCredentials;

use App\Filament\Saas\Resources\PortalCredentials\Pages\CreatePortalCredential;
use App\Filament\Saas\Resources\PortalCredentials\Pages\EditPortalCredential;
use App\Filament\Saas\Resources\PortalCredentials\Pages\ListPortalCredentials;
use App\Models\PortalCredential;
use App\Support\AdminClinicScope;
use App\Support\SaasSupportAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return in_array(Filament::getCurrentPanel()?->getId(), ['saas', 'admin'], true);
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return Filament::getCurrentPanel()?->getId() === 'admin'
            ? 'Resources'
            : 'Verifications';
    }

    public static function getNavigationSort(): ?int
    {
        return Filament::getCurrentPanel()?->getId() === 'admin' ? 2 : 4;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Credential Details')
                    ->description('Maintain the portal credentials assigned to the selected clinic. Other clinics will not see these credentials.')
                    ->schema([
                        Placeholder::make('clinic_scope')
                            ->label('Clinic Scope')
                            ->content(fn (): string => AdminClinicScope::selectedClinic()?->clinic_name ?: 'Select a clinic from the Workspace menu first.'),
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
                                    ->default('insurance')
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
                                    ->live()
                                    ->default('none')
                                    ->visible(fn ($get): bool => (bool) $get('mfa_required'))
                                    ->columnSpan(3),
                                Repeater::make('securityQuestions')
                                    ->relationship()
                                    ->label('Security Questions & Answers')
                                    ->helperText('Add each portal challenge exactly as shown. Questions and answers are encrypted and only revealed through audited actions.')
                                    ->visible(fn ($get): bool => (bool) $get('mfa_required') && $get('mfa_method') === 'security_question')
                                    ->required(fn ($get): bool => (bool) $get('mfa_required') && $get('mfa_method') === 'security_question')
                                    ->minItems(1)
                                    ->orderColumn('sort_order')
                                    ->addActionLabel('Add security question')
                                    ->columns(12)
                                    ->schema([
                                        TextInput::make('question')
                                            ->label('Security Question')
                                            ->required()
                                            ->maxLength(500)
                                            ->columnSpan(6),
                                        TextInput::make('answer')
                                            ->label('Protected Answer')
                                            ->password()
                                            ->revealable()
                                            ->required()
                                            ->maxLength(500)
                                            ->columnSpan(4),
                                        Toggle::make('is_required')
                                            ->label('Required')
                                            ->helperText('Mark when the portal asks this question during sign-in.')
                                            ->default(true)
                                            ->inline(false)
                                            ->columnSpan(2),
                                    ])
                                    ->columnSpan(12),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpan(3),
                                Toggle::make('visible_to_clinic')
                                    ->label('Visible in clinic panel')
                                    ->helperText('Enable this only when clinic users should be able to see this credential in their portal workspace.')
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(6),
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
                ->withCount('overrides')
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
                    ->formatStateUsing(fn (?string $state): string => PortalCredential::CATEGORY_OPTIONS[$state ?? 'other'] ?? 'Other'),
                TextColumn::make('login_url')
                    ->label('Login URL')
                    ->limit(36)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('username')
                    ->label('Username')
                    ->state(fn (PortalCredential $record): string => PortalCredential::maskSecret($record->username))
                    ->toggleable(),
                TextColumn::make('account_reference')
                    ->label('Account / Payer ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('mfa_required')
                    ->label('MFA')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
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
            ->recordActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $selectedClinicId = AdminClinicScope::selectedClinicId();

        return parent::getEloquentQuery()
            ->when(
                filled($selectedClinicId),
                fn (Builder $query) => $query->where('clinic_id', $selectedClinicId),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            );
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', PortalCredential::class) ?? false;
    }

    public static function canCreate(): bool
    {
        $clinic = AdminClinicScope::selectedClinic();

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return $clinic !== null
                && (auth()->user()?->can('create', PortalCredential::class) ?? false)
                && (auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'add') ?? false);
        }

        return $clinic !== null
            && SaasSupportAccess::matchesScope((int) $clinic->organization_id, (int) $clinic->getKey())
            && (auth()->user()?->can('create', PortalCredential::class) ?? false);
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof PortalCredential || ! (auth()->user()?->can('update', $record) ?? false)) {
            return false;
        }

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return (int) AdminClinicScope::selectedClinicId() === (int) $record->clinic_id
                && (auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'update') ?? false);
        }

        return SaasSupportAccess::matchesScope((int) $record->organization_id, (int) $record->clinic_id);
    }

    public static function canDelete(Model $record): bool
    {
        if (! $record instanceof PortalCredential || ! (auth()->user()?->can('delete', $record) ?? false)) {
            return false;
        }

        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return (int) AdminClinicScope::selectedClinicId() === (int) $record->clinic_id
                && (auth()->user()?->canPerformVerificationModuleAction('portal_credentials', 'delete') ?? false);
        }

        return SaasSupportAccess::matchesScope((int) $record->organization_id, (int) $record->clinic_id);
    }

    public static function canRestore(Model $record): bool
    {
        return static::canDelete($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortalCredentials::route('/'),
            'create' => CreatePortalCredential::route('/create'),
            'edit' => EditPortalCredential::route('/{record}/edit'),
        ];
    }
}
