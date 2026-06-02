<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions;

use App\Filament\Clinic\Resources\VerificationQuestions\Pages\CreateVerificationQuestion;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\EditVerificationQuestion;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\ListVerificationQuestions;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\ReorderVerificationQuestions;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Support\ClinicPanelScope;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class VerificationQuestionResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = VerificationFormQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Verification Questions';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(12)
                    ->schema([
                        Hidden::make('organization_id')
                            ->default(fn (): ?int => ClinicPanelScope::selectedOrganizationId()),
                        Hidden::make('clinic_id')
                            ->default(fn (): ?int => ClinicPanelScope::selectedClinicId()),
                        Hidden::make('sort_order')
                            ->default(0),
                        Hidden::make('order_position')
                            ->default('bottom'),
                        Hidden::make('order_reference_id'),
                        Section::make('Question Basics')
                            ->description('Create the question in the same order your team thinks about it: choose the form, place it in the right section, decide the response type, then add display guidance.')
                            ->columnSpan(12)
                            ->schema([
                                Placeholder::make('clinic_scope')
                                    ->label('Clinic scope')
                                    ->content(function (): string {
                                        $clinic = ClinicPanelScope::selectedClinic();

                                        return $clinic?->clinic_name
                                            ? $clinic->clinic_name . ' - ' . ($clinic->organization?->name ?? '')
                                            : 'Select a clinic from the Workspace menu first.';
                                    }),
                                Grid::make(12)
                                    ->schema([
                                        TextInput::make('prompt')
                                            ->label('Question')
                                            ->placeholder('Example: Is the provider in network with this plan?')
                                            ->live(onBlur: true)
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(12),
                                        Placeholder::make('prompt_preview')
                                            ->label('Question preview')
                                            ->content(fn (Get $get): string => filled($get('prompt')) ? (string) $get('prompt') : 'Your drafted question will appear here so you can review the wording before saving it.')
                                            ->columnSpan(12),
                                        Select::make('form_type')
                                            ->label('Form')
                                            ->options(VerificationFormQuestion::FORM_TYPE_OPTIONS)
                                            ->default('both')
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->columnSpan(4),
                                        Select::make('section_key')
                                            ->label('Section')
                                            ->options(VerificationFormQuestion::SECTION_OPTIONS)
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->columnSpan(4),
                                        Select::make('input_type')
                                            ->label('Type of response')
                                            ->options(VerificationFormQuestion::INPUT_TYPE_OPTIONS)
                                            ->default('text')
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->columnSpan(4),
                                        TextInput::make('placeholder')
                                            ->label('Placeholder')
                                            ->placeholder('Example: Enter payer phone number')
                                            ->maxLength(255)
                                            ->columnSpan(6),
                                    ]),
                            ]),
                        Section::make('Display & Guidance')
                            ->description('Add the optional guidance and control settings after the main question structure is in place.')
                            ->columnSpan(12)
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Textarea::make('help_text')
                                            ->label('Help text')
                                            ->placeholder('Add a short instruction to guide the user when answering this question.')
                                            ->rows(3)
                                            ->columnSpan(12),
                                        Toggle::make('is_active')
                                            ->label('Active')
                                            ->default(true)
                                            ->inline(false)
                                            ->columnSpan(3),
                                        Toggle::make('is_builtin')
                                            ->label('System question')
                                            ->default(false)
                                            ->inline(false)
                                            ->columnSpan(3),
                                        Placeholder::make('question_guidance')
                                            ->label('What this means')
                                            ->content('Use Active for live questions. Use System question only when the prompt should stay tied to the built-in verification worksheet structure.')
                                            ->columnSpan(6),
                                    ]),
                            ]),
                        Section::make('Field Binding')
                            ->description('Only use these fields when the question should map directly to stored verification values or a matrix-style worksheet row.')
                            ->columnSpan(12)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('field_key')
                                            ->label('Primary field key')
                                            ->options(fn (Get $get): array => VerificationFormQuestion::fieldKeyOptionsForSection($get('section_key')))
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->placeholder('Choose a mapped verification field')
                                            ->columnSpan(3),
                                        Select::make('secondary_field_key')
                                            ->label('Secondary field key')
                                            ->options(fn (Get $get): array => VerificationFormQuestion::fieldKeyOptionsForSection($get('section_key')))
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->placeholder('Optional paired field')
                                            ->columnSpan(3),
                                        Select::make('secondary_input_type')
                                            ->label('Secondary answer type')
                                            ->options(VerificationFormQuestion::INPUT_TYPE_OPTIONS)
                                            ->native(false)
                                            ->columnSpan(3),
                                        Select::make('code')
                                            ->label('Code / label prefix')
                                            ->options(fn (Get $get): array => VerificationFormQuestion::codePrefixOptionsForSection($get('section_key')))
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->placeholder('Choose a simple label or code')
                                            ->columnSpan(3),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $selectedClinicId = ClinicPanelScope::selectedClinicId();

                return $query
                    ->when(
                        filled($selectedClinicId),
                        fn (Builder $builder) => $builder->where('clinic_id', $selectedClinicId),
                        fn (Builder $builder) => $builder->whereRaw('1 = 0')
                    )
                    ->with(['clinic', 'organization'])
                    ->orderBy('section_key')
                    ->orderBy('sort_order')
                    ->orderBy('id');
            })
            ->columns([
                TextColumn::make('prompt')
                    ->label('Question')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('section_key')
                    ->label('Section')
                    ->formatStateUsing(fn (string $state): string => VerificationFormQuestion::SECTION_OPTIONS[$state] ?? str($state)->headline()->toString())
                    ->badge(),
                TextColumn::make('form_type')
                    ->label('Form')
                    ->formatStateUsing(fn (string $state): string => VerificationFormQuestion::FORM_TYPE_OPTIONS[$state] ?? str($state)->headline()->toString())
                    ->badge(),
                TextColumn::make('input_type')
                    ->label('Answer Type')
                    ->formatStateUsing(fn (string $state): string => VerificationFormQuestion::INPUT_TYPE_OPTIONS[$state] ?? str($state)->headline()->toString()),
                TextColumn::make('field_key')
                    ->label('Primary Field')
                    ->toggleable(),
                TextColumn::make('secondary_field_key')
                    ->label('Secondary Field')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                IconColumn::make('is_builtin')
                    ->label('System')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('section_key')
                    ->label('Section')
                    ->options(VerificationFormQuestion::SECTION_OPTIONS),
                SelectFilter::make('form_type')
                    ->label('Form')
                    ->options(VerificationFormQuestion::FORM_TYPE_OPTIONS),
                TernaryFilter::make('is_builtin')
                    ->label('System question'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageClinicVerificationSettings() ?? false;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess() && filled(ClinicPanelScope::selectedClinicId());
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationQuestions::route('/'),
            'create' => CreateVerificationQuestion::route('/create'),
            'edit' => EditVerificationQuestion::route('/{record}/edit'),
            'reorder' => ReorderVerificationQuestions::route('/reorder'),
        ];
    }
}
