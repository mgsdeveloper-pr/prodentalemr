<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Saas\Resources\Verifications\Pages\ViewVerificationWorkItem;
use App\Models\BillingWorkItemAttachment;
use Filament\Actions\Action;

class ViewVerificationRequest extends ViewVerificationWorkItem
{
    protected static string $resource = VerificationRequestResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        $actions[] = Action::make('downloadPdf')
            ->label('Download PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->url(fn (): string => $this->buildClinicPdfDownloadUrl());

        $actions[] = Action::make('viewPdf')
            ->label('View PDF')
            ->icon('heroicon-o-document-magnifying-glass')
            ->url(fn (): string => $this->buildClinicPdfPreviewUrl())
            ->openUrlInNewTab();

        if (VerificationRequestResource::canEdit($this->record)) {
            $actions[] = Action::make('edit')
                ->label(match ($this->record->normalized_status) {
                    \App\Models\BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => 'Respond to request',
                    \App\Models\BillingWorkItem::STATUS_REVIEW,
                    \App\Models\BillingWorkItem::STATUS_DONE => 'Request correction',
                    default => 'Open verification form',
                })
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => VerificationRequestResource::getUrl('edit', ['record' => $this->record]));
        }

        $actions[] = Action::make('queue')
            ->label('Back to queue')
            ->icon('heroicon-o-arrow-left')
            ->url(fn (): string => VerificationRequestResource::getUrl('index'));

        return $actions;
    }

    protected function buildClinicPdfDownloadUrl(): string
    {
        return route('clinic.verification-requests.pdf.download', $this->record);
    }

    protected function buildClinicPdfPreviewUrl(): string
    {
        return route('clinic.verification-requests.pdf.preview', $this->record);
    }

    public function getAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('clinic.billing-work-item-attachments.download', $attachment);
    }

    protected function getViewPanel(): string
    {
        return 'clinic';
    }
}
