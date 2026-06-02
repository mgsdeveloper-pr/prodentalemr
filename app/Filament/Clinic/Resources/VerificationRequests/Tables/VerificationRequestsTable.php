<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Tables;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\BillingWorkItem;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class VerificationRequestsTable
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
                TextColumn::make('patient_display')
                    ->label('Patient')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<div style="display:flex;flex-direction:column;gap:2px;min-width:140px;">'
                        . '<span style="font-weight:700;color:#0f172a;">' . e($record->patient?->full_name ?: ($record->verificationProfile?->patient_full_name ?: '-')) . '</span>'
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
                TextColumn::make('insurance_provider_display')
                    ->label('Insurance provider')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<div style="display:flex;flex-direction:column;gap:2px;min-width:170px;">'
                        . '<span style="font-weight:700;color:#0f172a;">' . e($record->insurancePolicy?->insurance_company ?: ($record->verificationPlanSnapshots->first()?->payer_name ?: '-')) . '</span>'
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
                TextColumn::make('appointment_display')
                    ->label('Appointment date')
                    ->state(fn (BillingWorkItem $record): ?string => $record->appointment?->appointment_date?->toDateString() ?: $record->verificationProfile?->appointment_date)
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
                TextColumn::make('assigned_user_display')
                    ->label('Assigned user')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#f8fafc;border:1px solid #dbe4ee;color:#334155;font-size:12px;font-weight:700;white-space:nowrap;">'
                        . e(static::assignedUserLabel($record))
                        . '</span>'
                    ))
                    ->alignCenter()
                    ->html()
                    ->placeholder('-'),
                TextColumn::make('workflow_actions')
                    ->label('Actions')
                    ->state(function (BillingWorkItem $record): HtmlString {
                        $user = auth()->user();
                        $buttonStyle = 'display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:999px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #dbe4ee;background:#ffffff;color:#334155;box-shadow:0 2px 6px rgba(15,23,42,0.05);';
                        $primaryStyle = 'display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border-radius:999px;font-size:12px;font-weight:700;text-decoration:none;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;box-shadow:0 4px 10px rgba(29,78,216,0.10);';

                        $actions = [];

                        if ($record->clinicUserCanEditVerification($user) && $record->normalized_status === 'pending') {
                            $actions[] = '<a href="' . route('clinic.verification-requests.start', $record) . '" style="' . $primaryStyle . '">Start</a>';
                        }

                        $actions[] = '<a href="' . VerificationRequestResource::getUrl('view', ['record' => $record]) . '" style="' . $buttonStyle . '">View</a>';

                        if ($record->clinicUserCanEditVerification($user) && $record->normalized_status !== 'done') {
                            $actions[] = '<a href="' . VerificationRequestResource::getUrl('edit', ['record' => $record]) . '" style="' . $buttonStyle . '">Edit</a>';
                        }

                        return new HtmlString('<div style="display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:8px;min-width:210px;margin-inline:auto;">' . implode('', $actions) . '</div>');
                    })
                    ->alignCenter()
                    ->html(),
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
                TextColumn::make('ownership_display')
                    ->label('Ownership')
                    ->getStateUsing(fn (BillingWorkItem $record): string => static::ownershipLabel($record))
                    ->description(fn (BillingWorkItem $record): string => static::ownerName($record))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->state(fn (BillingWorkItem $record) => $record->updated_at)
                    ->dateTime('d-M-Y h:i A')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('ownership')
                    ->label('Ownership')
                    ->options([
                        'clinic' => 'Clinic',
                        'service' => 'Service',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'clinic' => $query->where('source', 'clinic_self_service'),
                            'service' => $query->where('source', 'clinic_request'),
                            default => $query,
                        };
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
                SelectFilter::make('writeback_status')
                    ->label('Automated Writeback')
                    ->options(BillingWorkItem::WRITEBACK_STATUS_OPTIONS),
            ])
            ->defaultSort('due_at', 'asc');
    }

    protected static function assignedUserLabel(BillingWorkItem $record): string
    {
        if ($record->source === 'clinic_request') {
            return $record->assignedTo?->name ?: 'Unassigned';
        }

        return $record->verificationProfile?->requested_by_name
            ?: $record->creator?->name
            ?: 'Clinic Team';
    }

    protected static function ownershipLabel(BillingWorkItem $record): string
    {
        return $record->source === 'clinic_request'
            ? 'Service'
            : 'Clinic';
    }

    protected static function ownerName(BillingWorkItem $record): string
    {
        if ($record->source === 'clinic_request') {
            return $record->assignedTo?->name ?: 'Pending Assignment';
        }

        return $record->verificationProfile?->requested_by_name
            ?: $record->creator?->name
            ?: 'Clinic Team';
    }
}
