<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\Pages\Concerns\InteractsWithVerificationWorkbench;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Services\Verification\StatusService;
use App\Services\Verification\WorkflowService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewVerificationRequest extends ViewRecord
{
    use InteractsWithVerificationWorkbench;

    protected static string $resource = VerificationRequestResource::class;

    protected string $view = 'filament.saas.resources.verifications.pages.view-verification-request';

    public ?string $returnToQueue = null;

    public function getTitle(): string
    {
        return $this->record?->normalized_status === BillingWorkItem::STATUS_DONE
            ? 'Verification Result'
            : 'Verification Request';
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->returnToQueue = $this->resolveReturnToQueueUrl(request()->query('return'));

        $this->record->recordActivity('verification_detail_viewed', 'Verification detail view opened.', [
            'panel' => $this->getViewPanel(),
            'user_name' => auth()->user()?->name,
            'status' => $this->record->normalized_status,
        ]);
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('queue')
                ->label('Back to Queue')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => $this->getQueueUrl()),
            $this->getPdfOutputActionGroup(),
            Action::make('downloadAuditTrail')
                ->label('Audit Trail')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(fn (): string => route('admin.verifications.audit.download', $this->record)),
        ];

        $canReopen = app(StatusService::class)->canShowReopen($this->record, auth()->user());

        if (! $canReopen && $this->record->normalized_status !== BillingWorkItem::STATUS_DONE && VerificationRequestResource::canEdit($this->record)) {
            $actions[] = Action::make('edit')
                ->label('Open Verification Console')
                ->icon('heroicon-o-pencil-square')
                ->url(fn (): string => VerificationRequestResource::getUrl('edit', [
                    'record' => $this->record,
                    'return' => $this->getQueueUrl(),
                ]));
        }

        if ($canReopen) {
            $actions[] = $this->getReopenAction();
        }

        return $actions;
    }

    protected function getPdfOutputActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            $this->getPdfAction('previewStandardPdf', 'Preview Standard', 'standard', true),
            $this->getPdfAction('downloadStandardPdf', 'Download Standard', 'standard'),
            $this->getPdfAction('previewPortraitPdf', 'Preview Custom Portrait', 'custom_portrait', true),
            $this->getPdfAction('downloadPortraitPdf', 'Download Custom Portrait', 'custom_portrait'),
            $this->getPdfAction('previewLandscapePdf', 'Preview Custom Landscape', 'custom_landscape', true),
            $this->getPdfAction('downloadLandscapePdf', 'Download Custom Landscape', 'custom_landscape'),
        ])
            ->label('Download PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->button();
    }

    protected function getPdfAction(string $name, string $label, string $mode, bool $preview = false): Action
    {
        $action = Action::make($name)
            ->label($label)
            ->icon($preview ? 'heroicon-o-document-magnifying-glass' : 'heroicon-o-arrow-down-tray')
            ->url(fn (): string => $preview
                ? $this->buildPdfPreviewUrl($mode)
                : $this->buildPdfDownloadUrl($mode));

        return $preview ? $action->openUrlInNewTab() : $action;
    }

    protected function getReopenAction(): Action
    {
        return Action::make('reopen')
            ->label('Reopen Verification')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('primary')
            ->form([
                Textarea::make('reopen_reason')
                    ->label('Reason for reopening')
                    ->helperText('The reason will be retained in the verification timeline.')
                    ->rows(4)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->record = app(WorkflowService::class)->reopen(
                    $this->record,
                    $data['reopen_reason'],
                    auth()->user(),
                );

                Notification::make()
                    ->title('Request reopened')
                    ->body('The request is now In Progress and can be edited again.')
                    ->success()
                    ->send();

                $resource = static::getResource();
                $this->redirect($resource::getUrl('edit', [
                    'record' => $this->record,
                    'return' => $this->getQueueUrl(),
                ]));
            });
    }

    public function getQueueUrl(): string
    {
        return $this->returnToQueue ?? $this->getDefaultQueueUrl();
    }

    public function getRequestResponseUrl(): string
    {
        return route('filament.admin.pages.request-response', [
            'return' => $this->getQueueUrl(),
        ]);
    }

    public function canAccessSensitiveOutputs(): bool
    {
        return true;
    }

    public function getResultAccessMessage(): ?string
    {
        return null;
    }

    protected function getDefaultQueueUrl(): string
    {
        return VerificationRequestResource::getUrl('index');
    }

    protected function resolveReturnToQueueUrl(mixed $candidate): ?string
    {
        if (! is_string($candidate) || $candidate === '') {
            return null;
        }

        return str_starts_with($candidate, $this->getDefaultQueueUrl()) ? $candidate : null;
    }

    protected function buildPdfDownloadUrl(string $mode = 'standard', ?int $submissionId = null): string
    {
        return route('admin.verifications.pdf.download', [
            'billingWorkItem' => $this->record,
            'mode' => $mode,
            'submission_id' => $submissionId,
        ]);
    }

    protected function buildPdfPreviewUrl(string $mode = 'standard', ?int $submissionId = null): string
    {
        return route('admin.verifications.pdf.preview', [
            'billingWorkItem' => $this->record,
            'mode' => $mode,
            'submission_id' => $submissionId,
        ]);
    }

    public function getPdfDownloadUrl(string $mode = 'standard', ?int $submissionId = null): string
    {
        return $this->buildPdfDownloadUrl($mode, $submissionId);
    }

    public function getPdfPreviewUrl(string $mode = 'standard', ?int $submissionId = null): string
    {
        return $this->buildPdfPreviewUrl($mode, $submissionId);
    }

    protected function getViewPanel(): string
    {
        return 'verification';
    }
}
