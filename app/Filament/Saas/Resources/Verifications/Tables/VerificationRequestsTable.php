<?php

namespace App\Filament\Saas\Resources\Verifications\Tables;

use App\Actions\Verification\AssignVerificationRequestAction;
use App\Actions\Verification\ReturnVerificationForCorrectionAction;
use App\Actions\Verification\TakeVerificationOwnershipAction;
use App\Filament\Saas\Resources\Verifications\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Services\Verification\AssignmentService;
use App\Services\Verification\SLAService;
use App\Services\Verification\StatusService;
use App\Services\Verification\WorkflowService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class VerificationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->header(view('filament.saas.resources.verifications.pages.partials.verification-queue-header'))
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->state(function (BillingWorkItem $record): HtmlString {
                        $dob = $record->verificationProfile?->patient_dob ?? $record->patient?->dob;

                        return new HtmlString(
                            '<div class="pd-verification-primary-cell">'
                            . '<strong>' . e($record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? '-')) . '</strong>'
                            . '<span>' . e($dob ? 'DOB ' . $dob->format('m/d/Y') : 'DOB not available') . '</span>'
                            . '</div>'
                        );
                    })
                    ->html()
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($innerQuery) use ($search): void {
                            $innerQuery->whereHas('patient', function ($patientQuery) use ($search): void {
                                $patientQuery
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('pms_patient_id', 'like', "%{$search}%");
                            })->orWhereHas('verificationProfile', function ($profileQuery) use ($search): void {
                                $profileQuery
                                    ->where('patient_full_name', 'like', "%{$search}%")
                                    ->orWhere('patient_identifier', 'like', "%{$search}%")
                                    ->orWhere('pms_id', 'like', "%{$search}%");
                            });
                        });
                    }),
                TextColumn::make('clinic.clinic_name')
                    ->label('Clinic')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<div class="pd-verification-primary-cell">'
                        . '<strong>' . e($record->clinic?->clinic_name ?: '-') . '</strong>'
                        . '<span>' . e($record->location?->location_name ?: ($record->verificationProfile?->location_name ?: 'Primary location')) . '</span>'
                        . '</div>'
                    ))
                    ->html()
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('location.location_name')
                    ->label('Location')
                    ->state(fn (BillingWorkItem $record): string => $record->location?->location_name ?: ($record->verificationProfile?->location_name ?: '-'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provider.user.name')
                    ->label('Provider')
                    ->state(fn (BillingWorkItem $record): string => $record->provider?->display_name ?: ($record->verificationProfile?->provider_name ?: '-'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('insurance_provider')
                    ->label('Insurance')
                    ->state(function (BillingWorkItem $record): HtmlString {
                        $snapshot = $record->verificationPlanSnapshots->first();
                        $memberId = $snapshot?->member_id ?: $record->insurancePolicy?->member_id;
                        $maskedMemberId = filled($memberId)
                            ? 'Member •••• ' . substr((string) $memberId, -4)
                            : 'Member ID not available';

                        return new HtmlString(
                            '<div class="pd-verification-primary-cell">'
                            . '<strong>' . e($snapshot?->payer_name ?? $record->insurancePolicy?->insurance_company ?? '-') . '</strong>'
                            . '<span>' . e($maskedMemberId) . '</span>'
                            . '</div>'
                        );
                    })
                    ->html()
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('verificationProfile.form_type')
                    ->label('Form Type')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'full_form' => 'info',
                        'short_form' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'full_form' => 'Full Form',
                        'short_form' => 'Short Form',
                        default => '-',
                    })
                    ->alignCenter(),
                TextColumn::make('verificationProfile.appointment_date')
                    ->label('Appointment date')
                    ->date()
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (?string $state): string => $state === 'urgent' ? 'danger' : 'info')
                    ->formatStateUsing(fn (?string $state): string => $state === 'urgent' ? 'Urgent' : 'Normal')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('normalized_status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (string $state): string => match ($state) {
                        BillingWorkItem::STATUS_PENDING => 'warning',
                        BillingWorkItem::STATUS_IN_PROGRESS => 'info',
                        BillingWorkItem::STATUS_REVIEW => 'primary',
                        BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => 'warning',
                        BillingWorkItem::STATUS_RETURNED_FOR_REWORK => 'danger',
                        BillingWorkItem::STATUS_INCOMPLETE => 'gray',
                        BillingWorkItem::STATUS_DONE => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => BillingWorkItem::STATUS_OPTIONS[$state] ?? str($state)->headline()->toString()),
                TextColumn::make('due_at')
                    ->label('Due / SLA')
                    ->state(function (BillingWorkItem $record): HtmlString {
                        $sla = app(SLAService::class)->snapshot($record);

                        return new HtmlString(
                            '<div class="pd-verification-sla pd-verification-sla--' . e($sla['status']) . '">'
                            . '<strong>' . e($sla['due_at']) . '</strong>'
                            . '<span>' . e($sla['relative']) . '</span>'
                            . '</div>'
                        );
                    })
                    ->html(),
                TextColumn::make('sla_status')
                    ->label('SLA')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (string $state): string => match ($state) {
                        'overdue' => 'danger',
                        'due_today' => 'warning',
                        'on_track' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'overdue' => 'Overdue',
                        'due_today' => 'Due Today',
                        'paused_waiting_clinic' => 'Waiting on Clinic',
                        'on_track' => 'On Track',
                        'closed' => 'Closed',
                        default => 'Not Set',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assignedTo.name')
                    ->label('Assignee')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<span class="pd-verification-assignee">'
                        . '<i>' . e(str($record->assignedTo?->name ?: 'NA')->substr(0, 2)->upper()->toString()) . '</i>'
                        . '<span>' . e($record->assignedTo?->name ?: 'Unassigned') . '</span>'
                        . '</span>'
                    ))
                    ->alignCenter()
                    ->html()
                    ->placeholder('-'),
                TextColumn::make('outcome_status')
                    ->label('Verification')
                    ->badge()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pms_sync_status')
                    ->label('PMS sync')
                    ->badge()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Timestamp')
                    ->dateTime('d-M-Y h:i A')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (BillingWorkItem $record): string => static::requestUrl($record))
            ->actions([
                Action::make('openRequest')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (BillingWorkItem $record): string => static::requestUrl($record)),
                ActionGroup::make([
                    Action::make('view')
                        ->label('View details')
                        ->icon('heroicon-o-eye')
                        ->url(fn (BillingWorkItem $record): string => VerificationRequestResource::getUrl('view', [
                            'record' => $record,
                            'return' => VerificationRequestResource::getUrl('index', request()->query()),
                        ])),
                    Action::make('startWork')
                        ->label('Start')
                        ->icon('heroicon-o-play')
                        ->color('info')
                        ->visible(fn (BillingWorkItem $record): bool => app(StatusService::class)->canShowStartWork($record, auth()->user()))
                        ->action(function (BillingWorkItem $record): void {
                            app(WorkflowService::class)->start($record, auth()->user());

                            Notification::make()
                                ->title('Request started')
                                ->body('The request has moved to In Progress.')
                                ->success()
                                ->send();
                        }),
                    Action::make('approveQa')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (BillingWorkItem $record): bool => app(StatusService::class)->canShowMarkDone($record, auth()->user()))
                        ->action(function (BillingWorkItem $record): void {
                            app(WorkflowService::class)->approveQa($record, auth()->user());

                            Notification::make()
                                ->title('Audit approved')
                                ->body('The request has been marked complete.')
                                ->success()
                                ->send();
                        }),
                    Action::make('takeOwnership')
                        ->label('Take Ownership')
                        ->icon('heroicon-o-hand-raised')
                        ->color('info')
                        ->visible(fn (BillingWorkItem $record): bool => app(StatusService::class)->canShowTakeOwnership($record, auth()->user()))
                        ->action(function (BillingWorkItem $record): void {
                            abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                            app(TakeVerificationOwnershipAction::class)->execute($record, auth()->user());

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
                        ->visible(fn (BillingWorkItem $record): bool => app(StatusService::class)->canShowReassign($record, auth()->user()))
                        ->form(fn (BillingWorkItem $record): array => [
                            Select::make('assigned_to')
                                ->label('Assign to')
                                ->options(fn (): array => app(AssignmentService::class)->options($record->clinic_id))
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

                            app(AssignVerificationRequestAction::class)->execute($record, $data['assigned_to'], auth()->user());

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
                        ->visible(fn (BillingWorkItem $record): bool => app(StatusService::class)->canShowReturnForRework($record, auth()->user()))
                        ->form([
                            Textarea::make('return_reason')
                                ->label('Rework reason')
                                ->rows(4)
                                ->required(),
                        ])
                        ->action(function (BillingWorkItem $record, array $data): void {
                            abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                            app(ReturnVerificationForCorrectionAction::class)->execute($record, $data['return_reason'], auth()->user());

                            Notification::make()
                                ->title('Returned for rework')
                                ->body('The request has been sent back for correction.')
                                ->success()
                                ->send();
                        }),
                    Action::make('reopen')
                        ->label('Reopen')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('gray')
                        ->outlined()
                        ->visible(fn (BillingWorkItem $record): bool => app(StatusService::class)->canShowReopen($record, auth()->user()))
                        ->form([
                            Textarea::make('reopen_reason')
                                ->label('Reason for reopening')
                                ->helperText('This reason will be retained in the verification timeline.')
                                ->rows(4)
                                ->required(),
                        ])
                        ->action(function (BillingWorkItem $record, array $data): void {
                            abort_unless(auth()->user()?->canManageVerificationQueue(), 403);

                            app(WorkflowService::class)->reopen($record, $data['reopen_reason'], auth()->user());

                            Notification::make()
                                ->title('Request reopened')
                                ->body('The request has been moved back to In Progress.')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('More actions')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip('More actions')
                    ->visible(fn (BillingWorkItem $record): bool => auth()->user()?->canManageVerificationQueue() ?? false),
            ])
            ->filters([
                Filter::make('appointment_date_range')
                    ->schema([
                        DatePicker::make('from')->label('Appointment from')->native(false),
                        DatePicker::make('until')->label('Appointment to')->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($builder, $date) => $builder->whereHas('verificationProfile', fn ($profileQuery) => $profileQuery->whereDate('appointment_date', '>=', $date)))
                            ->when($data['until'] ?? null, fn ($builder, $date) => $builder->whereHas('verificationProfile', fn ($profileQuery) => $profileQuery->whereDate('appointment_date', '<=', $date)));
                    }),
                SelectFilter::make('status')
                    ->options(BillingWorkItem::STATUS_OPTIONS),
                SelectFilter::make('outcome_status')
                    ->label('Verification status')
                    ->options(BillingWorkItem::OUTCOME_STATUS_OPTIONS),
                SelectFilter::make('priority')
                    ->label('Work priority')
                    ->options(BillingWorkItem::PRIORITY_OPTIONS),
                SelectFilter::make('form_type')
                    ->label('Type')
                    ->options([
                        'full_form' => 'Full Form',
                        'short_form' => 'Short Form',
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'] ?? null,
                            fn ($builder, $value) => $builder->whereHas('verificationProfile', fn ($profileQuery) => $profileQuery->where('form_type', $value))
                        );
                    }),
                SelectFilter::make('pms_sync_status')
                    ->label('PMS sync')
                    ->options(BillingWorkItem::PMS_SYNC_STATUS_OPTIONS),
                SelectFilter::make('assigned_to')
                    ->label('User filter')
                    ->relationship('assignedTo', 'name'),
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'name'),
                SelectFilter::make('writeback_status')
                    ->label('Automated Writeback')
                    ->options(BillingWorkItem::WRITEBACK_STATUS_OPTIONS),
                SelectFilter::make('queue_view')
                    ->label('Queue')
                    ->options([
                        'pending_unassigned' => 'Pending & Unassigned',
                        'unassigned' => 'Unassigned',
                        'in_progress' => 'In Progress',
                        'waiting_clinic' => 'Waiting on Clinic',
                        'ready_for_audit' => 'Ready for Audit',
                        'due_today' => 'Due Today',
                        'overdue' => 'Overdue',
                        'verified' => 'Verified',
                        'not_synced' => 'Not Synced',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'pending_unassigned' => $query->where('status', BillingWorkItem::STATUS_PENDING)->whereNull('assigned_to'),
                            'unassigned' => $query->whereNull('assigned_to')->where('status', '!=', BillingWorkItem::STATUS_DONE),
                            'in_progress' => $query->where('status', BillingWorkItem::STATUS_IN_PROGRESS),
                            'waiting_clinic' => $query->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
                            'ready_for_audit' => $query->where('status', BillingWorkItem::STATUS_REVIEW),
                            'due_today' => $query->whereDate('due_at', today())->where('status', '!=', BillingWorkItem::STATUS_DONE)->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
                            'overdue' => $query->where('due_at', '<', now())->where('status', '!=', BillingWorkItem::STATUS_DONE)->where('status', '!=', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
                            'verified' => $query->where('outcome_status', 'verified'),
                            'not_synced' => $query->where('pms_sync_status', '!=', 'synced'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('managed_billing_service_id')
                    ->label('Verification service')
                    ->relationship('managedBillingService', 'name'),
            ])
            ->filtersLayout(FiltersLayout::Dropdown)
            ->filtersFormColumns([
                'default' => 1,
                'md' => 2,
                'xl' => 4,
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No verification requests found')
            ->emptyStateDescription('Adjust the compact filters or create a new verification request.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->defaultSort('created_at', 'desc');
    }

    public static function requestUrl(BillingWorkItem $record, ?string $returnUrl = null): string
    {
        $returnUrl ??= VerificationRequestResource::getUrl('index', request()->query());
        $page = $record->normalized_status === BillingWorkItem::STATUS_DONE ? 'view' : 'edit';

        return VerificationRequestResource::getUrl($page, [
            'record' => $record,
            'return' => $returnUrl,
        ]);
    }
}
