<?php

namespace App\Filament\Clinic\Pages;

use App\Support\ClinicWorkspace;
use App\Support\ClinicPanelScope;
use App\Support\DocumentCenter as DocumentCenterData;
use App\Support\SaasEntitlements;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class DocumentCenter extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string | UnitEnum | null $navigationGroup = 'Verifications';

    protected static ?string $navigationLabel = 'Document Center';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = '';

    protected static ?string $slug = 'document-center';

    protected string $view = 'filament.shared.pages.document-center';

    public string $search = '';

    public string $typeFilter = 'all';

    public string $dateFilter = 'all';

    public ?string $selectedDocumentId = null;

    public bool $showPreviewModal = false;

    public static function canAccess(): bool
    {
        return ClinicWorkspace::selected() === ClinicWorkspace::VERIFICATION
            && (auth()->user()?->canEditClinicVerificationRequests() ?? false)
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'document_center', ClinicPanelScope::selectedClinic());
    }

    public function getPanelMode(): string
    {
        return 'clinic';
    }

    public function getRows(): Collection
    {
        return DocumentCenterData::rows('clinic', $this->search, $this->typeFilter, $this->dateFilter);
    }

    public function openPreview(string $documentId): void
    {
        $this->selectedDocumentId = $documentId;
        $this->showPreviewModal = true;
    }

    public function closePreview(): void
    {
        $this->showPreviewModal = false;
        $this->selectedDocumentId = null;
    }

    public function getSelectedDocument(): ?array
    {
        if (! $this->selectedDocumentId) {
            return null;
        }

        return $this->getRows()
            ->first(fn (array $row): bool => $row['id'] === $this->selectedDocumentId);
    }

    public function getStats(): array
    {
        return DocumentCenterData::stats('clinic');
    }

    public function getTypeOptions(): array
    {
        return [
            'all' => 'All Documents',
            'verification' => 'Verification Files',
            'patient' => 'Patient Documents',
        ];
    }
}
