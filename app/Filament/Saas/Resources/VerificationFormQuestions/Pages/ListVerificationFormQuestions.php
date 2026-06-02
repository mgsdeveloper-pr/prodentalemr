<?php

namespace App\Filament\Saas\Resources\VerificationFormQuestions\Pages;

use App\Filament\Admin\Pages\VerificationQuestionArrangement;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
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
            Action::make('rearrangeQuestions')
                ->label('Rearrange Questions')
                ->icon('heroicon-o-bars-3-bottom-left')
                ->color('gray')
                ->url(fn (): string => VerificationQuestionArrangement::getUrl()),
        ];
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
                    'title' => VerificationFormQuestion::SECTION_OPTIONS[$sectionKey] ?? str($sectionKey)->headline()->toString(),
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
