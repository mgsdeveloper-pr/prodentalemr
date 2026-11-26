<?php

namespace App\Filament\Clinic\Pages\Concerns;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateVersion;
use App\Support\ClinicPanelScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use App\Support\VerificationTemplateVersionService;

trait InteractsWithVerificationQuestionLibraryOrdering
{
    public ?string $selectedSectionKey = null;

    public string $selectedTemplateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;

    protected function arrangementClinic(): ?Clinic
    {
        return ClinicPanelScope::selectedClinic();
    }

    public function getSelectedClinicName(): ?string
    {
        return $this->arrangementClinic()?->clinic_name;
    }

    public function selectTemplate(string $templateKey): void
    {
        if (! array_key_exists($templateKey, VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS)) {
            return;
        }

        $this->selectedTemplateKey = $templateKey;
        $this->selectedSectionKey = null;
    }

    public function getTemplateOptions(): array
    {
        return VerificationFormQuestion::ACTIVE_TEMPLATE_OPTIONS;
    }

    public function getSelectedTemplateLabel(): string
    {
        return 'Clinic Template';
    }

    public function repositionQuestion(int $questionId, string $direction): void
    {
        $clinic = $this->arrangementClinic();
        $clinicId = $clinic?->id;
        $version = VerificationQuestionResource::currentClinicWorkingVersion($clinic);

        if (! $clinicId
            || ! (auth()->user()?->canManageClinicTemplateSections($clinic) ?? false)
            || $version?->status !== VerificationTemplateVersion::STATUS_DRAFT) {
            Notification::make()
                ->title('Create a draft first')
                ->body('Published clinic template versions are read-only. Create a draft before changing question order.')
                ->danger()
                ->send();

            return;
        }

        /** @var VerificationFormQuestion|null $question */
        $organizationId = $clinic->organization_id;

        $question = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', $this->selectedTemplateKey)
            ->where('template_version_id', $version->getKey())
            ->find($questionId);

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('section_key', $question->section_key)
            ->where('template_key', $question->template_key)
            ->where('template_version_id', $version->getKey())
            ->where('is_active', true)
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

        app(VerificationTemplateVersionService::class)->reorderDraftQuestions(
            $version->id, $clinicId, $newIds, [$question->section_key], activeOnly: true,
        );

        Notification::make()
            ->title('Question order updated')
            ->success()
            ->send();
    }

    public function getQuestionSections(): Collection
    {
        $clinic = $this->arrangementClinic();
        $clinicId = $clinic?->id;

        if (! $clinicId) {
            return collect();
        }

        $organizationId = $clinic->organization_id;
        $version = VerificationQuestionResource::currentClinicWorkingVersion($clinic);

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId)
            ->where('template_key', $this->selectedTemplateKey)
            ->when($version, fn ($query) => $query->where('template_version_id', $version->getKey()))
            ->where('is_active', true)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key');

        return collect(VerificationFormQuestion::sectionOptionsForTemplate($this->selectedTemplateKey, $clinicId))
            ->map(function (string $sectionTitle, string $sectionKey) use ($questions): array {
                $sectionQuestions = $questions->get($sectionKey, collect());

                return [
                    'key' => $sectionKey,
                    'title' => $sectionTitle,
                    'count' => $sectionQuestions->count(),
                    'questions' => $sectionQuestions->map(function (VerificationFormQuestion $question): array {
                        return [
                            'id' => $question->getKey(),
                            'prompt' => $question->prompt,
                            'is_active' => $question->is_active,
                            'sort_order' => $question->sort_order,
                        ];
                    })->all(),
                ];
            });
    }

    public function getSectionFilterOptions(): array
    {
        return $this->getQuestionSections()
            ->mapWithKeys(fn (array $section): array => [
                $section['key'] => $section['title'],
            ])
            ->all();
    }

    public function getVisibleQuestionSections(): Collection
    {
        $sections = $this->getQuestionSections();

        if ($sections->isEmpty()) {
            return collect();
        }

        if (! filled($this->selectedSectionKey) || ! $sections->has($this->selectedSectionKey)) {
            $this->selectedSectionKey = (string) $sections->keys()->first();
        }

        return $sections->only([$this->selectedSectionKey]);
    }
}
