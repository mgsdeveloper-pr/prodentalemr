<?php

namespace App\Filament\Clinic\Resources\Appointments\Tables;

use App\Models\Appointment;
use App\Support\AdminClinicScope;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
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
                                . '<div style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:999px;background:#e0e7ff;color:#4f46e5;font-size:16px;font-weight:800;flex-shrink:0;">' . e($initials) . '</div>'
                                . '<div style="display:flex;flex-direction:column;gap:6px;min-width:0;">'
                                    . '<div style="font-size:14px;font-weight:800;color:#0f172a;">' . e($patientName) . '</div>'
                                    . '<div style="font-size:13px;line-height:1.5;color:#64748b;">Doctor: <span style="color:#334155;font-weight:700;">' . e($providerName) . '</span></div>'
                                    . '<div style="font-size:13px;line-height:1.5;color:#64748b;">Clinic: <span style="color:#334155;font-weight:700;">' . e($locationName) . '</span></div>'
                                . '</div>'
                            . '</div>'
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
                                . '<div style="font-size:14px;font-weight:800;color:#0f172a;">' . e($date) . '</div>'
                                . '<div style="font-size:13px;color:#64748b;">' . e($start) . ' - ' . e($end) . '</div>'
                            . '</div>'
                        );
                    })
                    ->sortable(query: fn ($query, string $direction) => $query
                        ->orderBy('appointment_date', $direction)
                        ->orderBy('start_time', $direction)),
                TextColumn::make('appointment_type')
                    ->label('Service')
                    ->state(fn (Appointment $record): string => $record->appointment_type ?: 'General Appointment')
                    ->description(fn (Appointment $record): string => $record->operatory?->name ? 'Operatory: ' . $record->operatory->name : 'Operatory not assigned')
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

                        return new HtmlString('<div style="display:flex;flex-wrap:wrap;gap:6px;">' . collect($items)->map(
                            fn (string $item): string => '<span style="display:inline-flex;align-items:center;padding:5px 9px;border-radius:999px;border:1px solid #dbeafe;background:#eff6ff;color:#1d4ed8;font-size:11px;font-weight:700;">' . e($item) . '</span>'
                        )->implode('') . '</div>');
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
}
