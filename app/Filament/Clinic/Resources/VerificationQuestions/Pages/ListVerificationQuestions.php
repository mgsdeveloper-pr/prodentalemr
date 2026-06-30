<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Support\ClinicPanelScope;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListVerificationQuestions extends ListRecords
{
    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.list-verification-questions';

    public string $selectedTemplateKey = 'template_2';

    protected $queryString = [
        'selectedTemplateKey' => ['except' => 'template_2', 'as' => 'template'],
    ];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reorderQuestions')
                ->label('Rearrange questions')
                ->url(VerificationQuestionResource::getUrl('reorder'))
                ->color('gray'),
            Action::make('createTemplateSection')
                ->label('Add Section')
                ->icon('heroicon-o-folder-plus')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->canManageClinicTemplateSections() ?? false)
                ->form([
                    Select::make('template_key')
                        ->label('Template')
                        ->options(VerificationFormQuestion::templateOptionsForUi())
                        ->default(fn (): string => $this->selectedTemplateKey)
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
                ->visible(fn (): bool => auth()->user()?->canManageClinicTemplateSections() ?? false)
                ->form([
                    Select::make('template_key')
                        ->label('Template')
                        ->options(VerificationFormQuestion::templateOptionsForUi())
                        ->default(fn (): string => $this->selectedTemplateKey)
                        ->required()
                        ->live(),
                    Select::make('parent_section_key')
                        ->label('Parent section')
                        ->helperText('Choose where this sub-section should sit.')
                        ->options(fn ($get): array => VerificationFormQuestion::topLevelSectionOptionsForTemplate($get('template_key'), ClinicPanelScope::selectedClinicId()))
                        ->searchable()
                        ->required(),
                    TextInput::make('label')
                        ->label('Sub-section name')
                        ->placeholder('Example: Implant Coverage')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(fn (array $data) => $this->createTemplateSection($data)),
            CreateAction::make()
                ->label('New Question')
                ->icon('heroicon-o-plus')
                ->color('warning'),
        ];
    }

    public function getVisibleHeaderActions(): array
    {
        return $this->getHeaderActions();
    }

    public function createTemplateSection(array $data): void
    {
        if (! (auth()->user()?->canManageClinicTemplateSections() ?? false)) {
            Notification::make()->title('Permission denied')->danger()->send();

            return;
        }

        $clinic = ClinicPanelScope::selectedClinic();

        if (! $clinic) {
            Notification::make()->title('Select a clinic first')->danger()->send();

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

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
    }

    public function getCreateUrl(): string
    {
        return VerificationQuestionResource::getUrl('create');
    }

    public function getEditUrl(int $questionId): string
    {
        return VerificationQuestionResource::getUrl('edit', ['record' => $questionId]);
    }

    public function updatedSelectedTemplateKey(): void
    {
        $this->selectedTemplateKey = VerificationFormQuestion::normalizeTemplateKey($this->selectedTemplateKey);
    }

    public function selectTemplate(string $templateKey): void
    {
        $templateKey = VerificationFormQuestion::normalizeTemplateKey($templateKey);

        if (! array_key_exists($templateKey, VerificationFormQuestion::templateOptionsForUi())) {
            return;
        }

        $this->selectedTemplateKey = $templateKey;
    }

    public function getSelectedTemplateLabel(): string
    {
        return VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS[$this->selectedTemplateKey] ?? 'Template 2';
    }

    public function getTemplateOptions(): array
    {
        return VerificationFormQuestion::templateOptionsForUi();
    }

    public function deleteQuestion(int $questionId): void
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            Notification::make()
                ->title('Select a clinic first')
                ->danger()
                ->send();

            return;
        }

        $question = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->find($questionId);

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        $question->delete();

        Notification::make()
            ->title('Question deleted')
            ->success()
            ->send();

        $this->normalizeSectionOrder((string) $question->section_key, (int) $clinicId);
    }

    public function repositionQuestion(int $questionId, string $direction): void
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            Notification::make()
                ->title('Select a clinic first')
                ->danger()
                ->send();

            return;
        }

        /** @var VerificationFormQuestion|null $question */
        $question = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->find($questionId);

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        $questions = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('section_key', $question->section_key)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        $ids = $questions->pluck('id')->all();
        $currentIndex = array_search($questionId, $ids, true);

        if ($currentIndex === false) {
            return;
        }

        $newIds = $ids;

        switch ($direction) {
            case 'top':
                if ($currentIndex === 0) {
                    return;
                }

                unset($newIds[$currentIndex]);
                array_unshift($newIds, $questionId);
                break;

            case 'bottom':
                if ($currentIndex === count($ids) - 1) {
                    return;
                }

                unset($newIds[$currentIndex]);
                $newIds[] = $questionId;
                break;

            case 'up':
                if ($currentIndex === 0) {
                    return;
                }

                [$newIds[$currentIndex - 1], $newIds[$currentIndex]] = [$newIds[$currentIndex], $newIds[$currentIndex - 1]];
                break;

            case 'down':
                if ($currentIndex === count($ids) - 1) {
                    return;
                }

                [$newIds[$currentIndex + 1], $newIds[$currentIndex]] = [$newIds[$currentIndex], $newIds[$currentIndex + 1]];
                break;

            default:
                return;
        }

        $newIds = array_values(array_filter($newIds));

        DB::transaction(function () use ($newIds): void {
            foreach ($newIds as $index => $id) {
                VerificationFormQuestion::query()
                    ->whereKey($id)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        Notification::make()
            ->title('Question order updated')
            ->success()
            ->send();
    }

    protected function normalizeSectionOrder(string $sectionKey, int $clinicId): void
    {
        $questions = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('section_key', $sectionKey)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        foreach ($questions as $index => $question) {
            VerificationFormQuestion::query()
                ->whereKey($question->id)
                ->update(['sort_order' => ($index + 1) * 10]);
        }
    }

    public function getQuestionSections(): Collection
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            return collect();
        }

        $questions = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', $this->selectedTemplateKey)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key');

        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->selectedTemplateKey, $clinicId))
            ->map(function (string $sectionTitle, string $sectionKey) use ($questions): array {
                $sectionQuestions = $questions->get($sectionKey, collect());
                $activeCount = $sectionQuestions->where('is_active', true)->count();
                $systemCount = $sectionQuestions->where('is_builtin', true)->count();

                return [
                    'key' => $sectionKey,
                    'title' => $sectionTitle,
                    'count' => $sectionQuestions->count(),
                    'active_count' => $activeCount,
                    'system_count' => $systemCount,
                    'questions' => $sectionQuestions->map(function (VerificationFormQuestion $question): array {
                        return [
                            'id' => $question->getKey(),
                            'prompt' => $question->prompt,
                            'is_active' => $question->is_active,
                            'is_builtin' => $question->is_builtin,
                            'form_type' => VerificationFormQuestion::FORM_TYPE_OPTIONS[$question->form_type] ?? str($question->form_type)->headline()->toString(),
                            'input_type' => VerificationFormQuestion::INPUT_TYPE_OPTIONS[$question->input_type] ?? str($question->input_type)->headline()->toString(),
                            'sort_order' => $question->sort_order,
                        ];
                    })->all(),
                ];
            });
    }
}
