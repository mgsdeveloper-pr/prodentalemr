<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CoverageAssessmentService
{
    public const OUTCOMES = ['active' => 'Active Coverage', 'inactive' => 'Inactive Coverage', 'unconfirmed' => 'Unable to Confirm'];

    public const FIELDS = [
        'patient_name' => 'Patient name',
        'insurance_name' => 'Insurance / policy name',
        'member_id' => 'Member / policy ID',
        'service_date' => 'Coverage checked for',
        'source' => 'Verification source',
        'reason' => 'Finding / explanation',
        'termination_date' => 'Termination date (if known)',
        'reference' => 'Representative / reference (if available)',
    ];

    public const REQUIRED = ['patient_name', 'insurance_name', 'member_id', 'service_date', 'source', 'reason'];

    public const SOURCES = ['Payer portal', 'Insurance phone call', 'Eligibility response', 'Other documented source'];

    public function normalize(array $data): array
    {
        $rules = ['outcome' => 'required|in:active,inactive,unconfirmed'];
        foreach (self::FIELDS as $key => $label) {
            $rules[$key] = in_array($key, ['service_date', 'termination_date'], true)
                ? 'nullable|date_format:Y-m-d' : 'nullable|string|max:2000';
        }
        $rules['source'] = ['nullable', Rule::in(self::SOURCES)];

        return Validator::make($data, $rules)->validate();
    }

    public function missing(array $data, ?string $appointmentDate = null): array
    {
        if ($data === []) {
            return [];
        }
        $missing = [];
        if (! in_array($data['outcome'] ?? null, ['active', 'inactive'], true)) {
            $missing['coverage.outcome'] = 'Confirm active or inactive coverage before review.';
        }
        foreach (self::REQUIRED as $key) {
            if (blank($data[$key] ?? null)) {
                $missing['coverage.'.$key] = self::FIELDS[$key];
            }
        }
        try {
            $this->normalize($data);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $missing['coverage.'.$key] = $messages[0];
            }
        }
        if ($appointmentDate && filled($data['service_date'] ?? null) && $appointmentDate !== $data['service_date']) {
            $missing['coverage.service_date'] = 'Recheck coverage for the current appointment date.';
        }

        return $missing;
    }

    public function missingForRequest(BillingWorkItem $request): array
    {
        if ($request->outcome_status === 'inactive_coverage' && ! $this->isInactive($request)) {
            return ['coverage.outcome' => 'Record an inactive coverage assessment with evidence.'];
        }
        $date = $request->appointment?->appointment_date ?? $request->verificationProfile?->appointment_date;
        $date = $date ? Carbon::parse($date)->toDateString() : null;

        return $this->missing($request->coverage_assessment ?? [], $date);
    }

    public function isInactive(BillingWorkItem $request): bool
    {
        return data_get($request->coverage_assessment, 'outcome') === 'inactive';
    }
}
