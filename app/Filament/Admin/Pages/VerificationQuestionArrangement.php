<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Pages\Concerns\InteractsWithAdminVerificationQuestionLibraryOrdering;
use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class VerificationQuestionArrangement extends Page
{
    use InteractsWithAdminVerificationQuestionLibraryOrdering;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3BottomLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Insurance Verification';

    protected static ?string $navigationLabel = 'Question Arrangement';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Question Arrangement';

    protected static ?string $slug = 'verification-question-arrangement';

    protected string $view = 'filament.saas.resources.verification-form-questions.pages.reorder-verification-form-questions';

    public static function canAccess(): bool
    {
        return (bool) (
            auth()->user()?->canAccessVerificationModule('settings')
            || auth()->user()?->canAccessSaasRevenueOperations()
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageQuestions')
                ->label('Manage verification questions')
                ->url(VerificationFormQuestionResource::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function getListUrl(): string
    {
        return VerificationFormQuestionResource::getUrl('index');
    }
}
