<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions;

use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\CreateVerificationFormQuestion;
use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\EditVerificationFormQuestion;
use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\ListVerificationFormQuestions;
use App\Models\AdaProcedureCode;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateVersionService;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
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
use Filament\Schemas\Components\Utilities\Set;
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

class VerificationFormQuestionResource extends Resource
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $model = VerificationFormQuestion::class;

    protected static ?string $slug = 'master-template';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Master Template';

    protected static ?string $modelLabel = 'Template Question';

    protected static ?string $pluralModelLabel = 'Template Questions';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'saas';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(12)
                    ->schema([
                        Hidden::make('sort_order')
                            ->default(0),
                        Hidden::make('order_position')
                            ->default('bottom'),
                        Hidden::make('order_reference_id'),
                        Section::make('Step 1 - Master Template')
                            ->description('Manage the platform Master Template directly. Client-specific templates are created later by copying this master into the client workspace.')
                            ->columnSpan(12)
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Hidden::make('organization_id')
                                            ->default(null),
                                        Hidden::make('clinic_id')
                                            ->default(null),
                                        Hidden::make('template_version_id'),
                                        Select::make('template_key')
                                            ->label('Template')
                                            ->options(VerificationFormQuestion::templateOptionsForUi())
                                            ->default(VerificationFormQuestion::defaultTemplateKey())
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->afterStateUpdated(function (Set $set): void {
                                                $set('section_key', null);
                                                $set('sub_section_key', null);
                                            })
                                            ->columnSpan(4),
                                        Select::make('section_key')
                                            ->label('Template section')
                                            ->options(fn (Get $get): array => VerificationFormQuestion::topLevelSectionOptionsForTemplate(
                                                $get('template_key'),
                                                filled($get('clinic_id')) ? (int) $get('clinic_id') : null,
                                                filled($get('template_version_id')) ? (int) $get('template_version_id') : null,
                                            ))
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->afterStateUpdated(function (Set $set): void {
                                                $set('sub_section_key', null);
                                                $set('input_type', 'text');
                                            })
                                            ->columnSpan(4),
                                        Select::make('sub_section_key')
                                            ->label('Template sub-section')
                                            ->options(fn (Get $get): array => VerificationFormQuestion::childSectionOptionsForTemplate(
                                                $get('template_key'),
                                                filled($get('clinic_id')) ? (int) $get('clinic_id') : null,
                                                $get('section_key'),
                                                filled($get('template_version_id')) ? (int) $get('template_version_id') : null,
                                            ))
                                            ->visible(fn (Get $get): bool => count(VerificationFormQuestion::childSectionOptionsForTemplate(
                                                $get('template_key'),
                                                filled($get('clinic_id')) ? (int) $get('clinic_id') : null,
                                                $get('section_key'),
                                                filled($get('template_version_id')) ? (int) $get('template_version_id') : null,
                                            )) > 0)
                                            ->required(fn (Get $get): bool => count(VerificationFormQuestion::childSectionOptionsForTemplate(
                                                $get('template_key'),
                                                filled($get('clinic_id')) ? (int) $get('clinic_id') : null,
                                                $get('section_key'),
                                                filled($get('template_version_id')) ? (int) $get('template_version_id') : null,
                                            )) > 0)
                                            ->live()
                                            ->native(false)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                                $sectionKey = filled($state) ? $state : $get('section_key');

                                                if (VerificationFormQuestion::isFrequencyPercentageSection($sectionKey)) {
                                                    $set('input_type', 'frequency_row');
                                                }
                                            })
                                            ->columnSpan(4),
                                        Select::make('form_type')
                                            ->label('Visible on')
                                            ->options(VerificationFormQuestion::FORM_TYPE_OPTIONS)
                                            ->default('both')
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->columnSpan(4),
                                    ]),
                            ]),
                        Section::make('Step 2 - Question & Response')
                            ->description('Write the question exactly how the verification team should see it, then choose how the answer should be captured.')
                            ->columnSpan(12)
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('question_kind')
                                            ->label('Question placement')
                                            ->options(VerificationFormQuestion::QUESTION_KIND_OPTIONS)
                                            ->default(VerificationFormQuestion::QUESTION_KIND_NORMAL)
                                            ->required()
                                            ->live()
                                            ->native(false)
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                if ($state !== VerificationFormQuestion::QUESTION_KIND_CONDITIONAL) {
                                                    $set('parent_question_id', null);
                                                    $set('trigger_answer', null);
                                                }
                                            })
                                            ->visible(fn (Get $get): bool => ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->columnSpan(3),
                                        Select::make('parent_question_id')
                                            ->label('Show after question')
                                            ->helperText('Conditional questions are shown only when this parent Yes/No question matches the trigger.')
                                            ->options(fn (Get $get, ?VerificationFormQuestion $record = null): array => VerificationFormQuestion::parentQuestionOptionsFor(
                                                $get('template_key'),
                                                filled($get('clinic_id')) ? (int) $get('clinic_id') : null,
                                                filled($get('organization_id')) ? (int) $get('organization_id') : null,
                                                $get('sub_section_key') ?: $get('section_key'),
                                                $record?->getKey(),
                                            ))
                                            ->searchable()
                                            ->native(false)
                                            ->visible(fn (Get $get): bool => ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                && $get('question_kind') === VerificationFormQuestion::QUESTION_KIND_CONDITIONAL)
                                            ->required(fn (Get $get): bool => ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                && $get('question_kind') === VerificationFormQuestion::QUESTION_KIND_CONDITIONAL)
                                            ->columnSpan(6),
                                        Select::make('trigger_answer')
                                            ->label('Show when answer is')
                                            ->options(VerificationFormQuestion::CONDITIONAL_TRIGGER_OPTIONS)
                                            ->native(false)
                                            ->visible(fn (Get $get): bool => ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                && $get('question_kind') === VerificationFormQuestion::QUESTION_KIND_CONDITIONAL)
                                            ->required(fn (Get $get): bool => ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                && $get('question_kind') === VerificationFormQuestion::QUESTION_KIND_CONDITIONAL)
                                            ->columnSpan(3),
                                        Select::make('frequency_row_mode')
                                            ->label('Frequency row type')
                                            ->options([
                                                'question' => 'Formal Question',
                                                'code' => 'Code (ADA/CDT code)',
                                            ])
                                            ->default('question')
                                            ->live()
                                            ->native(false)
                                            ->dehydrated(false)
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                if ($state === 'question') {
                                                    $set('code', null);
                                                }
                                            })
                                            ->visible(fn (Get $get): bool => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->required(fn (Get $get): bool => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->columnSpan(3),
                                        Select::make('code')
                                            ->label('ADA/CDT Code')
                                            ->placeholder('Example: D0120')
                                            ->helperText('Used only when this frequency row is an ADA/CDT code.')
                                            ->searchable()
                                            ->native(false)
                                            ->options(fn (): array => AdaProcedureCode::query()
                                                ->active()
                                                ->orderBy('procedure_code')
                                                ->limit(50)
                                                ->pluck('procedure_code', 'procedure_code')
                                                ->all())
                                            ->getSearchResultsUsing(fn (string $search): array => AdaProcedureCode::query()
                                                ->active()
                                                ->where(function ($query) use ($search): void {
                                                    $query
                                                        ->where('procedure_code', 'like', "%{$search}%")
                                                        ->orWhere('description', 'like', "%{$search}%");
                                                })
                                                ->orderBy('procedure_code')
                                                ->limit(50)
                                                ->pluck('procedure_code', 'procedure_code')
                                                ->all())
                                            ->getOptionLabelUsing(fn ($value): ?string => $value)
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                $description = AdaProcedureCode::query()
                                                    ->active()
                                                    ->where('procedure_code', $state)
                                                    ->value('description');

                                                if (filled($description)) {
                                                    $set('prompt', $description);
                                                }
                                            })
                                            ->visible(fn (Get $get): bool => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                && $get('frequency_row_mode') === 'code')
                                            ->required(fn (Get $get): bool => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                && $get('frequency_row_mode') === 'code')
                                            ->columnSpan(3),
                                        TextInput::make('prompt')
                                            ->label(fn (Get $get): string => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                ? ($get('frequency_row_mode') === 'code' ? 'Description' : 'Question')
                                                : 'Question text')
                                            ->placeholder(fn (Get $get): string => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                ? ($get('frequency_row_mode') === 'code' ? 'Example: Regular Checkup' : 'Example: Is this service covered?')
                                                : 'Example: Is there any waiting period on this plan?')
                                            ->live(onBlur: true)
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(fn (Get $get): int => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')) ? 5 : 8),
                                        Select::make('input_type')
                                            ->label('Answer type')
                                            ->options(VerificationFormQuestion::INPUT_TYPE_OPTIONS)
                                            ->default('text')
                                            ->required()
                                            ->live()
                                            ->helperText(fn (Get $get): ?string => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key'))
                                                ? 'Frequency rows always answer through %, Frequency, Pre-Auth, and Notes in the verification form.'
                                                : null)
                                            ->native(false)
                                            ->visible(fn (Get $get): bool => ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->columnSpan(4),
                                        Select::make('frequency_response_mode')
                                            ->label('Response option')
                                            ->options(VerificationFormQuestion::FREQUENCY_RESPONSE_MODE_OPTIONS)
                                            ->default('current')
                                            ->live()
                                            ->native(false)
                                            ->afterStateUpdated(fn ($state, Set $set) => $set('frequency_response_fields', VerificationFormQuestion::defaultFrequencyResponseFields($state)))
                                            ->visible(fn (Get $get): bool => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->required(fn (Get $get): bool => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->columnSpan(4),
                                        CheckboxList::make('frequency_response_fields')
                                            ->label('Optional response fields')
                                            ->helperText('The verification form always collects % and Frequency. Select the additional fields this row should ask for.')
                                            ->options(fn (Get $get): array => VerificationFormQuestion::frequencyResponseFieldOptions($get('frequency_response_mode')))
                                            ->default(fn (Get $get): array => VerificationFormQuestion::defaultFrequencyResponseFields($get('frequency_response_mode')))
                                            ->columns(3)
                                            ->visible(fn (Get $get): bool => VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->columnSpan(12),
                                        TextInput::make('placeholder')
                                            ->label('Answer placeholder')
                                            ->placeholder('Example: Add waiting period note')
                                            ->maxLength(255)
                                            ->visible(fn (Get $get): bool => ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->columnSpan(12),
                                        Textarea::make('select_options')
                                            ->label('Dropdown options')
                                            ->placeholder("Enter one option per line\nExample:\nYes\nNo\nNot Applicable")
                                            ->helperText('Only used when the response type is Dropdown or Multi Response.')
                                            ->rows(5)
                                            ->visible(fn (Get $get): bool => in_array($get('input_type'), ['select', 'multi_select'], true)
                                                && ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->required(fn (Get $get): bool => in_array($get('input_type'), ['select', 'multi_select'], true)
                                                && ! VerificationFormQuestion::isFrequencyPercentageSection($get('sub_section_key') ?: $get('section_key')))
                                            ->columnSpan(12),
                                    ]),
                            ]),
                        Section::make('Step 3 - Notes, Guidance & Status')
                            ->description('Use these only when the question needs instructions, an extra note field, or active/inactive control.')
                            ->columnSpan(12)
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Textarea::make('help_text')
                                            ->label('Instruction text')
                                            ->placeholder('Optional: Add a short instruction shown near this question.')
                                            ->rows(3)
                                            ->columnSpan(12),
                                        Toggle::make('has_note')
                                            ->label('Add a separate note area')
                                            ->helperText('Displays an optional note box beside or below this question in the Master Template.')
                                            ->default(false)
                                            ->live()
                                            ->inline(false)
                                            ->columnSpan(4),
                                        TextInput::make('note_label')
                                            ->label('Note label')
                                            ->placeholder('Example: Additional details')
                                            ->visible(fn (Get $get): bool => (bool) $get('has_note'))
                                            ->maxLength(255)
                                            ->columnSpan(4),
                                        TextInput::make('note_placeholder')
                                            ->label('Note placeholder')
                                            ->placeholder('Example: Add any supporting information')
                                            ->visible(fn (Get $get): bool => (bool) $get('has_note'))
                                            ->maxLength(255)
                                            ->columnSpan(4),
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
                                        Toggle::make('is_required_for_audit')
                                            ->label('Required for Audit')
                                            ->helperText('Audit will block completion until this answer is filled. Conditional questions are checked only when visible.')
                                            ->default(false)
                                            ->inline(false)
                                            ->columnSpan(3),
                                        Placeholder::make('question_guidance')
                                            ->label('What this means')
                                            ->content('Use Active for live questions. Use System question only for locked questions tied to the built-in verification worksheet.')
                                            ->columnSpan(3),
                                    ]),
                            ]),
                        Section::make('Field Binding')
                            ->description('Only use these fields when the question should map directly to stored verification values or a matrix-style worksheet row.')
                            ->columnSpan(12)
                            ->visible(fn (Get $get): bool => ! VerificationFormQuestion::isWorksheetTemplate($get('template_key')))
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
                $version = static::currentMasterWorkingVersion();

                return $query
                    ->whereNull('organization_id')
                    ->whereNull('clinic_id')
                    ->when(
                        $version,
                        fn (Builder $query) => $query->where('template_version_id', $version->getKey())
                    )
                    ->orderBy('section_key')
                    ->orderBy('sort_order');
            })
            ->columns([
                TextColumn::make('prompt')
                    ->label('Question')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('code')
                    ->label('Code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('section_key')
                    ->label('Section')
                    ->formatStateUsing(fn (?string $state, VerificationFormQuestion $record): string => VerificationFormQuestion::sectionLabel($state, $record->template_key, $record->clinic_id))
                    ->badge(),
                TextColumn::make('template_key')
                    ->label('Template')
                    ->formatStateUsing(fn (string $state): string => VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS[$state] ?? str($state)->headline()->toString())
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
                IconColumn::make('has_note')
                    ->label('Note')
                    ->boolean(),
                IconColumn::make('is_required_for_audit')
                    ->label('Audit')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('template_key')
                    ->label('Template')
                    ->options(VerificationFormQuestion::templateOptionsForUi())
                    ->default(VerificationFormQuestion::defaultTemplateKey()),
                SelectFilter::make('section_key')
                    ->label('Section')
                    ->options(fn (): array => static::sectionFilterOptions()),
                SelectFilter::make('form_type')
                    ->label('Form')
                    ->options(VerificationFormQuestion::FORM_TYPE_OPTIONS),
                TernaryFilter::make('is_active')
                    ->label('Active question'),
                TernaryFilter::make('is_builtin')
                    ->label('System question'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (VerificationFormQuestion $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (VerificationFormQuestion $record): bool => static::canDelete($record)),
            ]);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->canManageVerificationSettings()
            || $user?->canAccessVerificationModule('template_management')
            || $user?->canAccessSaasModule('template_management')
            || $user?->isSaasAdmin());
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canManageVerificationTemplateSections() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return (auth()->user()?->canManageVerificationTemplateSections() ?? false)
            && blank($record->organization_id)
            && blank($record->clinic_id)
            && ($record->templateVersion?->canEditDirectly() ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return (auth()->user()?->canManageVerificationTemplateSections() ?? false)
            && blank($record->organization_id)
            && blank($record->clinic_id)
            && ! $record->is_builtin
            && ! $record->is_locked_by_admin
            && ($record->templateVersion?->canEditDirectly() ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('organization_id')
            ->whereNull('clinic_id')
            ->whereHas('templateVersion', fn (Builder $query) => $query
                ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
                ->where('template_key', VerificationFormQuestion::defaultTemplateKey())
                ->whereNull('organization_id')
                ->whereNull('clinic_id'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationFormQuestions::route('/'),
            'create' => CreateVerificationFormQuestion::route('/questions/create'),
            'edit' => EditVerificationFormQuestion::route('/questions/{record}/edit'),
        ];
    }

    protected static function sectionFilterOptions(): array
    {
        return VerificationFormQuestion::sectionOptionsForTemplate(
            VerificationFormQuestion::defaultTemplateKey(),
        );
    }

    public static function currentMasterWorkingVersion(): ?VerificationTemplateVersion
    {
        $templateKey = VerificationFormQuestion::defaultTemplateKey();

        $draft = VerificationTemplateVersion::query()
            ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
            ->where('template_key', $templateKey)
            ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
            ->whereNull('clinic_id')
            ->orderByDesc('is_working_draft')
            ->latest('id')
            ->first();

        if ($draft) {
            return $draft;
        }

        return app(VerificationTemplateVersionService::class)->ensureMasterVersion($templateKey);
    }
}
