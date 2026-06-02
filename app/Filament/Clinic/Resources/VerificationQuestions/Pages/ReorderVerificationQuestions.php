<?php

namespace App\Filament\Clinic\Resources\VerificationQuestions\Pages;

use App\Filament\Clinic\Pages\Concerns\InteractsWithVerificationQuestionLibraryOrdering;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use Filament\Resources\Pages\Page;

class ReorderVerificationQuestions extends Page
{
    use InteractsWithVerificationQuestionLibraryOrdering;

    protected static string $resource = VerificationQuestionResource::class;

    protected string $view = 'filament.clinic.resources.verification-questions.pages.reorder-verification-questions';

    public function getListUrl(): string
    {
        return VerificationQuestionResource::getUrl('index');
    }
}
