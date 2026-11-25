<?php

namespace App\Filament\Admin\Resources\ClinicTemplates\Pages;

use App\Filament\Admin\Resources\ClinicTemplates\ClinicTemplateResource;
use App\Filament\Clinic\Resources\VerificationQuestions\Pages\ListVerificationQuestions;
use App\Models\Clinic;
use App\Support\AdminClinicScope;
use App\Support\VerificationTemplateVersionService;

class ManageClinicTemplate extends ListVerificationQuestions
{
    protected static string $resource = ClinicTemplateResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return ClinicTemplateResource::canAccess();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        if ($this->selectedTemplateVersionId === null) {
            $versions = app(VerificationTemplateVersionService::class);
            $clinicId = $this->builderClinic()->id;
            $this->selectedTemplateVersionId = ($versions->findActivePublishedVersion($clinicId, formType: 'full_form')
                ?? $versions->findActivePublishedVersion($clinicId, formType: 'short_form'))?->id;
        }

        if ($this->selectedTemplateVersionId === null) {
            $this->redirect($this->templateLibraryUrl(), navigate: true);

            return;
        }

        parent::mount();
    }

    public function hydrate(): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless($this->getDisplayedClinicVersion(), 404);
    }

    protected function builderClinic(): ?Clinic
    {
        return AdminClinicScope::selectedClinic();
    }

    protected function redirectToQuestionCreate(): void
    {
        $this->editInlineQuestion();
    }

    protected function templateLibraryUrl(): string
    {
        return \App\Filament\Admin\Pages\VerificationGeneralSettings::getUrl(panel: 'admin');
    }
}
