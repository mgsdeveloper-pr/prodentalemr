<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Admin\Pages\VerificationRequestResponse as AdminVerificationRequestResponse;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemAttachment;
use App\Services\Verification\WorkflowService;
use App\Support\ClinicPanelScope;
use App\Support\SaasEntitlements;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class VerificationRequestResponse extends AdminVerificationRequestResponse
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Verification';

    protected static ?string $navigationLabel = 'Clinic Responses';

    protected static ?int $navigationSort = 2;

    public ?string $returnToQueue = null;

    public function mount(): void
    {
        $queueUrl = route('filament.clinic.resources.verification-requests.index');
        $returnUrl = request()->query('return');
        $this->returnToQueue = is_string($returnUrl) && str_starts_with($returnUrl, $queueUrl)
            ? $returnUrl
            : null;

        $respondWorkItemId = request()->integer('respond');

        if ($respondWorkItemId <= 0) {
            return;
        }

        $this->statusFilter = 'open';
        $this->selectedWorkItemId = $respondWorkItemId;
        $this->openResponseComposer($respondWorkItemId);
    }

    public static function canAccess(): bool
    {
        return (auth()->user()?->canAccessClinicVerificationRequests() ?? false)
            && SaasEntitlements::userFeatureAllowed(auth()->user(), 'request_response', ClinicPanelScope::selectedClinic());
    }

    public static function getNavigationBadge(): ?string
    {
        $clinicId = ClinicPanelScope::selectedClinicId();

        if (! $clinicId) {
            return null;
        }

        $count = BillingWorkItem::query()
            ->where('clinic_id', $clinicId)
            ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
            ->where('processing_mode', BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE)
            ->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public function getSummary(): array
    {
        return collect(parent::getSummary())
            ->map(function (array $card): array {
            if (($card['filter'] ?? null) === 'closed') {
                $card['label'] = 'Closed Requests';
            }

                if (($card['filter'] ?? null) === 'responded') {
                    $card['label'] = 'Request Responded';
                }

                return $card;
            })
            ->all();
    }

    public function canShowRequestShortcut(BillingWorkItem $workItem): bool
    {
        return false;
    }

    public function canCloseRequestResponse(BillingWorkItem $workItem): bool
    {
        return false;
    }

    public function canShowResponseShortcut(BillingWorkItem $workItem): bool
    {
        return $workItem->clinicUserCanRespondToVerification(auth()->user());
    }

    public function canShowResponseEdit(BillingWorkItem $workItem): bool
    {
        return false;
    }

    public function responseWorkspaceTitle(): string
    {
        return 'Clinic Responses';
    }

    public function responseWorkspaceDescription(): string
    {
        return 'Review requests from the verification team and send clinic responses.';
    }

    public function responseWorkspaceScope(): string
    {
        return ClinicPanelScope::selectedClinic()?->clinic_name ?? 'All Clinics';
    }

    public function canExportResponseLog(): bool
    {
        return false;
    }

    public function openResponseComposer(int $workItemId): void
    {
        $workItem = $this->query()->findOrFail($workItemId);

        abort_unless($this->canShowResponseShortcut($workItem), 403);

        $this->selectedWorkItemId = $workItem->getKey();
        $this->responseComposerWorkItemId = $workItem->getKey();
        $this->responseComposerNote = '';
        $this->responseComposerAttachments = [];
        $this->resetErrorBag('responseComposerNote');
        $this->resetErrorBag('responseComposerAttachments');
        $this->resetErrorBag('responseComposerAttachments.*');
        $this->showResponseComposerModal = true;
    }

    public function sendClinicResponse(): void
    {
        $this->validate([
            'responseComposerNote' => ['required', 'string', 'max:5000'],
            'responseComposerAttachments.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ], [
            'responseComposerNote.required' => 'Please add the response details before sending it back to verification.',
            'responseComposerAttachments.*.max' => 'Each attachment must be 10 MB or smaller.',
            'responseComposerAttachments.*.mimes' => 'Attachments must be PDF, image, Word document, or DOCX files.',
        ]);

        $workItem = $this->query()->findOrFail((int) $this->responseComposerWorkItemId);

        abort_unless($this->canShowResponseShortcut($workItem), 403);

        $responseNote = trim($this->responseComposerNote);
        $responseStartedAt = now();

        $workItem->notes = $responseNote;
        $workItem = app(WorkflowService::class)->transition($workItem, BillingWorkItem::STATUS_IN_PROGRESS);
        $this->persistResponseComposerAttachments($workItem);

        $hasResponseActivity = $workItem->activities()
            ->where('activity_type', self::RESPONSE_ACTIVITY)
            ->where('created_at', '>=', $responseStartedAt->copy()->subSecond())
            ->exists();

        if (! $hasResponseActivity) {
            $workItem->clinic_responded_at ??= now();
            $workItem->clinic_responded_by_user_id ??= auth()->id();
            $workItem->save();

            $workItem->recordActivity(self::RESPONSE_ACTIVITY, 'Clinic responded and verification resumed.', [
                'clinic_response_note' => $responseNote,
                'responded_by_role' => 'clinic',
            ]);
        }

        $this->selectedWorkItemId = $workItem->getKey();
        $this->showResponseComposerModal = false;
        $this->responseComposerWorkItemId = null;
        $this->closeResponseComposer();

        Notification::make()
            ->title('Response sent')
            ->body('The verification team can now review the clinic response.')
            ->success()
            ->send();
    }

    protected function persistResponseComposerAttachments(BillingWorkItem $workItem): void
    {
        foreach ($this->responseComposerAttachments as $attachment) {
            if (! $attachment instanceof TemporaryUploadedFile) {
                continue;
            }

            $originalName = $attachment->getClientOriginalName();
            $storedName = now()->format('YmdHis') . '_' . Str::uuid()->toString() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $extension = $attachment->getClientOriginalExtension();
            $finalName = filled($extension) ? "{$storedName}.{$extension}" : $storedName;
            $storedPath = $attachment->storeAs(
                'billing-work-items/' . $workItem->getKey() . '/clinic-response',
                $finalName,
                'local'
            );

            $workItem->attachments()->create([
                'title' => 'Clinic response attachment',
                'file_path' => $storedPath,
                'original_file_name' => $originalName,
                'notes' => trim($this->responseComposerNote) ?: 'Uploaded while responding to a clinic information request.',
            ]);
        }
    }

    public function openWorkItemUrl(BillingWorkItem $workItem): string
    {
        return VerificationRequestResource::getUrl('view', ['record' => $workItem]);
    }

    public function verificationRequestIndexUrl(): string
    {
        return $this->returnToQueue ?? route('filament.clinic.resources.verification-requests.index');
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.clinic.resources.verification-requests.index') => 'Verification Requests',
            'Clinic Responses',
        ];
    }

    public function responseAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('clinic.verification-request-attachments.download', $attachment);
    }

    public function responseAttachmentPreviewUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('clinic.verification-request-attachments.preview', $attachment);
    }

    protected function query(): Builder
    {
        $query = ClinicPanelScope::apply(BillingWorkItem::query(), 'clinic_id')
            ->whereHas('managedBillingService', fn (Builder $builder) => $builder->where('category', 'verification'))
            ->where('processing_mode', BillingWorkItem::PROCESSING_MODE_MANAGED_SERVICE)
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

        if (! $user?->shouldBypassClinicScope()) {
            if (! $user?->organization_id || ! $user?->clinic_id) {
                return $query->whereRaw('1 = 0');
            }

            $query
                ->where('organization_id', $user->organization_id)
                ->where('clinic_id', $user->clinic_id);
        }

        return $query;
    }
}
