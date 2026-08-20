<?php

namespace App\Filament\Clinic\Resources\Appointments\Tables;

use App\Filament\Clinic\Resources\VerificationRequests\Schemas\VerificationRequestForm;
use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Support\AdminClinicScope;
use App\Support\AppointmentVerificationSender;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('appointment_details')
                    ->label('Appointment Details')
                    ->html()
                    ->state(function (Appointment $record): HtmlString {
                        $patientName = $record->patient?->full_name ?? 'Unknown patient';
                        $providerName = $record->provider?->display_name ?? 'Unknown provider';
                        $locationName = $record->location?->location_name ?? 'No location assigned';
                        $initials = collect(explode(' ', $patientName))
                            ->filter()
                            ->take(2)
                            ->map(fn (string $word): string => strtoupper(substr($word, 0, 1)))
                            ->implode('');
                        $initials = $initials !== '' ? $initials : 'PT';

                        return new HtmlString(
                            '<div style="display:flex;align-items:flex-start;gap:14px;min-width:280px;">'
                                .'<div style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:999px;background:#e0e7ff;color:#4f46e5;font-size:16px;font-weight:800;flex-shrink:0;">'.e($initials).'</div>'
                                .'<div style="display:flex;flex-direction:column;gap:6px;min-width:0;">'
                                    .'<div style="font-size:14px;font-weight:800;color:#0f172a;">'.e($patientName).'</div>'
                                    .'<div style="font-size:13px;line-height:1.5;color:#64748b;">Doctor: <span style="color:#334155;font-weight:700;">'.e($providerName).'</span></div>'
                                    .'<div style="font-size:13px;line-height:1.5;color:#64748b;">Clinic: <span style="color:#334155;font-weight:700;">'.e($locationName).'</span></div>'
                                .'</div>'
                            .'</div>'
                        );
                    })
                    ->searchable(query: function ($query, string $search): void {
                        $query->where(function ($builder) use ($search): void {
                            $builder->whereHas('patient', function ($patientQuery) use ($search): void {
                                $patientQuery
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })->orWhereHas('provider.user', function ($userQuery) use ($search): void {
                                $userQuery->where('name', 'like', "%{$search}%");
                            })->orWhereHas('location', function ($locationQuery) use ($search): void {
                                $locationQuery->where('location_name', 'like', "%{$search}%");
                            })->orWhere('appointment_type', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('date_time')
                    ->label('Date and Time')
                    ->html()
                    ->state(function (Appointment $record): HtmlString {
                        $date = $record->appointment_date?->format('M d, Y') ?? '-';
                        $start = $record->start_time ? date('g:i a', strtotime((string) $record->start_time)) : '-';
                        $end = $record->end_time ? date('g:i a', strtotime((string) $record->end_time)) : '-';

                        return new HtmlString(
                            '<div style="display:flex;flex-direction:column;gap:8px;min-width:190px;">'
                                .'<div style="font-size:14px;font-weight:800;color:#0f172a;">'.e($date).'</div>'
                                .'<div style="font-size:13px;color:#64748b;">'.e($start).' - '.e($end).'</div>'
                            .'</div>'
                        );
                    })
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('appointment_date', $direction)
                        ->orderBy('start_time', $direction)),
                TextColumn::make('clinicService.name')
                    ->label('Service')
                    ->state(fn (Appointment $record): string => $record->clinicService?->name ?: $record->appointment_type ?: 'General Appointment')
                    ->description(fn (Appointment $record): string => collect([
                        $record->clinicService?->service_code,
                        $record->operatory?->name ? 'Operatory: '.$record->operatory->name : null,
                    ])->filter()->implode(' | ') ?: 'Operatory not assigned')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'confirmed', 'checked_in', 'in_chair' => 'info',
                        'cancelled', 'no_show' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? str($state)->replace('_', ' ')->title()->toString() : '-'),
                TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->alignCenter()
                    ->state(fn (Appointment $record): string => $record->verification_status ?: Appointment::VERIFICATION_STATUS_NOT_SENT)
                    ->formatStateUsing(fn (string $state): string => Appointment::VERIFICATION_STATUS_OPTIONS[$state] ?? 'Not Sent')
                    ->color(fn (string $state): string => match ($state) {
                        Appointment::VERIFICATION_STATUS_COMPLETED => 'success',
                        Appointment::VERIFICATION_STATUS_IN_PROGRESS => 'info',
                        Appointment::VERIFICATION_STATUS_SENT => 'warning',
                        Appointment::VERIFICATION_STATUS_NEEDS_INSURANCE => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('journey')
                    ->label('Journey')
                    ->html()
                    ->state(function (Appointment $record): HtmlString {
                        $items = array_filter([
                            $record->confirmed_at ? 'Confirmed' : null,
                            $record->checked_in_at ? 'Checked in' : null,
                            $record->seated_at ? 'In chair' : null,
                            $record->completed_at ? 'Completed' : null,
                        ]);

                        if (empty($items)) {
                            return new HtmlString('<span style="font-size:13px;color:#94a3b8;">No progress yet</span>');
                        }

                        return new HtmlString('<div style="display:flex;flex-wrap:wrap;gap:6px;">'.collect($items)->map(
                            fn (string $item): string => '<span style="display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:700;">'.e($item).'</span>'
                        )->implode('').'</div>');
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'checked_in' => 'Checked in',
                        'in_chair' => 'In chair',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No-show',
                    ]),
                SelectFilter::make('clinic_operatory_id')
                    ->label('Operatory')
                    ->relationship('operatory', 'name'),
                SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'location_name'),
                SelectFilter::make('provider_id')
                    ->label('Provider')
                    ->relationship('provider.user', 'name'),
                SelectFilter::make('verification_status')
                    ->label('Verification')
                    ->options(Appointment::VERIFICATION_STATUS_OPTIONS),
                TrashedFilter::make(),
            ])
            ->defaultSort('appointment_date', 'asc')
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton()
                    ->visible(fn (): bool => (auth()->user()?->canEditClinicAppointments() ?? false)
                        || ((auth()->user()?->canAccessVerificationWorkspace() ?? false) && filled(AdminClinicScope::selectedClinicId()))),
                Action::make('openVerification')
                    ->label('Open verification')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip('Open verification request')
                    ->visible(fn (Appointment $record): bool => filled($record->verification_work_item_id))
                    ->url(fn (Appointment $record): string => static::verificationUrl($record)),
                ActionGroup::make([
                    Action::make('sendManagedVerification')
                        ->label('Send to Managed Service')
                        ->icon('heroicon-o-paper-airplane')
                        ->visible(fn (Appointment $record): bool => static::canStartVerification($record, BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE))
                        ->action(fn (Appointment $record) => static::startVerification($record, BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE)),
                    Action::make('startClinicVerification')
                        ->label('Start Self-Managed')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn (Appointment $record): bool => static::canStartVerification($record, BillingWorkItem::PROCESSING_MODE_SELF_MANAGED))
                        ->action(fn (Appointment $record) => static::startVerification($record, BillingWorkItem::PROCESSING_MODE_SELF_MANAGED)),
                ])
                    ->icon('heroicon-o-clipboard-document-check')
                    ->iconButton()
                    ->tooltip('Create verification request')
                    ->visible(fn (Appointment $record): bool => blank($record->verification_work_item_id)
                        && (auth()->user()?->canCreateClinicVerificationRequests() ?? false)),
                DeleteAction::make()
                    ->iconButton()
                    ->visible(fn (Appointment $record): bool => (auth()->user()?->canDeleteClinicAppointments() ?? false) && ! $record->trashed()),
                RestoreAction::make()
                    ->iconButton()
                    ->visible(fn (): bool => auth()->user()?->canDeleteClinicAppointments() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicAppointments() ?? false),
                    RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->canDeleteClinicAppointments() ?? false),
                ]),
            ]);
    }

    protected static function canStartVerification(Appointment $record, string $mode): bool
    {
        if (filled($record->verification_work_item_id) || ! (auth()->user()?->canCreateClinicVerificationRequests() ?? false)) {
            return false;
        }

        return array_key_exists($mode, VerificationRequestForm::processingModeOptions(
            $record->organization_id,
            $record->clinic_id,
            $record->location_id,
        ));
    }

    protected static function startVerification(Appointment $record, string $mode)
    {
        $request = app(AppointmentVerificationSender::class)->send($record, $mode);

        Notification::make()
            ->title($mode === BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE
                ? 'Verification sent to Managed Service'
                : 'Clinic verification started')
            ->success()
            ->send();

        return redirect(static::verificationUrl($record->fresh(['verificationWorkItem']) ?? $record, $request));
    }

    protected static function verificationUrl(Appointment $record, ?BillingWorkItem $request = null): string
    {
        $request ??= $record->verificationWorkItem;

        if (! $request) {
            return VerificationRequestResource::getUrl('index');
        }

        $page = $request->clinicUserCanEditVerification(auth()->user()) ? 'edit' : 'view';

        return VerificationRequestResource::getUrl($page, ['record' => $request]);
    }
}
