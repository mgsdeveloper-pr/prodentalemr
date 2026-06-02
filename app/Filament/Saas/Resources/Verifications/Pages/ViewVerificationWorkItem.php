<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\Pages\Concerns\InteractsWithVerificationWorkbench;
use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewVerificationWorkItem extends ViewRecord
{
    use InteractsWithVerificationWorkbench;

    protected static string $resource = VerificationWorkItemResource::class;

    protected string $view = 'filament.saas.resources.verifications.pages.view-verification-work-item';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->recordActivity('verification_detail_viewed', 'Verification detail view opened.', [
            'panel' => $this->getViewPanel(),
            'user_name' => auth()->user()?->name,
            'status' => $this->record->normalized_status,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => $this->buildPdfDownloadUrl()),
            Action::make('downloadAuditTrail')
                ->label('Download Audit Trail')
                ->icon('heroicon-o-clipboard-document-list')
                ->url(fn (): string => route('admin.verifications.audit.download', $this->record)),
            Action::make('viewPdf')
                ->label('View PDF')
                ->icon('heroicon-o-document-magnifying-glass')
                ->url(fn (): string => $this->buildPdfPreviewUrl())
                ->openUrlInNewTab(),
            Action::make('edit')
                ->label('Open verification console')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => VerificationWorkItemResource::getUrl('edit', ['record' => $this->record])),
            Action::make('queue')
                ->label('Back to queue')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => VerificationWorkItemResource::getUrl('index')),
        ];
    }

    protected function buildPdfDownloadUrl(): string
    {
        return route('admin.verifications.pdf.download', $this->record);
    }

    protected function buildPdfPreviewUrl(): string
    {
        return route('admin.verifications.pdf.preview', $this->record);
    }

    protected function getViewPanel(): string
    {
        return 'verification';
    }
}
