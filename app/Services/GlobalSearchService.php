<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\InsuranceCarrier;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Patient;
use App\Models\User;
use App\Support\AdminClinicScope;
use App\Support\ClinicPanelScope;
use App\Support\DsoScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GlobalSearchService
{
    private const LIMIT = 6;

    public function search(User $user, string $workspace, ?string $query): array
    {
        $workspace = $workspace === 'organization' ? 'clinic' : $workspace;
        $this->authorize($user, $workspace);

        $query = Str::of((string) $query)->replace(['%', '_'], '')->squish()->limit(100, '')->toString();

        if (mb_strlen($query) < 2) {
            return $this->response($workspace, $query, $this->quickLinks($user, $workspace));
        }

        $groups = match ($workspace) {
            'platform' => $this->platform($user, $query),
            'verification' => $this->verification($user, $query),
            'clinic' => $this->clinic($user, $query),
            'dso' => $this->dso($user, $query),
            default => [],
        };

        return $this->response($workspace, $query, $groups);
    }

    private function platform(User $user, string $query): array
    {
        $groups = [];
        $like = "%{$query}%";

        if ($user->canAccessSaasModule('organizations')) {
            $items = Organization::query()
                ->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('owner_name', 'like', $like)->orWhere('email', 'like', $like))
                ->orderBy('name')->limit(self::LIMIT)->get()
                ->map(fn (Organization $record) => $this->item('organization', $record->name, 'Organization', $record->status ? 'Active' : 'Inactive', $this->route('filament.saas.resources.organizations.view', ['record' => $record])));
            $this->add($groups, 'organizations', 'Organizations', $items);
        }

        if ($user->canAccessSaasModule('clinics')) {
            $items = Clinic::query()->with('organization')
                ->where(fn (Builder $q) => $q->where('clinic_name', 'like', $like)->orWhere('clinic_code', 'like', $like)->orWhereHas('organization', fn (Builder $org) => $org->where('name', 'like', $like)))
                ->orderBy('clinic_name')->limit(self::LIMIT)->get()
                ->map(fn (Clinic $record) => $this->item('clinic', $record->clinic_name, $record->organization?->name, $record->status ? 'Active' : 'Inactive', $this->route('filament.saas.resources.clinics.view', ['record' => $record])));
            $this->add($groups, 'clinics', 'Clinics', $items);
        }

        if ($user->canAccessSaasModule('invoices')) {
            $items = Invoice::query()->with('organization')
                ->where(fn (Builder $q) => $q->where('invoice_number', 'like', $like)->orWhereHas('organization', fn (Builder $org) => $org->where('name', 'like', $like)))
                ->latest('issue_date')->limit(self::LIMIT)->get()
                ->map(fn (Invoice $record) => $this->item('invoice', $record->invoice_number, $record->organization?->name, Str::headline((string) $record->status), $this->route('filament.saas.resources.invoices.view', ['record' => $record])));
            $this->add($groups, 'invoices', 'Invoices', $items);
        }

        if ($user->canAccessSaasModule('insurance_directory')) {
            $this->add($groups, 'insurance', 'Insurance Directory', $this->insurance($query, 'filament.saas.resources.insurance-carriers.edit'));
        }

        return $groups;
    }

    private function verification(User $user, string $query): array
    {
        $groups = [];
        $like = "%{$query}%";
        $items = AdminClinicScope::applyVerificationRequests(BillingWorkItem::query())
            ->with(['patient', 'clinic'])
            ->where(fn (Builder $q) => $q->where('reference_number', 'like', $like)->orWhere('title', 'like', $like)
                ->orWhereHas('patient', fn (Builder $patient) => $patient->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like))
                ->orWhereHas('clinic', fn (Builder $clinic) => $clinic->where('clinic_name', 'like', $like)))
            ->latest('updated_at')->limit(self::LIMIT)->get()
            ->map(fn (BillingWorkItem $record) => $this->requestItem($record, $this->route('filament.admin.resources.verifications.view', ['record' => $record])));
        $this->add($groups, 'requests', 'Verification Requests', $items);

        $clinics = AdminClinicScope::accessibleManagedServiceClinicQuery($user)->with('organization')
            ->where(fn (Builder $q) => $q->where('clinic_name', 'like', $like)->orWhereHas('organization', fn (Builder $org) => $org->where('name', 'like', $like)))
            ->orderBy('clinic_name')->limit(self::LIMIT)->get()
            ->map(fn (Clinic $record) => $this->item('clinic', $record->clinic_name, $record->organization?->name, 'Open clinic queue', $this->route('admin.clinic-scope', ['clinic_id' => $record->id, 'redirect' => $this->route('filament.admin.resources.verifications.index')])));
        $this->add($groups, 'clinics', 'Assigned Clinics', $clinics);

        return $groups;
    }

    private function clinic(User $user, string $query): array
    {
        $groups = [];
        $clinicId = ClinicPanelScope::selectedClinicId();
        $organizationId = ClinicPanelScope::selectedOrganizationId();
        if (! $clinicId || ! $organizationId) {
            return [];
        }

        $like = "%{$query}%";
        if ($user->canAccessClinicVerificationRequests()) {
            $items = BillingWorkItem::query()->with(['patient', 'clinic'])
                ->where('organization_id', $organizationId)->where('clinic_id', $clinicId)
                ->where(fn (Builder $q) => $q->where('reference_number', 'like', $like)->orWhere('title', 'like', $like)
                    ->orWhereHas('patient', fn (Builder $patient) => $patient->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)))
                ->latest('updated_at')->limit(self::LIMIT)->get()
                ->map(fn (BillingWorkItem $record) => $this->requestItem($record, $this->route('filament.clinic.resources.verification-requests.view', ['record' => $record])));
            $this->add($groups, 'requests', 'Verification Requests', $items);
        }

        if ($user->canAccessClinicPatients()) {
            $items = Patient::query()->where('organization_id', $organizationId)->where('clinic_id', $clinicId)
                ->where(fn (Builder $q) => $q->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)->orWhere('pms_patient_id', 'like', $like))
                ->orderBy('first_name')->limit(self::LIMIT)->get()
                ->map(fn (Patient $record) => $this->item('patient', $record->full_name, 'Patient record', $record->status ? 'Active' : 'Inactive', $this->route('filament.clinic.resources.patients.view', ['record' => $record])));
            $this->add($groups, 'patients', 'Patients', $items);
        }

        if ($user->canAccessClinicAppointments()) {
            $items = Appointment::query()->with('patient')->where('organization_id', $organizationId)->where('clinic_id', $clinicId)
                ->where(fn (Builder $q) => $q->where('appointment_type', 'like', $like)->orWhereHas('patient', fn (Builder $patient) => $patient->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)))
                ->latest('appointment_date')->limit(self::LIMIT)->get()
                ->map(fn (Appointment $record) => $this->item('appointment', $record->patient?->full_name ?: 'Appointment', $record->appointment_type, $record->appointment_date?->format('M j, Y'), $this->route('filament.clinic.resources.appointments.view', ['record' => $record])));
            $this->add($groups, 'appointments', 'Appointments', $items);
        }

        return $groups;
    }

    private function dso(User $user, string $query): array
    {
        if (! $user->hasPermissionTo('dso.clinics.view')) {
            return [];
        }

        $groups = [];
        $like = "%{$query}%";
        $items = Clinic::query()->with('organization')->whereIn('id', DsoScope::clinicIdsFor($user))
            ->where(fn (Builder $q) => $q->where('clinic_name', 'like', $like)->orWhereHas('organization', fn (Builder $org) => $org->where('name', 'like', $like)))
            ->orderBy('clinic_name')->limit(self::LIMIT)->get()
            ->map(fn (Clinic $record) => $this->item('clinic', $record->clinic_name, $record->organization?->name, $record->status ? 'Active' : 'Inactive', $this->route('filament.dso.pages.clinics')));
        $this->add($groups, 'clinics', 'Clinics', $items);

        return $groups;
    }

    private function insurance(string $query, string $route): Collection
    {
        $like = "%{$query}%";

        return InsuranceCarrier::query()->where('is_active', true)
            ->where(fn (Builder $q) => $q->where('insurance_name', 'like', $like)->orWhere('payer_id', 'like', $like)->orWhere('payer_phone', 'like', $like))
            ->orderBy('insurance_name')->limit(self::LIMIT)->get()
            ->map(fn (InsuranceCarrier $record) => $this->item('insurance', $record->insurance_name, filled($record->payer_id) ? "Payer ID {$record->payer_id}" : 'Insurance carrier', 'Active', $this->route($route, ['record' => $record])));
    }

    private function quickLinks(User $user, string $workspace): array
    {
        $items = match ($workspace) {
            'platform' => collect([$this->item('navigation', 'Client Management', 'Organizations and clinics', null, '/saas/client-management')]),
            'verification' => collect([$this->item('navigation', 'Verification Requests', 'Open the verification queue', null, $this->route('filament.admin.resources.verifications.index'))]),
            'clinic' => collect([$user->canAccessClinicVerificationRequests() ? $this->item('navigation', 'Verification Requests', 'Clinic verification records', null, $this->route('filament.clinic.resources.verification-requests.index')) : null]),
            'dso' => collect([$this->item('navigation', 'Clinic Directory', 'Organizations and clinics', null, $this->route('filament.dso.pages.clinics'))]),
            default => collect(),
        };
        $items = $items->filter()->values();

        return $items->isEmpty() ? [] : [['key' => 'navigation', 'label' => 'Quick Navigation', 'items' => $items->all()]];
    }

    private function requestItem(BillingWorkItem $record, string $url): array
    {
        return $this->item('request', $record->patient?->full_name ?: ($record->title ?: 'Verification Request'), collect([$record->reference_number, $record->clinic?->clinic_name])->filter()->implode(' | '), BillingWorkItem::STATUS_OPTIONS[$record->status] ?? Str::headline((string) $record->status), $url);
    }

    private function item(string $kind, string $title, ?string $subtitle, ?string $meta, string $url): array
    {
        return compact('kind', 'title', 'subtitle', 'meta', 'url');
    }

    private function route(string $name, array $parameters = []): string
    {
        return route($name, $parameters, false);
    }

    private function add(array &$groups, string $key, string $label, Collection $items): void
    {
        if ($items->isNotEmpty()) {
            $groups[] = ['key' => $key, 'label' => $label, 'items' => $items->values()->all()];
        }
    }

    private function response(string $workspace, string $query, array $groups): array
    {
        return ['workspace' => $workspace, 'query' => $query, 'groups' => $groups, 'result_count' => collect($groups)->sum(fn (array $group) => count($group['items']))];
    }

    private function authorize(User $user, string $workspace): void
    {
        $allowed = match ($workspace) {
            'platform' => $user->hasSaasWorkspaceRole(),
            'verification' => $user->canAccessVerificationWorkspace(),
            'clinic' => $user->canAccessClinicWorkspace(),
            'dso' => $user->canAccessDsoWorkspace(),
            default => false,
        };
        if (! $allowed) {
            throw new AccessDeniedHttpException('This search workspace is not available to your account.');
        }
    }
}
