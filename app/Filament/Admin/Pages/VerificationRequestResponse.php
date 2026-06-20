<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\Verifications\VerificationWorkItemResource;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Models\BillingWorkItemAttachment;
use App\Support\AdminClinicScope;
use App\Support\SaasEntitlements;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use UnitEnum;

class VerificationRequestResponse extends Page
{
    use WithFileUploads;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?string $navigationLabel = 'Request & Response';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = '';

    protected static ?string $slug = 'request-response';

    protected string $view = 'filament.admin.pages.verification-request-response';

    protected string $paginationTheme = 'tailwind';

    protected const REQUEST_ACTIVITY = 'info_requested_from_clinic';

    protected const RESPONSE_ACTIVITY = 'clinic_response_received';

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $selectedWorkItemId = null;

    public bool $showDetailsModal = false;

    public bool $showRequestComposerModal = false;

    public ?int $requestComposerWorkItemId = null;

    public string $requestComposerReason = '';

    public bool $showResponseComposerModal = false;

    public ?int $responseComposerWorkItemId = null;

    public string $responseComposerNote = '';

    public array $responseComposerAttachments = [];

    public ?int $selectedResponseAttachmentId = null;

    public bool $showResponseAttachmentPreview = false;

    public function selectStatusFilter(string $filter): void
    {
        $this->statusFilter = in_array($filter, ['all', 'open', 'responded', 'closed'], true)
            ? $filter
            : 'all';

        $this->resetPage();
    }

    public static function canAccess(): bool
    {
        return (auth()->user()?->canAccessVerificationWorkspace() ?? false)
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'request_response', AdminClinicScope::selectedClinic());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = AdminClinicScope::apply(BillingWorkItem::query(), 'clinic_id')
            ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
            ->where('source', '!=', 'clinic_self_service')
            ->where('status', '!=', BillingWorkItem::STATUS_DONE)
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNotNull('clinic_responded_at')
                    ->orWhereHas('activities', fn (Builder $activityQuery) => $activityQuery->where('activity_type', self::RESPONSE_ACTIVITY));
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openDetails(int $workItemId): void
    {
        $this->selectedWorkItemId = $workItemId;
        $this->showDetailsModal = true;
    }

    public function closeDetails(): void
    {
        $this->showDetailsModal = false;
        $this->closeResponseAttachmentPreview();
        $this->closeRequestComposer();
        $this->closeResponseComposer();
    }

    public function openRequestComposer(int $workItemId): void
    {
        $workItem = $this->query()->findOrFail($workItemId);

        abort_unless($this->canShowRequestShortcut($workItem), 403);

        $this->requestComposerWorkItemId = $workItem->getKey();
        $this->requestComposerReason = (string) ($workItem->info_request_reason ?? '');
        $this->resetErrorBag('requestComposerReason');
        $this->showRequestComposerModal = true;
    }

    public function closeRequestComposer(): void
    {
        $this->showRequestComposerModal = false;
        $this->requestComposerWorkItemId = null;
        $this->requestComposerReason = '';
        $this->resetErrorBag('requestComposerReason');
        $this->dispatch('verification-request-composer-closed');
    }

    public function sendRequestToClinic(): void
    {
        $this->validate([
            'requestComposerReason' => ['required', 'string', 'max:5000'],
        ], [
            'requestComposerReason.required' => 'Please explain what information is required from the clinic before sending this request.',
        ]);

        $workItem = $this->query()->findOrFail((int) $this->requestComposerWorkItemId);

        abort_unless($this->canShowRequestShortcut($workItem), 403);

        $reason = trim($this->requestComposerReason);
        $user = auth()->user();
        $actorRole = $this->resolveActorRole();

        $workItem->info_request_reason = $reason;
        $workItem->info_requested_by_user_id = $user?->getAuthIdentifier();
        $workItem->info_requested_by_role = $actorRole;
        $workItem->outcome_status = 'info_requested';

        if ($workItem->normalized_status !== BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            $workItem->transitionStatus(BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);
        } else {
            $workItem->save();

            $workItem->recordActivity(
                self::REQUEST_ACTIVITY,
                'Verification requested additional information from the clinic.',
                [
                    'info_request_reason' => $reason,
                    'requested_by_role' => $actorRole,
                    'follow_up' => true,
                ]
            );
        }

        $this->closeRequestComposer();
        $this->selectedWorkItemId = $workItem->getKey();

        Notification::make()
            ->title('Request sent to clinic')
            ->body('The request history has been updated with your latest clinic follow-up.')
            ->success()
            ->send();
    }

