<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Pages\Concerns\InteractsWithVerificationQuestionLibraryOrdering;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationQuestionArrangement extends Page
{
    use InteractsWithVerificationQuestionLibraryOrdering;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Question Arrangement';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Question Arrangement';

    protected static ?string $slug = 'verification-question-arrangement';

    protected string $view = 'filament.clinic.resources.verification-questions.pages.reorder-verification-questions';

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageClinicVerificationSettings() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageQuestions')
                ->label('Manage verification questions')
                ->url(VerificationQuestionResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getListUrl(): string
    {
        return VerificationQuestionResource::getUrl('index');
    }
}
