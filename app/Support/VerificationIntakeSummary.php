<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Provider;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\View\View;

class VerificationIntakeSummary
{
    private static function dateLabel(?string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);

        return $date ? $date->format('M d, Y') : (string) $value;
    }

    public static function isReady(Get $get): bool
    {
        foreach (['appointment_id', 'location_id', 'provider_id', 'vf_patient_full_name', 'vf_patient_dob', 'vf_appointment_date'] as $field) {
            if (blank($get($field))) {
                return false;
            }
        }

        return true;
    }

    public static function showsSummary(Get $get): bool
    {
        return static::isReady($get) && ! $get('edit_imported_details');
    }

    public static function components(bool $clinicPanel = false): array
    {
        return [
            Checkbox::make('edit_imported_details')
                ->label('Edit imported details')->default(false)->live()->dehydrated(false)
                ->visible(fn (Get $get): bool => static::isReady($get)),
            Placeholder::make('imported_details_summary')->hiddenLabel()->columnSpanFull()
                ->visible(fn (Get $get): bool => static::showsSummary($get))
                ->content(function (Get $get) use ($clinicPanel): View {
                    $appointment = VerificationCreationContext::scope(Appointment::query(), $clinicPanel)
                        ->with(['location', 'provider.user'])->find($get('appointment_id'));

                    return view('filament.shared.verification-intake-summary', [
                        'patient' => [
                            'Name' => $get('vf_patient_full_name'),
                            'Date of birth' => static::dateLabel($get('vf_patient_dob')),
                            'Member ID' => $get('vf_patient_identifier'),
                        ],
                        'appointment' => [
                            'Location' => $appointment?->location?->location_name,
                            'Provider' => $appointment ? Provider::query()->with('user')
                                ->where('clinic_id', $appointment->clinic_id)->find($get('provider_id'))?->display_name : null,
                            'Appointment date' => static::dateLabel($get('vf_appointment_date')),
                        ],
                    ]);
                }),
        ];
    }
}
