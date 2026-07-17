<?php

namespace App\Filament\Saas\Pages\Concerns;

use App\Models\VerificationFormQuestion;
use App\Support\AdminClinicScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait InteractsWithAdminVerificationQuestionLibraryOrdering
{
    public ?string $selectedSectionKey = null;

    public string $selectedTemplateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;

    public function getSelectedClinicName(): ?string
    {
        return AdminClinicScope::selectedClinic()?->clinic_name;
    }

    public function repositionQuestion(int $questionId, string $direction): void
    {
        $clinicId = AdminClinicScope::selectedClinicId();

        if (! $clinicId) {
            Notification::make()
                ->title('Select a clinic first')
                ->danger()
                ->send();

            return;
        }

        /** @var VerificationFormQuestion|null $question */
        $organizationId = AdminClinicScope::selectedClinic()?->organization_id;

        $question = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId ? (int) $organizationId : null)
            ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
            ->where('is_active', true)
            ->find($questionId);

        if (! $question) {
            Notification::make()
                ->title('Question not found')
                ->danger()
                ->send();

            return;
        }

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId ? (int) $organizationId : null)
            ->where('section_key', $question->section_key)
            ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
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

    public function getQuestionSections(): Collection
    {
        $clinicId = AdminClinicScope::selectedClinicId();

        if (! $clinicId) {
            return collect();
        }

        $organizationId = AdminClinicScope::selectedClinic()?->organization_id;

        $questions = VerificationFormQuestion::query()
            ->visibleForClinic($clinicId, $organizationId ? (int) $organizationId : null)
            ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
            ->where('is_active', true)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key');

        return collect(VerificationFormQuestion::sectionOptionsForTemplate(VerificationFormQuestion::DEFAULT_TEMPLATE_KEY, $clinicId))
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