    public function canShowRequestShortcut(BillingWorkItem $workItem): bool
    {
        $user = auth()->user();

        if (! $user?->canWorkVerificationQueue()) {
            return false;
        }

        if ($workItem->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            return $user->canManageVerificationQueue()
                || (filled($workItem->assigned_to) && (int) $workItem->assigned_to === (int) $user->getAuthIdentifier());
        }

        return $workItem->canUserTransitionTo($user, BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE);
    }

    public function requestActionLabel(BillingWorkItem $workItem): string
    {
        return $workItem->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE
            ? 'Follow Up'
            : 'Request Again';
    }

    public function canShowResponseShortcut(BillingWorkItem $workItem): bool
    {
        return false;
    }

    public function canShowResponseEdit(BillingWorkItem $workItem): bool
    {
        return false;
    }

    public function canCloseRequestResponse(BillingWorkItem $workItem): bool
    {
        $user = auth()->user();

        if (! $user?->canWorkVerificationQueue()) {
            return false;
        }

        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            return false;
        }

        return filled($workItem->clinic_responded_at)
            || $workItem->activities->contains(
                fn (BillingWorkItemActivity $activity): bool => $activity->activity_type === self::RESPONSE_ACTIVITY
            );
    }

    public function closeRequestResponse(int $workItemId): void
    {
        $workItem = $this->query()->findOrFail($workItemId);

        abort_unless($this->canCloseRequestResponse($workItem), 403);

        $workItem->transitionStatus(BillingWorkItem::STATUS_DONE);
        $this->selectedWorkItemId = $workItem->getKey();
        $this->showDetailsModal = false;

        Notification::make()
            ->title('Request closed')
            ->body('The clinic response has been reviewed and moved to Closed Requests.')
            ->success()
            ->send();
    }

    public function openResponseComposer(int $workItemId): void
    {
        $workItem = $this->query()->findOrFail($workItemId);

        abort_unless($this->canShowResponseShortcut($workItem) || $this->canShowResponseEdit($workItem), 403);
    }

    public function closeResponseComposer(): void
    {
        $this->showResponseComposerModal = false;
        $this->responseComposerWorkItemId = null;
        $this->responseComposerNote = '';
        $this->responseComposerAttachments = [];
        $this->resetErrorBag('responseComposerNote');
        $this->resetErrorBag('responseComposerAttachments');
        $this->resetErrorBag('responseComposerAttachments.*');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getRows(): LengthAwarePaginator
    {
        return $this->query()
            ->orderByDesc(
                BillingWorkItemActivity::query()
                    ->select('created_at')
                    ->whereColumn('billing_work_item_id', 'billing_work_items.id')
                    ->whereIn('activity_type', [self::REQUEST_ACTIVITY, self::RESPONSE_ACTIVITY])
                    ->latest('created_at')
                    ->limit(1)
            )
            ->orderByDesc('id')
            ->paginate(12);
    }

    public function getSummary(): array
    {
        $query = $this->query();

        return collect([
            [
                'label' => 'Open Requests',
                'count' => (clone $query)->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)->count(),
                'tone' => 'amber',
                'filter' => 'open',
            ],
            [
                'label' => 'Responses Received',
                'count' => (clone $query)
                    ->where('status', '!=', BillingWorkItem::STATUS_DONE)
                    ->where(function (Builder $builder): void {
                        $builder
                            ->whereNotNull('clinic_responded_at')
                            ->orWhereHas('activities', fn (Builder $activityQuery) => $activityQuery->where('activity_type', self::RESPONSE_ACTIVITY));
                    })
                    ->count(),
                'tone' => 'sky',
                'filter' => 'responded',
            ],
            [
                'label' => 'Pending Review',
                'count' => (clone $query)->whereIn('status', [
                    BillingWorkItem::STATUS_IN_PROGRESS,
                    BillingWorkItem::STATUS_REVIEW,
                    BillingWorkItem::STATUS_RETURNED_FOR_REWORK,
                    BillingWorkItem::STATUS_INCOMPLETE,
                ])->count(),
                'tone' => 'violet',
                'filter' => 'all',
            ],
            [
                'label' => 'Closed Requests',
                'count' => (clone $query)->where('status', BillingWorkItem::STATUS_DONE)->count(),
                'tone' => 'emerald',
                'filter' => 'closed',
            ],
        ])->map(function (array $card): array {
            $styles = match ($card['tone']) {
                'amber' => ['border' => '#fde68a', 'bg' => '#fffbeb', 'text' => '#92400e'],
                'sky' => ['border' => '#bfdbfe', 'bg' => '#f8fbff', 'text' => '#1d4ed8'],
                'violet' => ['border' => '#ddd6fe', 'bg' => '#f5f3ff', 'text' => '#6d28d9'],
                'emerald' => ['border' => '#bbf7d0', 'bg' => '#f0fdf4', 'text' => '#166534'],
                default => ['border' => '#dbe4ee', 'bg' => '#ffffff', 'text' => '#334155'],
            };

            $isActive = $this->statusFilter === $card['filter']
                || ($card['filter'] === 'all' && ! in_array($this->statusFilter, ['open', 'responded', 'closed'], true));

            return array_merge($card, [
                'styles' => $styles,
                'is_active' => $isActive,
                'shadow' => $isActive
                    ? '0 14px 30px rgba(15, 23, 42, 0.12)'
                    : '0 8px 22px rgba(15, 23, 42, 0.05)',
                'active_display' => $isActive ? 'inline-flex' : 'none',
            ]);
        })->all();
    }

    public function getSelectedWorkItem(): ?BillingWorkItem
    {
        if (! $this->selectedWorkItemId) {
            return null;
        }

        return $this->query()->find($this->selectedWorkItemId);
    }

    public function getRequestHistory(BillingWorkItem $workItem): Collection
    {
        return $workItem->activities
            ->where('activity_type', self::REQUEST_ACTIVITY)
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (BillingWorkItemActivity $activity): array => [
                'title' => Str::limit(trim((string) data_get($activity->meta, 'info_request_reason', $activity->description)), 180),
                'message' => trim((string) data_get($activity->meta, 'info_request_reason', '')),
                'message_label' => 'Information asked',
                'message_fallback' => 'No request note captured',
                'actor' => $activity->user?->name ?: 'System',
                'role' => $this->formatRoleLabel((string) data_get($activity->meta, 'requested_by_role')),
                'source_label' => 'Verification',
                'target_label' => 'Clinic',
                'date' => optional($activity->created_at)->format('d M Y, h:i A') ?: '-',
            ]);
    }

    public function getResponseHistory(BillingWorkItem $workItem): Collection
    {
        $history = $workItem->activities
            ->where('activity_type', self::RESPONSE_ACTIVITY)
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (BillingWorkItemActivity $activity): array => [
                'title' => Str::limit(trim((string) data_get($activity->meta, 'clinic_response_note', $activity->description)), 180),
                'message' => trim((string) data_get($activity->meta, 'clinic_response_note', '')),
                'message_label' => 'Response received',
                'message_fallback' => 'No response note captured',
                'actor' => $activity->user?->name ?: 'System',
                'role' => $this->formatRoleLabel((string) data_get($activity->meta, 'responded_by_role')),
                'source_label' => 'Clinic',
                'target_label' => 'Verification',
                'date' => optional($activity->created_at)->format('d M Y, h:i A') ?: '-',
            ]);

        if ($history->isNotEmpty() || blank($workItem->clinic_responded_at)) {
            return $history;
        }

        $message = trim((string) $workItem->notes);

        return collect([[
            'title' => Str::limit($message ?: 'Clinic response received', 180),
            'message' => $message,
            'message_label' => 'Response received',
            'message_fallback' => 'No response note captured',
            'actor' => 'Clinic',
            'role' => 'Clinic',
            'source_label' => 'Clinic',
            'target_label' => 'Verification',
            'date' => optional($workItem->clinic_responded_at)->format('d M Y, h:i A') ?: '-',
        ]]);
    }

    public function presentRow(BillingWorkItem $workItem): array
    {
        $request = $workItem->activities->firstWhere('activity_type', self::REQUEST_ACTIVITY);
        $response = $this->resolveLatestResponse($workItem, $request);
        $responseCount = $workItem->activities->where('activity_type', self::RESPONSE_ACTIVITY)->count();

        if ($responseCount === 0 && filled($workItem->clinic_responded_at)) {
            $responseCount = 1;
        }

        $latestActivityAt = collect([$request?->created_at, $response?->created_at])->filter()->max();
        $status = $this->resolveWorkflowState($workItem, $request, $response);
        $statusStyles = match ($status['tone']) {
            'amber' => ['border' => '#fde68a', 'bg' => '#fffbeb', 'text' => '#92400e'],
            'sky' => ['border' => '#bfdbfe', 'bg' => '#eff6ff', 'text' => '#1d4ed8'],
            'emerald' => ['border' => '#bbf7d0', 'bg' => '#f0fdf4', 'text' => '#166534'],
            default => ['border' => '#dbe4ee', 'bg' => '#f8fafc', 'text' => '#475569'],
        };

        return [
            'patient_name' => $workItem->verificationProfile?->patient_full_name ?: ($workItem->patient?->full_name ?: 'Unknown patient'),
            'clinic_name' => $workItem->clinic?->clinic_name ?: '-',
            'request_raised' => $request
                ? Str::limit(trim((string) data_get($request->meta, 'info_request_reason', $request->description)), 90)
                : '-',
            'response_received' => $response
                ? Str::limit(trim((string) data_get($response->meta, 'clinic_response_note', $response->description)), 90)
                : ($request ? 'Waiting for clinic response' : '-'),
            'date_time' => $latestActivityAt?->format('d M Y, h:i A') ?: '-',
            'status' => $status,
            'status_styles' => $statusStyles,
            'request_count' => $workItem->activities->where('activity_type', self::REQUEST_ACTIVITY)->count(),
            'response_count' => $responseCount,
        ];
    }

    public function getResponseAttachments(BillingWorkItem $workItem): Collection
    {
        return $workItem->attachments
            ->filter(fn (BillingWorkItemAttachment $attachment): bool => str_contains((string) $attachment->file_path, '/clinic-response/')
                || str_contains((string) $attachment->file_path, '\\clinic-response\\')
                || strcasecmp((string) $attachment->title, 'Clinic response attachment') === 0)
            ->values();
    }

    public function openResponseAttachmentPreview(int $attachmentId): void
    {
        $this->selectedResponseAttachmentId = $attachmentId;
        $this->showResponseAttachmentPreview = true;
    }

    public function closeResponseAttachmentPreview(): void
    {
        $this->showResponseAttachmentPreview = false;
        $this->selectedResponseAttachmentId = null;
    }

    public function getSelectedResponseAttachment(): ?BillingWorkItemAttachment
    {
        $workItem = $this->getSelectedWorkItem();

        if (! $workItem || ! $this->selectedResponseAttachmentId) {
            return null;
        }

        return $this->getResponseAttachments($workItem)
            ->first(fn (BillingWorkItemAttachment $attachment): bool => (int) $attachment->getKey() === (int) $this->selectedResponseAttachmentId);
    }

    public function responseAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('saas.billing-work-item-attachments.download', $attachment);
    }

    public function openWorkItemUrl(BillingWorkItem $workItem): string
    {
        $user = auth()->user();

        if ($workItem->verificationUserCanEditVerification($user)) {
            return VerificationWorkItemResource::getUrl('edit', ['record' => $workItem]);
        }

        return VerificationWorkItemResource::getUrl('view', ['record' => $workItem]);
    }

    protected function query(): Builder
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
                'assignedTo',
                'closedBy',
                'activities' => fn ($builder) => $builder
                    ->whereIn('activity_type', [self::REQUEST_ACTIVITY, self::RESPONSE_ACTIVITY])
                    ->with('user')
                    ->latest('created_at'),
                'attachments' => fn ($builder) => $builder->latest('created_at'),
            ])
            ->when($this->statusFilter === 'open', fn (Builder $builder) => $builder->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE))
            ->when($this->statusFilter === 'responded', function (Builder $builder): void {
                $builder
                    ->where('status', '!=', BillingWorkItem::STATUS_DONE)
                    ->where(function (Builder $responseQuery): void {
                        $responseQuery
                            ->whereNotNull('clinic_responded_at')
                            ->orWhereHas('activities', fn (Builder $activityQuery) => $activityQuery->where('activity_type', self::RESPONSE_ACTIVITY));
                    });
            })
            ->when($this->statusFilter === 'closed', fn (Builder $builder) => $builder->where('status', BillingWorkItem::STATUS_DONE))
            ->when(filled($this->search), function (Builder $builder): void {
                $search = '%' . trim($this->search) . '%';

                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('reference_number', 'like', $search)
                        ->orWhere('title', 'like', $search)
                        ->orWhereHas('clinic', fn (Builder $clinicQuery) => $clinicQuery->where('clinic_name', 'like', $search))
                        ->orWhereHas('verificationProfile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('patient_full_name', 'like', $search)
                                ->orWhere('requested_by_name', 'like', $search)
                                ->orWhere('insurance_provider_name', 'like', $search);
                        })
                        ->orWhereHas('activities', function (Builder $activityQuery) use ($search): void {
                            $activityQuery
                                ->whereIn('activity_type', [self::REQUEST_ACTIVITY, self::RESPONSE_ACTIVITY])
                                ->where(function (Builder $innerQuery) use ($search): void {
                                    $innerQuery
                                        ->where('description', 'like', $search)
                                        ->orWhere('meta->info_request_reason', 'like', $search)
                                        ->orWhere('meta->clinic_response_note', 'like', $search);
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
        $response = $workItem->activities
            ->where('activity_type', self::RESPONSE_ACTIVITY)
            ->when($request, fn (Collection $items) => $items->filter(
                fn (BillingWorkItemActivity $activity): bool => optional($activity->created_at)?->greaterThanOrEqualTo($request->created_at) ?? false
            ))
            ->sortByDesc('created_at')
            ->first();

        if ($response || blank($workItem->clinic_responded_at)) {
            return $response;
        }

        $fallback = new BillingWorkItemActivity([
            'activity_type' => self::RESPONSE_ACTIVITY,
            'description' => 'Clinic response received',
            'meta' => ['clinic_response_note' => $workItem->notes],
        ]);
        $fallback->created_at = $workItem->clinic_responded_at;

        return $fallback;
    }

    protected function resolveWorkflowState(BillingWorkItem $workItem, ?BillingWorkItemActivity $request, ?BillingWorkItemActivity $response): array
    {
        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            return ['label' => 'Closed Request', 'tone' => 'emerald'];
        }

        if ($response) {
            return ['label' => 'Response Received', 'tone' => 'sky'];
        }

        if ($request) {
            return ['label' => 'Waiting on Clinic', 'tone' => 'amber'];
        }

        return ['label' => 'Pending', 'tone' => 'slate'];
    }

    public function getClosureSummary(BillingWorkItem $workItem): ?array
    {
        if ($workItem->normalized_status !== BillingWorkItem::STATUS_DONE) {
            return null;
        }

        return [
            'closed_by' => $workItem->closedBy?->name ?: 'System',
            'closed_at' => optional($workItem->completed_at)->format('d M Y, h:i A') ?: '-',
        ];
    }

    public function getWorkflowStatus(BillingWorkItem $workItem): array
    {
        $request = $workItem->activities->firstWhere('activity_type', self::REQUEST_ACTIVITY);
        $response = $this->resolveLatestResponse($workItem, $request);

        return $this->resolveWorkflowState($workItem, $request, $response);
    }

    public function workflowStatusStyles(string $tone): array
    {
        return match ($tone) {
            'amber' => ['border' => '#fde68a', 'bg' => '#fffbeb', 'text' => '#92400e'],
            'sky' => ['border' => '#bfdbfe', 'bg' => '#eff6ff', 'text' => '#1d4ed8'],
            'emerald' => ['border' => '#bbf7d0', 'bg' => '#f0fdf4', 'text' => '#166534'],
            default => ['border' => '#dbe4ee', 'bg' => '#f8fafc', 'text' => '#475569'],
        };
    }

    protected function formatRoleLabel(?string $role): string
    {
        return match ($role) {
            'clinic' => 'Clinic',
            'verification_manager' => 'Verification Manager',
            'verification_user' => 'Verification User',
            'admin' => 'Admin',
            default => filled($role) ? str($role)->replace('_', ' ')->headline()->toString() : 'System',
        };
    }

    protected function resolveActorRole(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return 'admin';
        }

        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole(['owner', 'super_admin', 'admin'])) {
                return 'admin';
            }

            if ($user->hasAnyRole(['revenue_manager', 'operations_manager', 'manager'])) {
                return 'verification_manager';
            }

            if ($user->hasAnyRole(['clinic_admin', 'clinic_manager', 'front_desk', 'scheduler'])) {
                return 'clinic';
            }
        }

        if (filled($user->clinic_id)) {
            return 'clinic';
        }

        return 'verification_user';
    }
}
