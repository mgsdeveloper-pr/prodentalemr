<?php

namespace App\Filament\Saas\Resources\Verifications\Tables;

use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Support\VerificationAutoAssigner;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class VerificationWorkItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial')
                    ->label('S.No.')
                    ->rowIndex()
                    ->alignCenter(),
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('patient_name')
                    ->label('Patient')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<div style="display:flex;flex-direction:column;gap:2px;min-width:140px;">'
                        . '<span style="font-weight:700;color:#0f172a;">' . e($record->verificationProfile?->patient_full_name ?: ($record->patient?->full_name ?? '-')) . '</span>'
                        . '<span style="font-size:11px;color:#64748b;">' . e($record->reference_number ?: 'No reference') . '</span>'
                        . '</div>'
                    ))
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
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->label('Insurance provider')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<div style="display:flex;flex-direction:column;gap:2px;min-width:170px;">'
                        . '<span style="font-weight:700;color:#0f172a;">' . e($record->verificationPlanSnapshots->first()?->payer_name
                            ?? $record->insurancePolicy?->insurance_company
                            ?? '-') . '</span>'
                        . '<span style="font-size:11px;color:#64748b;">' . e(match ($record->verificationProfile?->form_type) {
                            'full_form' => 'Full Form',
                            'short_form' => 'Short Form',
                            default => 'Verification request',
                        }) . '</span>'
                        . '</div>'
                    ))
                    ->html()
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('verificationProfile.form_type')
                    ->label('Type')
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
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->formatStateUsing(fn (?string $state): string => $state === 'urgent' ? 'Urgent' : 'Normal'),
                TextColumn::make('assignedTo.name')
                    ->label('Assigned user')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#f8fafc;border:1px solid #dbe4ee;color:#334155;font-size:12px;font-weight:700;white-space:nowrap;">'
                        . e($record->assignedTo?->name ?: 'Unassigned')
                        . '</span>'
                    ))
                    ->alignCenter()
                    ->html()
                    ->placeholder('-'),
                TextColumn::make('workflow_actions')
                    ->label('Actions')
                    ->state(function (BillingWorkItem $record): HtmlString {
                        $buttonStyle = 'display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:999px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #dbe4ee;background:#ffffff;color:#334155;box-shadow:0 2px 6px rgba(15,23,42,0.05);';
                        $primaryStyle = 'display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:999px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;box-shadow:0 4px 10px rgba(29,78,216,0.10);';

                        $actions = [];

                        if ($record->normalized_status === 'pending') {
                            $actions[] = '<a href="' . route('admin.verifications.start', $record) . '" style="' . $primaryStyle . '">Start</a>';
                        }

                        $actions[] = '<a href="' . VerificationWorkItemResource::getUrl('view', ['record' => $record]) . '" style="' . $buttonStyle . '">View</a>';

                        if ($record->normalized_status !== 'done') {
                            $actions[] = '<a href="' . VerificationWorkItemResource::getUrl('edit', ['record' => $record]) . '" style="' . $buttonStyle . '">Edit</a>';
                        }

                        return new HtmlString('<div style="display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:8px;min-width:210px;margin-inline:auto;">' . implode('', $actions) . '</div>');
                    })
                    ->alignCenter()
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
                    ->label('System View')
                    ->options([
                        'pending_unassigned' => 'Pending & Unassigned',
                        'due_today' => 'Due Today',
                        'overdue' => 'Overdue',
                        'verified' => 'Verified',
                        'not_synced' => 'Not Synced',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'pending_unassigned' => $query->where('status', BillingWorkItem::STATUS_PENDING)->whereNull('assigned_to'),
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
            ->defaultSort('created_at', 'desc');
    }
}
