<?php

namespace App\Support;

use App\Models\Clinic;
use App\Models\Location;

class AppointmentWorkspaceScope
{
    public static function selectedClinicId(): ?int
    {
        return ClinicPanelScope::selectedClinicId()
            ?: AdminClinicScope::selectedClinic()?->getKey();
    }

    public static function selectedClinic(): ?Clinic
    {
        return ClinicPanelScope::selectedClinic()
            ?: AdminClinicScope::selectedClinic();
    }

    public static function selectedOrganizationId(): ?int
    {
        $clinic = self::selectedClinic();

        if ($clinic) {
            return (int) $clinic->organization_id;
        }

        return ClinicPanelScope::selectedOrganizationId();
    }

    public static function mappedLocation(): ?Location
    {
        $clinicId = self::selectedClinicId();

        if (! $clinicId) {
            return null;
        }

        $userLocationId = auth()->user()?->location_id;

        if ($userLocationId) {
            $assignedLocation = Location::query()
                ->whereKey($userLocationId)
                ->where('clinic_id', $clinicId)
                ->where('status', true)
                ->first();

            if ($assignedLocation) {
                return $assignedLocation;
            }
        }

        $defaultLocationId = self::selectedClinic()?->default_location_id;

        if ($defaultLocationId) {
            $defaultLocation = Location::query()
                ->whereKey($defaultLocationId)
                ->where('clinic_id', $clinicId)
                ->where('status', true)
                ->first();

            if ($defaultLocation) {
                return $defaultLocation;
            }
        }

        $locations = Location::query()
            ->where('clinic_id', $clinicId)
            ->where('status', true)
            ->orderBy('location_name')
            ->limit(2)
            ->get();

        return $locations->count() === 1 ? $locations->first() : null;
    }

    public static function mappedLocationId(): ?int
    {
        return self::mappedLocation()?->getKey();
    }

    public static function hasLockedLocation(): bool
    {
        return filled(self::mappedLocationId());
    }
}
