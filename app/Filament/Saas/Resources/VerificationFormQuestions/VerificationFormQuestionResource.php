<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions;

use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\CreateVerificationFormQuestion;
use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\EditVerificationFormQuestion;
use App\Filament\Saas\Resources\VerificationFormQuestions\Pages\ListVerificationFormQuestions;
use App\Models\AdaProcedureCode;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\VerificationFormQuestion;
use App\Support\AdminClinicScope;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = VerificationFormQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Template Management';

    protected static ?string $modelLabel = 'Template Question';

    protected static ?string $pluralModelLabel = 'Template Questions';

    protected static string|UnitEnum|null $navigationGroup = 'Verification Workspace';

    protected static ?int $navigationSort = 4;

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
                        Section::make('Step 1 - Scope & Template')
                            ->description('Choose where this question belongs before writing it. This keeps the Template 2 builder clean and organized.')
                            ->columnSpan(12)
                            ->schema([
                                Grid::make(12)
                                    ->schema([
                                        Select::make('organization_id')
                                            ->label('Organization')
                                            ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                                            ->native(false)
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('clinic_id', null))
                                            ->default(fn (): ?int => AdminClinicScope::selectedClinic()?->organization_id)
                                            ->required()
                                            ->columnSpan(4),
                                        Select::make('clinic_id')
                                            ->label('Clinic')
                                            ->options(function (Get $get): array {
                                                $organizationId = $get('organization_id');

                                                return Clinic::query()
                                                    ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
                                                    ->orderBy('clinic_name')
                                                    ->pluck('clinic_name', 'id')
                                                    ->all();
                                            })
                                            ->native(false)
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->default(fn (): ?int => AdminClinicScope::selectedClinicId())
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                if (! filled($state)) {
                                                    return;
                                                }

                                                $organizationId = Clinic::query()->whereKey($state)->value('organization_id');

                                                if (filled($organizationId)) {
                                                    $set('organization_id', $organizationId);
                                                }
                                            })
                                            ->columnSpan(4),
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
                                            ->options(fn (Get $get): array => VerificationFormQuestion::topLevelSectionOptionsForTemplate($get('template_key'), filled($get('clinic_id')) ? (int) $get('clinic_id') : null))
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
                                            ))
                                            ->visible(fn (Get $get): bool => count(VerificationFormQuestion::childSectionOptionsForTemplate(
                                                $get('template_key'),
                                                filled($get('clinic_id')) ? (int) $get('clinic_id') : null,
                                                $get('section_key'),
                                            )) > 0)
                                            ->required(fn (Get $get): bool => count(VerificationFormQuestion::childSectionOptionsForTemplate(
                                                $get('template_key'),
                                                filled($get('clinic_id')) ? (int) $get('clinic_id') : null,
                                                $get('section_key'),
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
                                            ->helperText('Displays an optional note box beside or below this question in Template 2.')
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
                                        Placeholder::make('question_guidance')
                                            ->label('What this means')
                                            ->content('Use Active for live questions. Use System question only for locked questions tied to the built-in verification worksheet.')
                                            ->columnSpan(6),
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
                $query = AdminClinicScope::apply($query, 'clinic_id');

                return $query
                    ->with(['organization', 'clinic'])
                    ->orderBy('organization_id')
                    ->orderBy('clinic_id')
                    ->orderBy('section_key')
                    ->orderBy('sort_order');
            })
            ->columns([
                TextColumn::make('prompt')
                    ->label('Question')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->toggleable(),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->toggleable(),
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
            ])
            ->filters([
                SelectFilter::make('template_key')
                    ->label('Template')
                    ->options(VerificationFormQuestion::templateOptionsForUi())
                    ->default(VerificationFormQuestion::defaultTemplateKey()),
                SelectFilter::make('section_key')
                    ->label('Section')
                    ->options(fn (): array => static::sectionFilterOptions()),
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('clinic_id')
                    ->label('Clinic')
                    ->options(fn (): array => Clinic::query()->orderBy('clinic_name')->pluck('clinic_name', 'id')->all()),
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
        return auth()->user()?->canManageVerificationTemplateSections() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->canManageVerificationTemplateSections() ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationFormQuestions::route('/'),
            'create' => CreateVerificationFormQuestion::route('/create'),
            'edit' => EditVerificationFormQuestion::route('/{record}/edit'),
        ];
    }

    protected static function sectionFilterOptions(): array
    {
        $options = VerificationFormQuestion::SECTION_OPTIONS
            + VerificationFormQuestion::TEMPLATE_2_SECTION_OPTIONS
            + VerificationFormQuestion::TEMPLATE_3_SECTION_OPTIONS;

        VerificationFormQuestion::query()
            ->select(['section_key', 'template_key', 'clinic_id'])
            ->whereNotNull('section_key')
            ->distinct()
            ->get()
            ->each(function (VerificationFormQuestion $question) use (&$options): void {
                $options[$question->section_key] = VerificationFormQuestion::sectionLabel(
                    $question->section_key,
                    $question->template_key,
                    $question->clinic_id,
                );
            });

        asort($options);

        return $options;
    }
}
