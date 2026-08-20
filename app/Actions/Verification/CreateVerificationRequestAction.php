<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Services\Verification\AssignmentService;
use App\Services\Verification\SLAService;
use App\Services\Verification\VerificationIntakeService;
use App\Support\VerificationTemplateVersionService;

class CreateVerificationRequestAction
{
    public function __construct(
        protected AssignmentService $assignments,
        protected SLAService $sla,
        protected VerificationIntakeService $intake,
        protected VerificationTemplateVersionService $templates,
    ) {
    }

    public function prepareData(array $data): array
    {
        $data = $this->intake->normalizeAndValidate($data);
        $data['processing_mode'] ??= BillingWorkItem::processingModeForSource($data['source'] ?? null);
        $data['due_at'] = $this->sla->resolveDueAt($data);
        $data['assigned_to'] = ($data['assigned_to'] ?? null) ?: $this->assignments->autoAssign(
            $data['source'] ?? null,
            filled($data['clinic_id'] ?? null) ? (int) $data['clinic_id'] : null,
        )?->id;

        return $data;
    }

    public function execute(array $data): BillingWorkItem
    {
        $workItem = BillingWorkItem::query()->create($this->prepareData($data));

        return $this->templates->attachSnapshotToWorkItem($workItem);
    }
}
