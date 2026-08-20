<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVerificationRequests extends ListRecords
{
    protected static string $resource = VerificationRequestResource::class;

    public function getSubheading(): ?string
    {
        return 'Review, assign, and complete insurance verification work.';
    }

    public function getBreadcrumbs(): array
    {
        return [
            Filament::getUrl() => 'Verification',
            'Verification Requests',
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $preset = request()->query('queue_preset');

        if (! filled($preset)) {
            return;
        }

        $this->tableFilters = match ($preset) {
            'pending_unassigned' => [
                'queue_view' => ['value' => 'pending_unassigned'],
            ],
            'unassigned' => [
                'queue_view' => ['value' => 'unassigned'],
            ],
            'in_progress' => [
                'queue_view' => ['value' => 'in_progress'],
            ],
            'waiting_clinic' => [
                'queue_view' => ['value' => 'waiting_clinic'],
            ],
            'ready_for_audit' => [
                'queue_view' => ['value' => 'ready_for_audit'],
            ],
            'urgent_requests' => [
                'priority' => ['value' => 'urgent'],
            ],
            'due_today' => [
                'queue_view' => ['value' => 'due_today'],
            ],
            'overdue' => [
                'queue_view' => ['value' => 'overdue'],
            ],
            default => $this->tableFilters,
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->url(VerificationRequestResource::getUrl('import')),
            CreateAction::make()
                ->label('New Request')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return VerificationRequestResource::getEloquentQuery()
            ->without([
                'reviewedBy',
                'insuranceClaim',
                'appointment',
                'workNotes.user',
                'attachments',
                'activities.user',
            ]);
    }
}
