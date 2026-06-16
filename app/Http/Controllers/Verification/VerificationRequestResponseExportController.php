<?php

namespace App\Http\Controllers\Verification;

use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Support\AdminClinicScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class VerificationRequestResponseExportController extends Controller
{
    protected const REQUEST_ACTIVITY = 'info_requested_from_clinic';

    protected const RESPONSE_ACTIVITY = 'clinic_response_received';

    public function __invoke(Request $request)
    {
        abort_unless(auth()->user()?->canAccessVerificationWorkspace(), 403);

        $statusFilter = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));

        return response()->streamDownload(function () use ($statusFilter, $search): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Sr#',
                'Reference Number',
                'Patient Name',
                'Clinic Name',
                'Request Raised',
                'Response Received',
                'Date & Time',
                'Status',
            ]);

            $this->query($statusFilter, $search)
                ->orderByDesc(
                    BillingWorkItemActivity::query()
                        ->select('created_at')
                        ->whereColumn('billing_work_item_id', 'billing_work_items.id')
                        ->whereIn('activity_type', [self::REQUEST_ACTIVITY, self::RESPONSE_ACTIVITY])
                        ->latest('created_at')
                        ->limit(1)
                )
                ->orderByDesc('id')
                ->get()
                ->values()
                ->each(function (BillingWorkItem $workItem, int $index) use ($handle): void {
                    $request = $workItem->activities->firstWhere('activity_type', self::REQUEST_ACTIVITY);
                    $response = $this->resolveLatestResponse($workItem, $request);
                    $latestActivityAt = collect([$request?->created_at, $response?->created_at])->filter()->max();
                    $status = $this->resolveWorkflowState($workItem, $request, $response);

                    fputcsv($handle, [
                        $index + 1,
                        $workItem->reference_number,
                        $workItem->verificationProfile?->patient_full_name ?: ($workItem->patient?->full_name ?: 'Unknown patient'),
                        $workItem->clinic?->clinic_name ?: '-',
                        $request
                            ? Str::limit(trim((string) data_get($request->meta, 'info_request_reason', $request->description)), 90)
                            : '-',
                        $response
                            ? Str::limit(trim((string) data_get($response->meta, 'clinic_response_note', $response->description)), 90)
                            : ($request ? 'Waiting for clinic response' : '-'),
                        $latestActivityAt?->format('d M Y, h:i A') ?: '-',
                        $status['label'],
                    ]);
                });

            fclose($handle);
        }, 'verification-request-response.csv', ['Content-Type' => 'text/csv']);
    }

    protected function query(string $statusFilter, string $search): Builder
    {
        $query = AdminClinicScope::apply(BillingWorkItem::query(), 'clinic_id')
            ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
            ->where('source', '!=', 'clinic_self_service')
            ->whereHas('activities', fn (Builder $builder) => $builder->whereIn('activity_type', [
                self::REQUEST_ACTIVITY,
                self::RESPONSE_ACTIVITY,
            ]))
            ->with([
                'clinic.organization',
                'patient',
                'verificationProfile',
                'activities' => fn ($builder) => $builder
                    ->whereIn('activity_type', [self::REQUEST_ACTIVITY, self::RESPONSE_ACTIVITY])
                    ->with('user')
                    ->latest('created_at'),
            ])
            ->when($statusFilter === 'open', fn (Builder $builder) => $builder->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE))
            ->when($statusFilter === 'responded', fn (Builder $builder) => $builder->whereNotNull('clinic_responded_at'))
            ->when($statusFilter === 'closed', fn (Builder $builder) => $builder->where('status', BillingWorkItem::STATUS_DONE))
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $like = '%' . $search . '%';

                $builder->where(function (Builder $searchQuery) use ($like): void {
                    $searchQuery
                        ->where('reference_number', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhereHas('clinic', fn (Builder $clinicQuery) => $clinicQuery->where('clinic_name', 'like', $like))
                        ->orWhereHas('verificationProfile', function (Builder $profileQuery) use ($like): void {
                            $profileQuery
                                ->where('patient_full_name', 'like', $like)
                                ->orWhere('requested_by_name', 'like', $like)
                                ->orWhere('insurance_provider_name', 'like', $like);
                        })
                        ->orWhereHas('activities', function (Builder $activityQuery) use ($like): void {
                            $activityQuery
                                ->whereIn('activity_type', [self::REQUEST_ACTIVITY, self::RESPONSE_ACTIVITY])
                                ->where(function (Builder $innerQuery) use ($like): void {
                                    $innerQuery
                                        ->where('description', 'like', $like)
                                        ->orWhere('meta->info_request_reason', 'like', $like)
                                        ->orWhere('meta->clinic_response_note', 'like', $like);
                                });
                        });
                });
            });

        $user = auth()->user();

        if ($user?->hasRole('verification_user') && ! $user->canManageVerificationQueue()) {
            $query->where('assigned_to', $user->getAuthIdentifier());
        }

        return $query;
    }

    protected function resolveLatestResponse(BillingWorkItem $workItem, ?BillingWorkItemActivity $request): ?BillingWorkItemActivity
    {
        return $workItem->activities
            ->where('activity_type', self::RESPONSE_ACTIVITY)
            ->when($request, fn ($items) => $items->filter(
                fn (BillingWorkItemActivity $activity): bool => optional($activity->created_at)?->greaterThanOrEqualTo($request->created_at) ?? false
            ))
            ->sortByDesc('created_at')
            ->first();
    }

    protected function resolveWorkflowState(BillingWorkItem $workItem, ?BillingWorkItemActivity $request, ?BillingWorkItemActivity $response): array
    {
        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            return ['label' => 'Closed', 'tone' => 'emerald'];
        }

        if ($response) {
            return ['label' => 'Responded', 'tone' => 'sky'];
        }

        if ($request) {
            return ['label' => 'Open', 'tone' => 'amber'];
        }

        return ['label' => 'Pending', 'tone' => 'slate'];
    }
}
