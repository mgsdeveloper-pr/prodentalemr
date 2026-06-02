<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Support\ClinicPanelScope;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ListVerificationQuestions extends ListRecords
{
    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.list-verification-questions';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reorderQuestions')
                ->label('Rearrange questions')
                ->url(VerificationQuestionResource::getUrl('reorder'))
                ->color('gray'),
            CreateAction::make(),
        ];
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

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_key')
            ->map(function (Collection $questions, string $sectionKey): array {
                $activeCount = $questions->where('is_active', true)->count();
                $systemCount = $questions->where('is_builtin', true)->count();

                return [
                    'key' => $sectionKey,
                    'title' => VerificationFormQuestion::SECTION_OPTIONS[$sectionKey] ?? str($sectionKey)->headline()->toString(),
                    'count' => $questions->count(),
                    'active_count' => $activeCount,
                    'system_count' => $systemCount,
                    'questions' => $questions->map(function (VerificationFormQuestion $question): array {
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
