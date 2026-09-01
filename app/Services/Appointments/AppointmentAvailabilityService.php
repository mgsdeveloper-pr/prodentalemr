<?php

namespace App\Services\Appointments;

use App\Models\Clinic;
use App\Models\ClinicOperatory;
use App\Models\Location;
use App\Models\Provider;
use Carbon\Carbon;

class AppointmentAvailabilityService
{
    public function context(?Clinic $clinic, ?Location $location, ?Provider $provider, ?ClinicOperatory $operatory = null): array
    {
        $hours = filled($provider?->business_hours)
            ? $provider->business_hours
            : (filled($location?->business_hours) ? $location->business_hours : ($clinic?->business_hours ?? []));

        return [
            'hours' => $hours,
            'operatory_hours' => $operatory?->business_hours ?? [],
            'exceptions' => [
                ...($location?->schedule_exceptions ?? []),
                ...($provider?->schedule_exceptions ?? []),
                ...($operatory?->schedule_exceptions ?? []),
            ],
            'buffer_minutes' => (int) ($provider?->scheduling_buffer_minutes ?? 0),
        ];
    }

    public function operatingWindow(string $date, string $timezone, array $context): ?array
    {
        $day = strtolower(Carbon::parse($date, $timezone)->format('l'));
        $hours = data_get($context, "hours.{$day}");

        if (is_array($hours)) {
            if (! ($hours['open'] ?? false)) {
                return null;
            }

            $opensAt = $hours['opens_at'] ?? '09:00';
            $closesAt = $hours['closes_at'] ?? '17:00';
        } else {
            if (in_array($day, ['saturday', 'sunday'], true)) {
                return null;
            }

            $opensAt = '09:00';
            $closesAt = '17:00';
        }

        if ($this->isClosedAllDay($date, $context)) {
            return null;
        }

        $start = Carbon::parse($date.' '.$opensAt, $timezone);
        $end = Carbon::parse($date.' '.$closesAt, $timezone);

        $operatoryHours = data_get($context, "operatory_hours.{$day}");

        if (is_array($operatoryHours)) {
            if (! ($operatoryHours['open'] ?? false)) {
                return null;
            }

            $operatoryStart = Carbon::parse($date.' '.($operatoryHours['opens_at'] ?? $opensAt), $timezone);
            $operatoryEnd = Carbon::parse($date.' '.($operatoryHours['closes_at'] ?? $closesAt), $timezone);
            $start = $start->max($operatoryStart);
            $end = $end->min($operatoryEnd);
        }

        return $end->gt($start) ? [$start, $end] : null;
    }

    public function overlapsBreak(Carbon $start, Carbon $end, array $context): bool
    {
        $day = strtolower($start->format('l'));

        foreach (['hours', 'operatory_hours'] as $hoursPath) {
            $hours = data_get($context, "{$hoursPath}.{$day}");

            if (! is_array($hours) || ! filled($hours['break_starts_at'] ?? null) || ! filled($hours['break_ends_at'] ?? null)) {
                continue;
            }

            $date = $start->toDateString();
            $breakStart = Carbon::parse($date.' '.$hours['break_starts_at'], $start->getTimezone());
            $breakEnd = Carbon::parse($date.' '.$hours['break_ends_at'], $start->getTimezone());

            if ($start->lt($breakEnd) && $end->gt($breakStart)) {
                return true;
            }
        }

        return false;
    }

    public function overlapsException(Carbon $start, Carbon $end, array $context): bool
    {
        $date = $start->toDateString();

        return collect($context['exceptions'] ?? [])->contains(function (array $exception) use ($start, $end, $date): bool {
            if (($exception['date'] ?? null) !== $date) {
                return false;
            }

            if ((bool) ($exception['all_day'] ?? false)) {
                return true;
            }

            if (! filled($exception['starts_at'] ?? null) || ! filled($exception['ends_at'] ?? null)) {
                return false;
            }

            $blockedStart = Carbon::parse($date.' '.$exception['starts_at'], $start->getTimezone());
            $blockedEnd = Carbon::parse($date.' '.$exception['ends_at'], $start->getTimezone());

            return $start->lt($blockedEnd) && $end->gt($blockedStart);
        });
    }

    private function isClosedAllDay(string $date, array $context): bool
    {
        return collect($context['exceptions'] ?? [])->contains(
            fn (array $exception): bool => ($exception['date'] ?? null) === $date && (bool) ($exception['all_day'] ?? false)
        );
    }
}
