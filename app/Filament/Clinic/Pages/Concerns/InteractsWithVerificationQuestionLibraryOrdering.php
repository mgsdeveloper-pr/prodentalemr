<?php

namespace App\Filament\Clinic\Pages\Concerns;

use App\Models\VerificationFormQuestion;
use App\Support\ClinicPanelScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait InteractsWithVerificationQuestionLibraryOrdering
{
    public ?string $selectedSectionKey = null;

    public function getSelectedClinicName(): ?string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name;
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

    public function getQuestionSections(): Collection
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            return collect();
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key')
            ->map(function (Collection $questions, string $sectionKey): array {
                return [
                    'key' => $sectionKey,
                    'title' => VerificationFormQuestion::SECTION_OPTIONS[$sectionKey] ?? str($sectionKey)->headline()->toString(),
                    'count' => $questions->count(),
                    'questions' => $questions->map(function (VerificationFormQuestion $question): array {
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
