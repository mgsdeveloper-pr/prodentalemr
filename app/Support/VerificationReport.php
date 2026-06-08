<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Models\Clinic;
use App\Models\Location;
use App\Models\Provider;
use App\Models\VerificationProfile;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VerificationReport
{
    public static function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['from_date'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('billing_work_items.created_at', '>=', $date))
            ->when($filters['to_date'] ?? null, fn (Builder $builder, $date) => $builder->whereDate('billing_work_items.created_at', '<=', $date))
            ->when($filters['clinic_id'] ?? null, fn (Builder $builder, $clinicId) => $builder->where('billing_work_items.clinic_id', $clinicId))
            ->when($filters['location_id'] ?? null, fn (Builder $builder, $locationId) => $builder->where('billing_work_items.location_id', $locationId))
            ->when($filters['provider_id'] ?? null, fn (Builder $builder, $providerId) => $builder->where('billing_work_items.provider_id', $providerId))
            ->when($filters['assigned_to'] ?? null, fn (Builder $builder, $assigneeId) => $builder->where('billing_work_items.assigned_to', $assigneeId))
            ->when($filters['worked_by'] ?? null, function (Builder $builder, $userId): void {
                $builder->whereHas('activities', fn (Builder $activityQuery) => $activityQuery->where('user_id', $userId));
            })
            ->when($filters['status'] ?? null, fn (Builder $builder, $status) => $builder->where('billing_work_items.status', $status))
            ->when($filters['outcome_status'] ?? null, fn (Builder $builder, $status) => $builder->where('billing_work_items.outcome_status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $builder, $priority) => $builder->where('billing_work_items.priority', $priority))
            ->when($filters['source'] ?? null, fn (Builder $builder, $source) => $builder->where('billing_work_items.source', $source))
            ->when($filters['workflow_exception'] ?? null, function (Builder $builder, $exception): void {
                match ((string) $exception) {
                    'awaiting_clinic_response' => $builder->where('billing_work_items.status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE),
                    'returned_for_rework' => $builder->where('billing_work_items.status', BillingWorkItem::STATUS_RETURNED_FOR_REWORK),
                    'active_exception' => $builder->whereIn('billing_work_items.status', [
                        BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
                        BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                    ]),
                    default => null,
                };
            })
            ->when($filters['form_type'] ?? null, function (Builder $builder, $formType): void {
                $builder->whereHas('verificationProfile', fn (Builder $profileQuery) => $profileQuery->where('form_type', $formType));
            })
            ->when(array_key_exists('insurance_status', $filters) && filled($filters['insurance_status'] ?? null), function (Builder $builder) use ($filters): void {
                $isActive = (string) $filters['insurance_status'] === 'active';

                $builder->whereHas('insurancePolicy', fn (Builder $policyQuery) => $policyQuery->where('status', $isActive));
            });
    }

    public static function summaryCards(Builder $query): array
    {
        $total = (clone $query)->count();
        $completed = (clone $query)->whereIn('status', [BillingWorkItem::STATUS_DONE, 'completed'])->count();
        $verified = (clone $query)->where('outcome_status', 'verified')->count();
        $awaitingClinic = (clone $query)->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)->count();
        $returnedForRework = (clone $query)->where('status', BillingWorkItem::STATUS_RETURNED_FOR_REWORK)->count();
        $overdue = (clone $query)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [BillingWorkItem::STATUS_DONE, BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE, 'completed'])
            ->count();
        $urgent = (clone $query)->where('priority', 'urgent')->count();
        return [
            [
                'label' => 'Total Requests',
                'value' => number_format($total),
                'description' => 'Verification requests in the current reporting view',
                'accent' => 'slate',
            ],
            [
                'label' => 'Completed',
                'value' => number_format($completed),
                'description' => 'Requests marked done',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Verified',
                'value' => number_format($verified),
                'description' => 'Requests with verified outcome',
                'accent' => 'sky',
            ],
            [
                'label' => 'Waiting on Clinic',
                'value' => number_format($awaitingClinic),
                'description' => 'Requests paused until clinic information is received',
                'accent' => 'indigo',
            ],
            [
                'label' => 'Returned for Rework',
                'value' => number_format($returnedForRework),
                'description' => 'Requests sent back for correction before final closure',
                'accent' => 'amber',
            ],
            [
                'label' => 'Overdue',
                'value' => number_format($overdue),
                'description' => 'Requests past SLA and still open',
                'accent' => 'rose',
            ],
            [
                'label' => 'Urgent',
                'value' => number_format($urgent),
                'description' => 'Urgent-priority requests',
                'accent' => 'amber',
            ],
        ];
    }

    public static function slaAnalytics(Builder $query): array
    {
        $completedRows = (clone $query)
            ->whereNotNull('completed_at')
            ->get([
                'id',
                'created_at',
                'started_at',
                'completed_at',
                'sla_paused_seconds',
            ]);

        $awaitingRows = (clone $query)
            ->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
            ->get([
                'id',
                'created_at',
                'updated_at',
                'sla_pause_started_at',
                'sla_paused_seconds',
                'due_at',
            ]);

        $reworkRows = (clone $query)
            ->where('status', BillingWorkItem::STATUS_RETURNED_FOR_REWORK)
            ->get([
                'id',
                'created_at',
                'updated_at',
            ]);

        $reviewRows = (clone $query)
            ->where('status', BillingWorkItem::STATUS_REVIEW)
            ->get([
                'id',
                'created_at',
                'updated_at',
                'due_at',
            ]);

        $avgTurnaroundSeconds = static::averageSeconds(
            $completedRows->map(function (BillingWorkItem $item): int {
                $startedAt = $item->started_at ?: $item->created_at;

                if (! $startedAt || ! $item->completed_at) {
                    return 0;
                }

                $rawSeconds = max(0, $startedAt->diffInSeconds($item->completed_at));

                return max(0, $rawSeconds - (int) ($item->sla_paused_seconds ?? 0));
            })->all()
        );

        $avgWaitingOnClinicSeconds = static::averageSeconds(
            $awaitingRows->map(function (BillingWorkItem $item): int {
                $currentPauseSeconds = filled($item->sla_pause_started_at)
                    ? max(0, $item->sla_pause_started_at->diffInSeconds(now()))
                    : 0;

                return (int) ($item->sla_paused_seconds ?? 0) + $currentPauseSeconds;
            })->all()
        );

        $avgReworkAgeSeconds = static::averageSeconds(
            $reworkRows->map(function (BillingWorkItem $item): int {
                $anchor = $item->updated_at ?: $item->created_at;

                return $anchor ? max(0, $anchor->diffInSeconds(now())) : 0;
            })->all()
        );

        $avgReviewAgeSeconds = static::averageSeconds(
            $reviewRows->map(function (BillingWorkItem $item): int {
                $anchor = $item->updated_at ?: $item->created_at;

                return $anchor ? max(0, $anchor->diffInSeconds(now())) : 0;
            })->all()
        );

        $overdueCount = (clone $query)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [
                BillingWorkItem::STATUS_DONE,
                BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
                'completed',
            ])
            ->count();

        $dueTodayCount = (clone $query)
            ->whereDate('due_at', today())
            ->whereNotIn('status', [
                BillingWorkItem::STATUS_DONE,
                BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
                'completed',
            ])
            ->count();

        $bars = static::barVisualization([
            [
                'label' => 'Waiting on Clinic',
                'value' => $awaitingRows->count(),
                'key' => BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE,
            ],
            [
                'label' => 'Returned for Rework',
                'value' => $reworkRows->count(),
                'key' => BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
            ],
            [
                'label' => 'Review Queue',
                'value' => $reviewRows->count(),
                'key' => BillingWorkItem::STATUS_REVIEW,
            ],
            [
                'label' => 'Overdue',
                'value' => $overdueCount,
                'key' => 'overdue',
            ],
        ]);

        return [
            'cards' => [
                [
                    'label' => 'Avg Active Turnaround',
                    'value' => static::formatDurationShort($avgTurnaroundSeconds),
                    'description' => 'Average working time from start to completion, excluding clinic-wait pause time.',
                    'accent' => 'emerald',
                ],
                [
                    'label' => 'Avg Waiting on Clinic',
                    'value' => static::formatDurationShort($avgWaitingOnClinicSeconds),
                    'description' => 'Average current clinic-response wait time for requests paused on missing information.',
                    'accent' => 'indigo',
                ],
                [
                    'label' => 'Avg Rework Aging',
                    'value' => static::formatDurationShort($avgReworkAgeSeconds),
                    'description' => 'Average time requests have spent sitting in Returned for Rework.',
                    'accent' => 'amber',
                ],
                [
                    'label' => 'Avg Review Aging',
                    'value' => static::formatDurationShort($avgReviewAgeSeconds),
                    'description' => 'Average age of requests currently waiting in Review.',
                    'accent' => 'sky',
                ],
            ],
            'snapshot' => [
                'due_today' => $dueTodayCount,
                'overdue' => $overdueCount,
                'waiting_on_clinic' => $awaitingRows->count(),
                'returned_for_rework' => $reworkRows->count(),
                'review' => $reviewRows->count(),
            ],
            'bars' => $bars,
        ];
    }

    public static function trendChart(Builder $query, array $filters): array
    {
        $from = filled($filters['from_date'] ?? null)
            ? Carbon::parse($filters['from_date'])->startOfDay()
            : now()->startOfMonth();
        $to = filled($filters['to_date'] ?? null)
            ? Carbon::parse($filters['to_date'])->startOfDay()
            : now()->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $days = Collection::times($from->diffInDays($to) + 1, fn (int $index): Carbon => $from->copy()->addDays($index - 1));

        $createdTotals = (clone $query)
            ->selectRaw('DATE(created_at) as trend_date, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'trend_date');

        $completedTotals = (clone $query)
            ->whereNotNull('completed_at')
            ->selectRaw('DATE(completed_at) as trend_date, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(completed_at)'))
            ->pluck('total', 'trend_date');

        $labels = [];
        $created = [];
        $completed = [];

        foreach ($days as $day) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('M j');
            $created[] = (int) ($createdTotals[$key] ?? 0);
            $completed[] = (int) ($completedTotals[$key] ?? 0);
        }

        $max = max(max($created ?: [0]), max($completed ?: [0]), 1);

        return [
            'labels' => $labels,
            'created' => $created,
            'completed' => $completed,
            'created_points' => static::buildPolylinePoints($created, $max),
            'completed_points' => static::buildPolylinePoints($completed, $max),
            'max' => $max,
            'total_created' => array_sum($created),
            'total_completed' => array_sum($completed),
        ];
    }

    public static function statusBreakdown(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row): array => [
                'label' => BillingWorkItem::STATUS_OPTIONS[BillingWorkItem::normalizeStatus($row->status)] ?? str($row->status)->headline()->toString(),
                'value' => (int) $row->total,
                'key' => BillingWorkItem::normalizeStatus($row->status),
            ])
            ->all();
    }

    public static function outcomeBreakdown(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('outcome_status, COUNT(*) as total')
            ->groupBy('outcome_status')
            ->orderBy('outcome_status')
            ->get()
            ->map(fn ($row): array => [
                'label' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$row->outcome_status] ?? str($row->outcome_status)->headline()->toString(),
                'value' => (int) $row->total,
                'key' => (string) $row->outcome_status,
            ])
            ->all();
    }

    public static function sourceBreakdown(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('source, COUNT(*) as total')
            ->groupBy('source')
            ->orderBy('source')
            ->get()
            ->map(fn ($row): array => [
                'label' => BillingWorkItem::SOURCE_OPTIONS[$row->source] ?? str($row->source)->headline()->toString(),
                'value' => (int) $row->total,
                'key' => (string) $row->source,
            ])
            ->all();
    }

    public static function assigneeBreakdown(Builder $query): array
    {
        return (clone $query)
            ->leftJoin('users as assignees', 'billing_work_items.assigned_to', '=', 'assignees.id')
            ->selectRaw("COALESCE(assignees.name, 'Unassigned') as assignee_name, COUNT(*) as total")
            ->groupBy('assignee_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->assignee_name,
                'value' => (int) $row->total,
            ])
            ->all();
    }

    public static function barVisualization(array $rows): array
    {
        $max = max(array_column($rows, 'value') ?: [1]);

        return array_map(function (array $row) use ($max): array {
            $row['width'] = $max > 0 ? max(($row['value'] / $max) * 100, 8) : 0;

            return $row;
        }, $rows);
    }

    public static function recentRows(Builder $query, int $limit = 10): array
    {
        return (clone $query)
            ->with(['clinic', 'patient', 'assignedTo', 'verificationProfile'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (BillingWorkItem $row): array => static::mapExportRow($row))
            ->all();
    }

    public static function exportRows(Builder $query): array
    {
        return (clone $query)
            ->with(['clinic', 'location', 'patient', 'provider.user', 'assignedTo', 'verificationProfile'])
            ->latest('created_at')
            ->get()
            ->map(fn (BillingWorkItem $row): array => static::mapExportRow($row))
            ->all();
    }

    public static function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys(static::exportHeadings()));

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    public static function excelHtml(array $rows, array $meta = []): string
    {
        return view('exports.verification-reports.table', [
            'rows' => $rows,
            'meta' => $meta,
            'format' => 'excel',
            'headings' => static::exportHeadings(),
        ])->render();
    }

    public static function wordHtml(array $rows, array $meta = []): string
    {
        return view('exports.verification-reports.table', [
            'rows' => $rows,
            'meta' => $meta,
            'format' => 'word',
            'headings' => static::exportHeadings(),
        ])->render();
    }

    public static function pdf(array $rows, array $meta = []): string
    {
        return Pdf::loadView('exports.verification-reports.table', [
            'rows' => $rows,
            'meta' => $meta,
            'format' => 'pdf',
            'headings' => static::exportHeadings(),
        ])->setPaper('a4', 'landscape')->output();
    }

    public static function clinicOptions(): array
    {
        return AdminClinicScope::clinics()
            ->mapWithKeys(fn (Clinic $clinic): array => [
                $clinic->getKey() => (string) $clinic->clinic_name,
            ])
            ->all();
    }

    public static function locationOptions(?int $clinicId): array
    {
        return Location::query()
            ->when(
                blank($clinicId) && auth()->user() && ! auth()->user()->hasFullVerificationClinicAccess(),
                fn (Builder $query) => $query->whereIn('clinic_id', auth()->user()->verificationAccessibleClinicIds())
            )
            ->when($clinicId, fn (Builder $query, $id) => $query->where('clinic_id', $id))
            ->orderBy('location_name')
            ->pluck('location_name', 'id')
            ->all();
    }

    public static function providerOptions(?int $clinicId): array
    {
        return Provider::query()
            ->when(
                blank($clinicId) && auth()->user() && ! auth()->user()->hasFullVerificationClinicAccess(),
                fn (Builder $query) => $query->whereIn('clinic_id', auth()->user()->verificationAccessibleClinicIds())
            )
            ->when($clinicId, fn (Builder $query, $id) => $query->where('clinic_id', $id))
            ->orderBy('display_name')
            ->pluck('display_name', 'id')
            ->all();
    }

    public static function assigneeOptions(?int $clinicId = null): array
    {
        $viewer = auth()->user();
        $viewerClinicIds = $viewer?->hasFullVerificationClinicAccess() ? [] : ($viewer?->verificationAccessibleClinicIds() ?? []);

        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', array_keys(User::verificationRoleOptions())))
            ->with(['roles', 'permissions', 'verificationClinics'])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $user->canAccessVerificationWorkspace()
                && (
                    filled($clinicId)
                        ? $user->canAccessVerificationClinic((int) $clinicId)
                        : ($viewer?->hasFullVerificationClinicAccess()
                            || count(array_intersect($viewerClinicIds, $user->verificationAccessibleClinicIds())) > 0)
                ))
            ->pluck('name', 'id')
            ->all();
    }

    public static function workedByOptions(?int $clinicId = null): array
    {
        $userIds = BillingWorkItemActivity::query()
            ->when(
                blank($clinicId) && auth()->user() && ! auth()->user()->hasFullVerificationClinicAccess(),
                function ($query): void {
                    $query->whereHas('workItem', fn (Builder $workItemQuery) => $workItemQuery->whereIn(
                        'clinic_id',
                        auth()->user()->verificationAccessibleClinicIds()
                    ));
                }
            )
            ->when($clinicId, function ($query, $id): void {
                $query->whereHas('workItem', fn (Builder $workItemQuery) => $workItemQuery->where('clinic_id', $id));
            })
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->filter()
            ->all();

        if (empty($userIds)) {
            return [];
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', array_keys(User::verificationRoleOptions())))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function formTypeOptions(): array
    {
        return VerificationProfile::FORM_TYPE_OPTIONS;
    }

    public static function insuranceStatusOptions(): array
    {
        return [
            'active' => 'Active Insurance',
            'inactive' => 'Inactive Insurance',
        ];
    }

    public static function workflowExceptionOptions(): array
    {
        return [
            'active_exception' => 'Any Exception State',
            BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE => 'Awaiting Clinic Response',
            BillingWorkItem::STATUS_RETURNED_FOR_REWORK => 'Returned for Rework',
        ];
    }

    public static function exportHeadings(): array
    {
        return [
            'Reference' => 'reference',
            'Patient' => 'patient',
            'Clinic' => 'clinic',
            'Location' => 'location',
            'Provider' => 'provider',
            'Status' => 'status',
            'Outcome' => 'outcome',
            'Priority' => 'priority',
            'Source' => 'source',
            'Assigned To' => 'assigned_to',
            'Created' => 'created_at',
            'Due' => 'due_at',
            'Completed' => 'completed_at',
        ];
    }

    protected static function mapExportRow(BillingWorkItem $row): array
    {
        $patientName = $row->patient?->full_name
            ?: $row->verificationProfile?->patient_full_name
            ?: static::patientNameFromTitle($row->title);

        return [
            'Reference' => $row->reference_number ?: '-',
            'Patient' => $patientName ?: '-',
            'Clinic' => $row->clinic?->clinic_name ?: '-',
            'Location' => $row->location?->location_name ?: '-',
            'Provider' => $row->provider?->display_name ?: '-',
            'Status' => BillingWorkItem::STATUS_OPTIONS[$row->normalized_status] ?? str($row->normalized_status)->headline()->toString(),
            'Outcome' => BillingWorkItem::OUTCOME_STATUS_OPTIONS[$row->outcome_status] ?? str($row->outcome_status)->headline()->toString(),
            'Priority' => BillingWorkItem::PRIORITY_OPTIONS[$row->priority] ?? str($row->priority)->headline()->toString(),
            'Source' => BillingWorkItem::SOURCE_OPTIONS[$row->source] ?? str($row->source)->headline()->toString(),
            'Assigned To' => $row->assignedTo?->name ?: 'Unassigned',
            'Created' => optional($row->created_at)->format('Y-m-d H:i'),
            'Due' => optional($row->due_at)->format('Y-m-d H:i'),
            'Completed' => optional($row->completed_at)->format('Y-m-d H:i'),
        ];
    }

    protected static function patientNameFromTitle(?string $title): ?string
    {
        if (blank($title)) {
            return null;
        }

        $segments = collect(explode(' - ', (string) $title))
            ->map(fn (string $segment): string => trim($segment))
            ->filter();

        if ($segments->count() >= 2) {
            return $segments->last();
        }

        return null;
    }

    protected static function buildPolylinePoints(array $values, float $maxValue): string
    {
        $width = 640;
        $height = 220;
        $padding = 20;
        $count = count($values);

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $padding . ',' . ($height - $padding);
        }

        return collect($values)
            ->map(function (float|int $value, int $index) use ($count, $width, $height, $padding, $maxValue): string {
                $x = $padding + (($width - ($padding * 2)) / max($count - 1, 1)) * $index;
                $ratio = $maxValue > 0 ? ($value / $maxValue) : 0;
                $y = $height - $padding - (($height - ($padding * 2)) * $ratio);

                return round($x, 2) . ',' . round($y, 2);
            })
            ->implode(' ');
    }

    protected static function averageSeconds(array $values): int
    {
        $filtered = array_values(array_filter($values, fn ($value): bool => (int) $value > 0));

        if ($filtered === []) {
            return 0;
        }

        return (int) round(array_sum($filtered) / count($filtered));
    }

    protected static function formatDurationShort(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0h';
        }

        $hours = round($seconds / 3600, 1);

        if ($hours < 24) {
            return rtrim(rtrim(number_format($hours, 1, '.', ''), '0'), '.') . 'h';
        }

        $days = floor($hours / 24);
        $remainingHours = round($hours - ($days * 24));

        if ($remainingHours <= 0) {
            return $days . 'd';
        }

        return $days . 'd ' . $remainingHours . 'h';
    }
}
