<?php

namespace App\Actions\Verification;

use App\Models\BillingWorkItem;
use App\Models\Appointment;
use App\Models\Clinic;
use Illuminate\Support\Facades\Validator;
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
        // Existing integrations retain automatic routing unless they supply an explicit method.
        $explicitMethod = array_key_exists('assignment_method', $data);
        $method = $data['assignment_method'] ?? (filled($data['assigned_to'] ?? null) ? 'manual' : 'auto');
        if (($data['source'] ?? null) === 'clinic_self_service') {
            $method = 'clinic';
        } elseif (($data['source'] ?? null) === 'clinic_request' && $explicitMethod) {
            $clinicId = $data['clinic_id'] ?? Appointment::find($data['appointment_id'] ?? null)?->clinic_id;
            $method = Clinic::find($clinicId)?->verification_assignment_method ?? 'unassigned';
        }
        if ($method === 'clinic' && ($data['source'] ?? null) !== 'clinic_self_service') {
            $method = 'unassigned';
        }
        Validator::make(['assignment_method' => $method], [
            'assignment_method' => ['required', 'in:unassigned,auto,manual,clinic'],
        ])->validate();
        if ($method !== 'manual') {
            $data['assigned_to'] = null;
        } else {
            Validator::make($data, ['assigned_to' => ['required', 'integer']])->validate();
        }
        $data = $this->intake->normalizeAndValidate($data);
        $data['processing_mode'] ??= BillingWorkItem::processingModeForSource($data['source'] ?? null);
        $data['due_at'] = $this->sla->resolveDueAt($data);
        $data['assignment_method'] = $method;
        if ($method === 'auto') {
            $data['assigned_to'] = $this->assignments->autoAssign(
                $data['source'] ?? null,
                filled($data['clinic_id'] ?? null) ? (int) $data['clinic_id'] : null,
            )?->id;
        }
        if ($method !== 'clinic' && ($explicitMethod || blank($data['status'] ?? null))) {
            $data['status'] = filled($data['assigned_to']) ? BillingWorkItem::STATUS_PENDING : 'unassigned';
        }

        return $data;
    }

    public function execute(array $data): BillingWorkItem
    {
        $workItem = BillingWorkItem::query()->create($this->prepareData($data));

        return $this->templates->attachSnapshotToWorkItem($workItem);
    }
}
