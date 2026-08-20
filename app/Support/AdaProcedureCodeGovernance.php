<?php

namespace App\Support;

use App\Models\AdaProcedureCode;
use App\Models\SaasEntitlementAuditLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class AdaProcedureCodeGovernance
{
    public function create(User $actor, array $data): AdaProcedureCode
    {
        Gate::forUser($actor)->authorize('create', AdaProcedureCode::class);

        $code = AdaProcedureCode::create([
            ...$this->governancePayload($data, $actor),
            'procedure_code' => strtoupper(trim((string) $data['procedure_code'])),
            'description' => trim((string) $data['description']),
            'class' => AdaProcedureCode::normalizeClassValue($data['class'] ?? null),
            'is_active' => true,
            'lifecycle_status' => AdaProcedureCode::LIFECYCLE_ACTIVE,
            'retired_at' => null,
            'retirement_reason' => null,
        ]);

        $this->audit('ada_code_created', $code, [], $this->auditedValues($code), $actor, $data['governance_notes'] ?? null);

        return $code;
    }

    public function update(User $actor, AdaProcedureCode $code, array $data): AdaProcedureCode
    {
        Gate::forUser($actor)->authorize('update', $code);

        $before = $this->auditedValues($code);

        $code->update([
            ...$this->governancePayload($data, $actor),
            'description' => trim((string) $data['description']),
            'class' => AdaProcedureCode::normalizeClassValue($data['class'] ?? null),
            'source_year' => (int) $data['source_year'],
            'source_document' => trim((string) $data['source_document']),
            'source_page' => filled($data['source_page'] ?? null) ? (int) $data['source_page'] : null,
        ]);

        $this->audit('ada_code_updated', $code, $before, $this->auditedValues($code), $actor, $data['governance_notes'] ?? null);

        return $code->refresh();
    }

    public function retireByAda(User $actor, AdaProcedureCode $code, array $data): AdaProcedureCode
    {
        Gate::forUser($actor)->authorize('update', $code);

        $before = $this->auditedValues($code);

        $code->update([
            ...$this->governancePayload($data, $actor),
            'is_active' => false,
            'lifecycle_status' => AdaProcedureCode::LIFECYCLE_REMOVED_BY_ADA,
            'retired_at' => now(),
            'retirement_reason' => trim((string) $data['retirement_reason']),
            'source_year' => (int) $data['source_year'],
            'source_document' => trim((string) $data['source_document']),
        ]);

        $this->audit('ada_code_removed_by_ada', $code, $before, $this->auditedValues($code), $actor, $data['retirement_reason'] ?? null);

        return $code->refresh();
    }

    protected function governancePayload(array $data, User $actor): array
    {
        return [
            'source_year' => filled($data['source_year'] ?? null) ? (int) $data['source_year'] : (int) date('Y'),
            'source_document' => trim((string) ($data['source_document'] ?? 'Manual ADA/CDT update')),
            'source_page' => filled($data['source_page'] ?? null) ? (int) $data['source_page'] : null,
            'effective_date' => $data['effective_date'] ?? null,
            'governance_notes' => trim((string) ($data['governance_notes'] ?? '')),
            'last_reviewed_at' => now(),
            'last_reviewed_by' => $actor->getKey(),
        ];
    }

    protected function auditedValues(AdaProcedureCode $code): array
    {
        return Arr::only($code->getAttributes(), [
            'procedure_code',
            'description',
            'class',
            'is_active',
            'lifecycle_status',
            'source_year',
            'source_document',
            'source_page',
            'effective_date',
            'retired_at',
            'retirement_reason',
            'governance_notes',
            'last_reviewed_by',
        ]);
    }

    protected function audit(string $eventType, AdaProcedureCode $code, array $before, array $after, User $actor, ?string $notes): void
    {
        SaasEntitlementAuditLog::query()->create([
            'actor_user_id' => $actor->getKey(),
            'event_type' => $eventType,
            'entity_type' => $code::class,
            'entity_id' => $code->getKey(),
            'before_values' => ['record' => $before],
            'after_values' => ['record' => $after],
            'notes' => $notes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
