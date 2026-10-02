<?php

namespace App\Support;

use App\Models\AdaProcedureCode;

class VerificationProcedureTags
{
    public static function inspect(array $row): array
    {
        preg_match_all('/\bD\d{4}\b/i', $row['question'] ?? '', $matches);
        $detected = array_values(array_unique(array_map('strtoupper', $matches[0])));
        $explicit = array_values(array_unique(array_filter(array_map(fn ($code) => strtoupper(trim($code)), explode('|', $row['procedure_codes'] ?? '')))));
        $system = strtoupper(trim($row['code_system'] ?? ''));
        $codes = $explicit ?: $detected;
        if ($codes && $system === '') {
            $system = $explicit ? '' : 'CDT';
        }
        $errors = [];
        if ($explicit && $detected && ($system !== 'CDT' || array_diff($detected, $explicit) || array_diff($explicit, $detected))) {
            $errors[] = 'Explicit procedure codes conflict with codes in the question.';
        }
        $tags = [];
        foreach ($codes as $code) {
            if (! in_array($system, ['CDT', 'CPT'], true)
                || ! preg_match($system === 'CDT' ? '/^D\d{4}$/' : '/^(?:\d{5}|\d{4}[FTU])$/', $code)) {
                $errors[] = "Invalid code or code system: {$code}.";
                continue;
            }
            $entry = $system === 'CDT' ? AdaProcedureCode::query()->active()->where('procedure_code', $code)->first() : null;
            $tags[] = [
                'system' => $system, 'code' => $code,
                'status' => $entry ? 'directory_match' : 'unverified',
                'source_year' => $entry?->source_year,
                'origin' => $explicit ? 'explicit' : 'detected',
                'purpose' => $row['question_purpose'] ?? '',
                'relationship' => $row['code_relationship'] ?? '',
            ];
        }
        return ['tags' => $tags, 'errors' => $errors];
    }
}
