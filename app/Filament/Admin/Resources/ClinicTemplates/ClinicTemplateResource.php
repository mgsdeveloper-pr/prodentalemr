<?php

namespace App\Filament\Admin\Resources\ClinicTemplates;

use App\Filament\Admin\Resources\ClinicTemplates\Pages\ManageClinicTemplate;
use App\Filament\Clinic\Resources\VerificationQuestions\VerificationQuestionResource;
use App\Support\AdminClinicScope;

class ClinicTemplateResource extends VerificationQuestionResource
{
    protected static ?string $slug = 'clinic-templates';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('index', panel: 'admin');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $clinic = AdminClinicScope::selectedClinic();

        return $user && $clinic && $user->canAccessVerificationClinic($clinic->id)
            && $user->canManageClinicTemplateSections($clinic);
    }

    public static function getPages(): array
    {
        return ['index' => ManageClinicTemplate::route('/')];
    }
}
