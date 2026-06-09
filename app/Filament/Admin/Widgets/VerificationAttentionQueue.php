<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use App\Models\User;
use App\Support\VerificationAutoAssigner;
use Filament\Actions\Action as HeaderAction;
use Filament\Actions\ActionGroup as HeaderActionGroup;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class VerificationAttentionQueue extends TableWidget
{
    protected static ?string $heading = 'Verification Attention Queue';

    protected static bool $isLazy = false;

    protected string $view = 'filament.admin.widgets.verification-attention-queue';

    protected int|string|array $columnSpan = 'full';

    public ?string $activeFilter = null;

    public function mount(): void
    {
        $filter = request()->query('attention_filter');
        $this->activeFilter = filled($filter) ? (string) $filter : null;
    }

    #[On('verification-attention-filter-changed')]
    public function setAttentionFilter(?string $filter = null): void
    {
        $this->activeFilter = filled($filter) ? (string) $filter : null;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->description($this->getTableDescription())
            ->columns([
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->state(fn (BillingWorkItem $record): string => $record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? '-'))
                    ->searchable(),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->state(fn (BillingWorkItem $record): string => $record->clinic?->clinic_name
                        ?: ($record->verificationProfile?->location_name ?: '-'))
                    ->placeholder('-'),
                TextColumn::make('priority')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('sla_status')
                    ->label('SLA')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'overdue' => 'Overdue',
                        'due_today' => 'Due Today',
                        'paused_waiting_clinic' => 'Waiting on Clinic',
                        'on_track' => 'On Track',
                        'closed' => 'Closed',
                        default => 'Not Set',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'overdue' => 'danger',
                        'due_today' => 'warning',
                        'paused_waiting_clinic' => 'gray',
                        'on_track' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                TextColumn::make('assignedTo.name')
                    ->label('Assignee')
                    ->placeholder('Unassigned'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('takeOwnership')
                        ->label('Take Ownership')
                        ->icon('heroicon-o-hand-raised')
                        ->color('info')
                        ->visible(fn (BillingWorkItem $record): bool => auth()->user()?->canManageVerificationQueue()
                            && $record->normalized_status !== BillingWorkItem::STATUS_DONE
                            && $record->assigned_to !== auth()->id())
                        ->action(function (BillingWorkItem $record): void {
                            abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                            $record->assigned_to = auth()->id();

                            if ($record->normalized_status === BillingWorkItem::STATUS_PENDING) {
                                $record->started_at ??= now();
                            }

                            $record->save();

                            Notification::make()
                                ->title('Ownership updated')
                                ->body('The request is now assigned to you.')
                                ->success()
                                ->send();
                        }),
                    Action::make('reassign')
                        ->label('Reassign')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->visible(fn (BillingWorkItem $record): bool => auth()->user()?->canManageVerificationQueue()
                            && $record->normalized_status !== BillingWorkItem::STATUS_DONE)
                        ->form(fn (BillingWorkItem $record): array => [
                            Select::make('assigned_to')
                                ->label('Assign to')
                                ->options(VerificationAutoAssigner::optionList($record->clinic_id))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->native(false),
                        ])
                        ->fillForm(fn (BillingWorkItem $record): array => [
                            'assigned_to' => $record->assigned_to,
                        ])
                        ->action(function (BillingWorkItem $record, array $data): void {
                            abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                            $record->assigned_to = $data['assigned_to'];
                            $record->save();

                            Notification::make()
                                ->title('Request reassigned')
                                ->body('The assignee has been updated successfully.')
                                ->success()
                                ->send();
                        }),
                    Action::make('returnForRework')
                        ->label('Return for Rework')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->visible(fn (BillingWorkItem $record): bool => auth()->user()?->canManageVerificationQueue()
                            && in_array($record->normalized_status, [
                                BillingWorkItem::STATUS_REVIEW,
                                BillingWorkItem::STATUS_DONE,
                            ], true)
                            && $record->canUserTransitionTo(auth()->user(), BillingWorkItem::STATUS_RETURNED_FOR_REWORK))
                        ->form([
                            Textarea::make('return_reason')
                                ->label('Rework reason')
                                ->rows(4)
                                ->required(),
                        ])
                        ->action(function (BillingWorkItem $record, array $data): void {
                            abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                            $record->return_reason = $data['return_reason'];
                            $record->transitionStatus(BillingWorkItem::STATUS_RETURNED_FOR_REWORK);

                            Notification::make()
                                ->title('Returned for rework')
                                ->body('The request has been sent back for correction.')
                                ->success()
                                ->send();
                        }),
                    Action::make('reopen')
                        ->label('Reopen')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('warning')
                        ->visible(fn (BillingWorkItem $record): bool => auth()->user()?->canManageVerificationQueue()
                            && in_array($record->normalized_status, [
                                BillingWorkItem::STATUS_DONE,
                                BillingWorkItem::STATUS_INCOMPLETE,
                            ], true)
                            && $record->canUserTransitionTo(auth()->user(), BillingWorkItem::STATUS_IN_PROGRESS))
                        ->action(function (BillingWorkItem $record): void {
                            abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                            $record->transitionStatus(BillingWorkItem::STATUS_IN_PROGRESS);

                            Notification::make()
                                ->title('Request reopened')
                                ->body('The request has been moved back to In Progress.')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Manager')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->button()
                    ->visible(fn (BillingWorkItem $record): bool => auth()->user()?->canManageVerificationQueue() ?? false),
            ])
            ->headerActions([
                HeaderActionGroup::make([
                    HeaderAction::make('exportExcel')
                        ->label('Excel')
                        ->icon(Heroicon::OutlinedTableCells)
                        ->url(fn (): string => $this->getExportUrl('excel')),
                    HeaderAction::make('exportPdf')
                        ->label('PDF')
                        ->icon(Heroicon::OutlinedDocumentArrowDown)
                        ->url(fn (): string => $this->getExportUrl('pdf')),
                ])
                    ->label('Export')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->button(),
            ])
            ->defaultSort('due_at', 'asc')
            ->recordUrl(fn (BillingWorkItem $record): string => url("/verification/verifications/{$record->getKey()}/edit"))
            ->paginated([8]);
    }

    protected function getTableHeading(): string | Htmlable | null
    {
        $label = match ($this->getActiveFilter()) {
            'new_pending' => 'New & Pending',
            'urgent_requests' => 'Urgent Requests',
            'due_today' => 'Due Today',
            'overdue' => 'Overdue SLA',
            'awaiting_clinic_response' => 'Waiting on Clinic',
            'returned_for_rework' => 'Returned for Rework',
            default => null,
        };

        if (blank($label)) {
            return 'Verification Attention Queue';
        }

        return new HtmlString("Verification Attention Queue <span style=\"color:#64748b;font-weight:700;\">&middot; {$label}</span>");
    }

    protected function getTableDescription(): ?Htmlable
    {
        $activeFilter = $this->getActiveFilter();

        if (blank($activeFilter)) {
            return null;
        }

        return new HtmlString(
            '<span style="display:inline-flex;align-items:center;gap:0.5rem;">'
            . '<span style="color:#64748b;">Filtered from the dashboard cards. Click the active card again to clear it.</span>'
            . '</span>'
        );
    }

    protected function getActiveFilter(): ?string
    {
        return $this->activeFilter;
    }

    public function getExportUrl(string $format): string
    {
        $route = match ($format) {
            'excel' => 'admin.verification-attention-queue.export.excel',
            'pdf' => 'admin.verification-attention-queue.export.pdf',
            default => null,
        };

        if (blank($route)) {
            return '#';
        }

        $parameters = array_filter([
            'attention_filter' => $this->getActiveFilter(),
        ], fn ($value) => filled($value));

        return route($route, $parameters);
    }

    protected function getTableQuery(): Builder
    {
        $query = AdminClinicScope::apply(
            BillingWorkItem::query()
            ->with(['clinic', 'assignedTo', 'patient', 'verificationProfile'])
            ->whereHas('managedBillingService', fn (Builder $query) => $query->where('category', 'verification'))
            ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, 'completed', 'cancelled'])
        );

        match ($this->getActiveFilter()) {
            'new_pending' => $query->whereIn('status', [
                BillingWorkItem::STATUS_PENDING,
                'unassigned',
            ]),
            'urgent_requests' => $query->where('priority', 'urgent'),
            'due_today' => $query->whereDate('due_at', today())->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
            'overdue' => $query->where('due_at', '<', now())->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
            'awaiting_clinic_response' => $query->whereIn('status', [
                BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
                'waiting_on_client',
                BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                'audit',
            ]),
            'returned_for_rework' => $query->where('status', BillingWorkItem::STATUS_RETURNED_FOR_REWORK),
            default => null,
        };

        return $query
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 0 WHEN priority = 'high' THEN 1 WHEN priority = 'normal' THEN 2 ELSE 3 END")
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_at');
    }
}
