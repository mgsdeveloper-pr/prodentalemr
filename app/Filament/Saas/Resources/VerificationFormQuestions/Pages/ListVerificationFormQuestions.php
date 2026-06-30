<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions\Pages;

use App\Filament\Admin\Pages\VerificationQuestionArrangement;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\Clinic;
use App\Models\Organization;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;

class ListVerificationFormQuestions extends ListRecords
{
    protected static string $resource = VerificationFormQuestionResource::class;

    protected string $view = 'filament.saas.resources.verification-form-questions.pages.list-verification-form-questions';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Question')
                ->icon('heroicon-o-plus')
                ->color('warning'),
            Action::make('createTemplateSection')
                ->label('Add Section')
                ->icon('heroicon-o-folder-plus')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->canManageVerificationTemplateSections() ?? false)
                ->form([
                    Select::make('organization_id')
                        ->label('Organization')
                        ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->required(),
                    Select::make('clinic_id')
                        ->label('Clinic')
                        ->options(fn ($get): array => Clinic::query()
                            ->when($get('organization_id'), fn ($query, $organizationId) => $query->where('organization_id', $organizationId))
                            ->orderBy('clinic_name')
                            ->pluck('clinic_name', 'id')
                            ->all())
                        ->searchable()
                        ->required()
                        ->live(),
                    Select::make('template_key')
                        ->label('Template')
                        ->options(VerificationFormQuestion::templateOptionsForUi())
                        ->default(VerificationFormQuestion::defaultTemplateKey())
                        ->required()
                        ->live(),
                    Hidden::make('parent_section_key')
                        ->default(null),
                    TextInput::make('label')
                        ->label('Section name')
                        ->placeholder('Example: Implant Coverage')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(fn (array $data) => $this->createTemplateSection($data)),
            Action::make('createTemplateSubSection')
                ->label('Add Sub-section')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->canManageVerificationTemplateSections() ?? false)
                ->form([
                    Select::make('organization_id')
                        ->label('Organization')
                        ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->live()
                        ->required(),
                    Select::make('clinic_id')
                        ->label('Clinic')
                        ->options(fn ($get): array => Clinic::query()
                            ->when($get('organization_id'), fn ($query, $organizationId) => $query->where('organization_id', $organizationId))
                            ->orderBy('clinic_name')
                            ->pluck('clinic_name', 'id')
                            ->all())
                        ->searchable()
                        ->required()
                        ->live(),
                    Select::make('template_key')
                        ->label('Template')
                        ->options(VerificationFormQuestion::templateOptionsForUi())
                        ->default(VerificationFormQuestion::defaultTemplateKey())
                        ->required()
                        ->live(),
                    Select::make('parent_section_key')
                        ->label('Parent section')
                        ->helperText('Choose where this sub-section should sit.')
                        ->options(fn ($get): array => VerificationFormQuestion::topLevelSectionOptionsForTemplate($get('template_key'), filled($get('clinic_id')) ? (int) $get('clinic_id') : null))
                        ->searchable()
                        ->required(),
                    TextInput::make('label')
                        ->label('Sub-section name')
                        ->placeholder('Example: Implant Coverage')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(fn (array $data) => $this->createTemplateSection($data)),
            Action::make('rearrangeQuestions')
                ->label('Rearrange Questions')
                ->icon('heroicon-o-bars-3-bottom-left')
                ->color('gray')
                ->url(fn (): string => VerificationQuestionArrangement::getUrl()),
        ];
    }

    public function getVisibleHeaderActions(): array
    {
        return $this->getHeaderActions();
    }

    public function createTemplateSection(array $data): void
    {
        if (! (auth()->user()?->canManageVerificationTemplateSections() ?? false)) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $clinic = Clinic::query()->find((int) $data['clinic_id']);

        if (! $clinic) {
            Notification::make()->title('Select a valid clinic')->danger()->send();

            return;
        }

        $sectionKey = VerificationTemplateSection::makeSectionKey((string) $data['label'], $data['parent_section_key'] ?? null);
        $baseKey = $sectionKey;
        $counter = 2;

        while (VerificationTemplateSection::query()
            ->where('clinic_id', $clinic->id)
            ->where('template_key', $data['template_key'])
            ->where('section_key', $sectionKey)
            ->exists()) {
            $sectionKey = $baseKey . '_' . $counter++;
        }

        VerificationTemplateSection::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'template_key' => $data['template_key'],
            'section_key' => $sectionKey,
            'parent_section_key' => $data['parent_section_key'] ?? null,
            'label' => $data['label'],
            'sort_order' => ((int) VerificationTemplateSection::query()
                ->where('clinic_id', $clinic->id)
                ->where('template_key', $data['template_key'])
                ->max('sort_order')) + 10,
            'is_active' => true,
        ]);

        Notification::make()
            ->title('Template section created')
            ->body('The new section is now available while creating questions.')
            ->success()
            ->send();
    }

    public function getBuiltInSections(): array
    {
        $clinicId = $this->getSelectedClinicId();

        if (! filled($clinicId)) {
            return [];
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->with('clinic.organization')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key')
            ->map(function (Collection $questions, string $sectionKey): array {
                $activeCount = $questions->where('is_active', true)->count();
                $systemCount = $questions->where('is_builtin', true)->count();

                return [
                    'title' => VerificationFormQuestion::sectionLabel(
                        $sectionKey,
                        $questions->first()?->template_key,
                        $questions->first()?->clinic_id,
                    ),
                    'count' => $questions->count(),
                    'active_count' => $activeCount,
                    'system_count' => $systemCount,
                    'questions' => $questions->take(5)->map(function (VerificationFormQuestion $question): array {
                        return [
                            'prompt' => filled($question->code) ? "{$question->code} {$question->prompt}" : $question->prompt,
                            'is_active' => $question->is_active,
                            'is_builtin' => $question->is_builtin,
                            'form_type' => VerificationFormQuestion::FORM_TYPE_OPTIONS[$question->form_type] ?? str($question->form_type)->headline()->toString(),
                        ];
                    })->all(),
                ];
            })
            ->all();
    }

    public function getSelectedClinicId(): ?int
    {
        $candidate = data_get($this->tableFilters, 'clinic_id.value')
            ?? data_get($this->tableFilters, 'clinic_id')
            ?? null;

        return filled($candidate) ? (int) $candidate : null;
    }

    public function getSelectedClinicName(): ?string
    {
        $clinicId = $this->getSelectedClinicId();

        if (! $clinicId) {
            return null;
        }

        return Clinic::query()->whereKey($clinicId)->value('clinic_name');
    }
}
