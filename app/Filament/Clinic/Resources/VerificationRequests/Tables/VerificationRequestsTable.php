<?php

namespace App\Filament\Clinic\Resources\VerificationRequests\Tables;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\BillingWorkItem;
use App\Services\Verification\SLAService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('patient_display')
                    ->label('Patient')
                    ->state(function (BillingWorkItem $record): HtmlString {
                        $dob = $record->verificationProfile?->patient_dob ?? $record->patient?->dob;

                        return new HtmlString(
                            '<div class="pd-verification-primary-cell">'
                            . '<strong>' . e($record->patient?->full_name ?: ($record->verificationProfile?->patient_full_name ?: '-')) . '</strong>'
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
                    ->formatStateUsing(fn (?string $state): string => $state === 'urgent' ? 'Urgent' : 'Normal')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assigned_user_display')
                    ->label('Assigned user')
                    ->state(fn (BillingWorkItem $record): HtmlString => new HtmlString(
                        '<span style="display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#f8fafc;border:1px solid #dbe4ee;color:#334155;font-size:12px;font-weight:700;white-space:nowrap;">'
                        . e(static::assignedUserLabel($record))
                        . '</span>'
                    ))
                    ->alignCenter()
                    ->html()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processing_mode')
                    ->label('Handled By')
                    ->state(fn (BillingWorkItem $record): string => $record->processingModeLabel())
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Managed service' ? 'info' : 'gray')
                    ->alignCenter(),
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
            ->recordUrl(fn (BillingWorkItem $record): string => static::primaryActionUrl($record))
            ->actions([
                Action::make('openRequest')
                    ->label(fn (BillingWorkItem $record): string => static::primaryActionLabel($record))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (BillingWorkItem $record): string => static::primaryActionUrl($record)),
                ActionGroup::make([
                    Action::make('viewDetails')
                        ->label('View details')
                        ->icon('heroicon-o-eye')
                        ->url(fn (BillingWorkItem $record): string => VerificationRequestResource::getUrl('view', [
                            'record' => $record,
                            'return' => static::queueReturnUrl(),
                        ])),
                    Action::make('respond')
                        ->label('Respond')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->visible(fn (BillingWorkItem $record): bool => $record->clinicUserCanRespondToVerification(auth()->user()))
                        ->url(fn (BillingWorkItem $record): string => static::responseUrl($record)),
                    Action::make('openForm')
                        ->label('Open Form')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn (BillingWorkItem $record): bool => $record->clinicUserCanOpenVerificationForm(auth()->user()))
                        ->url(fn (BillingWorkItem $record): string => static::formUrl($record)),
                ]),
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
                    ->label('Handled by')
                    ->options([
                        BillingWorkItem::PROCESSING_MODE_SELF_MANAGED => 'Self-Managed',
                        BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE => 'Managed Service',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            BillingWorkItem::PROCESSING_MODE_SELF_MANAGED => $query->where('processing_mode', BillingWorkItem::PROCESSING_MODE_SELF_MANAGED),
                            BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE => $query->where('processing_mode', BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE),
                            default => $query,
                        };
                    }),
                SelectFilter::make('assigned_to')
                    ->label('Assignee')
                    ->relationship('assignedTo', 'name'),
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
            ])
            ->defaultSort('due_at', 'asc');
    }

    protected static function primaryActionLabel(BillingWorkItem $record): string
    {
        if ($record->clinicUserCanRespondToVerification(auth()->user())) {
            return 'Respond';
        }

        if ($record->clinicUserCanOpenVerificationForm(auth()->user())) {
            return 'Open Form';
        }

        return 'View';
    }

    protected static function primaryActionUrl(BillingWorkItem $record): string
    {
        if ($record->clinicUserCanRespondToVerification(auth()->user())) {
            return static::responseUrl($record);
        }

        if ($record->clinicUserCanOpenVerificationForm(auth()->user())) {
            return static::formUrl($record);
        }

        return VerificationRequestResource::getUrl('view', [
            'record' => $record,
            'return' => static::queueReturnUrl(),
        ]);
    }

    protected static function formUrl(BillingWorkItem $record): string
    {
        return VerificationRequestResource::getUrl('edit', [
            'record' => $record,
            'return' => static::queueReturnUrl(),
        ]);
    }

    public static function responseUrl(BillingWorkItem $record, ?string $returnUrl = null): string
    {
        return route('filament.clinic.pages.request-response', [
            'respond' => $record->getKey(),
            'return' => $returnUrl ?? static::queueReturnUrl(),
        ]);
    }

    protected static function queueReturnUrl(): string
    {
        return route('filament.clinic.resources.verification-requests.index', request()->query());
    }

    protected static function assignedUserLabel(BillingWorkItem $record): string
    {
        if ($record->isManagedServiceMode()) {
            return $record->assignedTo?->name ?: 'Unassigned';
        }

        return $record->verificationProfile?->requested_by_name
            ?: $record->creator?->name
            ?: 'Self-Managed';
    }

    protected static function ownershipLabel(BillingWorkItem $record): string
    {
        return $record->processingModeLabel();
    }

    protected static function ownerName(BillingWorkItem $record): string
    {
        if ($record->isManagedServiceMode()) {
            return $record->assignedTo?->name ?: 'Pending Assignment';
        }

        return $record->verificationProfile?->requested_by_name
            ?: $record->creator?->name
            ?: 'Self-Managed';
    }
}
