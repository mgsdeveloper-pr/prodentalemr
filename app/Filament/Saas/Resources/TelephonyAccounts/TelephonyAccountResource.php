<?php

namespace App\Filament\Saas\Resources\TelephonyAccounts;

use App\Filament\Saas\Resources\TelephonyAccounts\Pages\CreateTelephonyAccount;
use App\Filament\Saas\Resources\TelephonyAccounts\Pages\EditTelephonyAccount;
use App\Filament\Saas\Resources\TelephonyAccounts\Pages\ListTelephonyAccounts;
use App\Models\TelephonyAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TelephonyAccountResource extends Resource
{
    protected static ?string $model = TelephonyAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static string|UnitEnum|null $navigationGroup = 'Calling';

    protected static ?string $navigationLabel = 'Calling Setup';

    protected static ?string $modelLabel = 'calling account';

    protected static ?string $pluralModelLabel = 'calling accounts';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Client & Provider')
                    ->description('Connect MightyCall once and control which client can use it.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Connection name')
                            ->required()
                            ->maxLength(255),
                        Select::make('organization_id')
                            ->label('Client organization')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty only for the platform-wide default connection.'),
                        Select::make('provider')
                            ->options([TelephonyAccount::PROVIDER_MIGHTYCALL => 'MightyCall'])
                            ->default(TelephonyAccount::PROVIDER_MIGHTYCALL)
                            ->required()
                            ->native(false),
                        TextInput::make('business_number')
                            ->label('Business caller number')
                            ->tel()
                            ->placeholder('+15551234567')
                            ->maxLength(32),
                        Toggle::make('is_platform_default')
                            ->label('Platform default')
                            ->helperText('Used only when a client does not have its own calling connection.')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Connection active')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Secure Connection')
                    ->description('Credentials are encrypted. They are revealed only to an authorized user while initializing the embedded phone.')
                    ->schema([
                        TextInput::make('api_key')
                            ->label('MightyCall API key')
                            ->password()
                            ->revealable()
                            ->required()
                            ->maxLength(500),
                        TextInput::make('api_secret')
                            ->label('MightyCall account secret')
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->helperText('Reserved for server-side synchronization. Individual calls use the assigned user key.'),
                        TextInput::make('webphone_sdk_url')
                            ->label('WebPhone service URL')
                            ->url()
                            ->default('https://ccapi.mightycall.com/v4/sdk/mightycall.webphone.sdk.js')
                            ->required()
                            ->columnSpanFull(),
                        Placeholder::make('webhook_url')
                            ->label('MightyCall webhook URL')
                            ->content(fn (?TelephonyAccount $record): string => $record?->exists
                                ? $record->webhookUrl()
                                : 'Save this connection to generate its secure webhook URL.')
                            ->helperText('Add this address in MightyCall under Integrations, API, Webhooks.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Recording & Intelligence')
                    ->description('These settings cannot exceed the features included in the client subscription plan.')
                    ->schema([
                        Toggle::make('recording_enabled')
                            ->label('Record calls')
                            ->default(true),
                        Toggle::make('transcription_enabled')
                            ->label('Create transcripts')
                            ->default(false),
                        Toggle::make('ai_summary_enabled')
                            ->label('Create AI summaries')
                            ->default(false),
                        TextInput::make('recording_retention_days')
                            ->label('Recording retention')
                            ->suffix('days')
                            ->numeric()
                            ->default(365)
                            ->minValue(1)
                            ->maxValue(365),
                        TextInput::make('monthly_minute_limit')
                            ->label('Connection minute limit')
                            ->suffix('minutes')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('recording_announcement')
                            ->label('Recording announcement')
                            ->placeholder('This call may be recorded for quality assurance.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('User Calling Access')
                    ->description('Map portal users to their MightyCall user key and grant only the features they need.')
                    ->schema([
                        Repeater::make('userAssignments')
                            ->relationship()
                            ->label('Assigned users')
                            ->addActionLabel('Assign portal user')
                            ->defaultItems(0)
                            ->columns(4)
                            ->schema([
                                Select::make('user_id')
                                    ->label('Portal user')
                                    ->relationship('user', 'name')
                                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->name.' ('.$record->email.')')
                                    ->searchable(['name', 'email'])
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('provider_user_id')
                                    ->label('MightyCall user ID')
                                    ->maxLength(255),
                                TextInput::make('extension')
                                    ->label('Extension')
                                    ->maxLength(50),
                                TextInput::make('user_key')
                                    ->label('MightyCall user key')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpan(2),
                                Toggle::make('can_call')
                                    ->label('Can call')
                                    ->default(true),
                                Toggle::make('can_access_recordings')
                                    ->label('Recording access')
                                    ->default(false),
                                Toggle::make('can_use_ai_summary')
                                    ->label('AI summaries')
                                    ->default(false),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Internal Notes')
                    ->schema([
                        Textarea::make('notes')->rows(3)->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                TextColumn::make('scope_label')->label('Client')->searchable(query: fn ($query, string $search) => $query->whereHas('organization', fn ($builder) => $builder->where('name', 'like', "%{$search}%"))),
                TextColumn::make('provider')->formatStateUsing(fn (): string => 'MightyCall')->badge()->color('info'),
                TextColumn::make('business_number')->label('Caller number')->placeholder('-'),
                TextColumn::make('user_assignments_count')->label('Users')->counts('userAssignments')->badge(),
                TextColumn::make('monthly_minute_limit')->label('Monthly limit')->suffix(' min')->placeholder('Plan limit'),
                IconColumn::make('recording_enabled')->label('Recording')->boolean(),
                IconColumn::make('ai_summary_enabled')->label('AI summary')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAccessSaasModule('calling') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('calling', 'add') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('calling', 'update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->canPerformSaasModuleAction('calling', 'delete') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTelephonyAccounts::route('/'),
            'create' => CreateTelephonyAccount::route('/create'),
            'edit' => EditTelephonyAccount::route('/{record}/edit'),
        ];
    }
}
