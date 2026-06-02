<?php

namespace App\Filament\Saas\Resources\Verifications\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVerificationWorkItems extends ListRecords
{
    protected static string $resource = VerificationWorkItemResource::class;

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
            CreateAction::make()
                ->label('New verification request'),
            Action::make('import')
                ->label('Import verification requests')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(VerificationWorkItemResource::getUrl('import')),
        ];
    }
}
