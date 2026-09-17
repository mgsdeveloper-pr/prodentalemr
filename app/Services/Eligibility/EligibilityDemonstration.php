<?php

namespace App\Services\Eligibility;

class EligibilityDemonstration
{
    public const SCENARIOS = [
        'partial' => 'Active coverage / incomplete benefits',
        'inactive' => 'Inactive coverage',
        'unavailable' => 'Payer unavailable',
    ];

    public function run(string $scenario): array
    {
        abort_unless(array_key_exists($scenario, self::SCENARIOS), 422);

        return [
            'source' => 'ProDental fictional example - not a Zuub response',
            'patient' => 'Avery Example (TEST)',
            'status' => $scenario === 'partial' ? 'Needs review' : ($scenario === 'inactive' ? 'Inactive coverage' : 'Unable to retrieve'),
            'fields' => $scenario === 'partial' ? [
                ['label' => 'Coverage status', 'value' => 'Active', 'state' => 'Sample value'],
                ['label' => 'Annual maximum', 'value' => '$1,500.00', 'state' => 'Sample value'],
                ['label' => 'Remaining annual maximum', 'value' => '$750.00', 'state' => 'Sample value'],
                ['label' => 'Individual deductible', 'value' => '$50.00', 'state' => 'Sample value'],
                ['label' => 'Preventive coverage', 'value' => '100%', 'state' => 'Sample value'],
                ['label' => 'Orthodontic payment schedule', 'value' => null, 'state' => 'Needs confirmation'],
                ['label' => 'Missing-tooth clause', 'value' => null, 'state' => 'Needs confirmation'],
            ] : [],
            'message' => match ($scenario) {
                'partial' => 'Example only. Missing benefits stay unconfirmed; no form answers were changed.',
                'inactive' => 'Example only. No active coverage for the requested date; review before proceeding.',
                default => 'Example only. A retrieval failure is not evidence of inactive coverage.',
            },
        ];
    }
}
