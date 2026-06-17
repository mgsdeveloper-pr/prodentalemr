<?php

namespace App\Filament\Clinic\Pages;

use App\Filament\Clinic\Resources\VerificationRequests\VerificationRequestResource;
use App\Filament\Admin\Pages\VerificationRequestResponse as AdminVerificationRequestResponse;
use App\Models\BillingWorkItem;
use App\Models\BillingWorkItemActivity;
use App\Models\BillingWorkItemAttachment;
use App\Support\ClinicPanelScope;
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

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?string $navigationLabel = 'Request & Response';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessClinicVerificationRequests() ?? false;
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
            ->where('source', '!=', 'clinic_self_service')
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
        if (! $workItem->clinicUserCanEditVerification(auth()->user())) {
            return false;
        }

        if ($workItem->normalized_status === BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE) {
            return true;
        }

        $hasRequest = $workItem->activities->contains(
            fn (BillingWorkItemActivity $activity): bool => $activity->activity_type === self::REQUEST_ACTIVITY
        );
        $hasResponse = $workItem->activities->contains(
            fn (BillingWorkItemActivity $activity): bool => $activity->activity_type === self::RESPONSE_ACTIVITY
        );

        return $hasRequest && ! $hasResponse;
    }

    public function canShowResponseEdit(BillingWorkItem $workItem): bool
    {
        if (! $workItem->clinicUserCanEditVerification(auth()->user())) {
            return false;
        }

        return $workItem->activities->contains(
            fn (BillingWorkItemActivity $activity): bool => $activity->activity_type === self::RESPONSE_ACTIVITY
        ) || filled($workItem->clinic_responded_at);
    }

    public function openResponseComposer(int $workItemId): void
    {
        $workItem = $this->query()->findOrFail($workItemId);

        abort_unless($this->canShowResponseShortcut($workItem) || $this->canShowResponseEdit($workItem), 403);

        $latestResponse = $workItem->activities
            ->where('activity_type', self::RESPONSE_ACTIVITY)
            ->sortByDesc('created_at')
            ->first();

        $this->responseComposerWorkItemId = $workItem->getKey();
        $this->responseComposerNote = trim((string) data_get($latestResponse?->meta, 'clinic_response_note', $workItem->notes ?: ''));
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

        abort_unless($this->canShowResponseShortcut($workItem) || $this->canShowResponseEdit($workItem), 403);

        $responseNote = trim($this->responseComposerNote);
        $responseStartedAt = now();
        $existingResponse = $workItem->activities()
            ->where('activity_type', self::RESPONSE_ACTIVITY)
            ->latest('created_at')
            ->first();

        if ($existingResponse) {
            $workItem->notes = $responseNote;
            $workItem->clinic_responded_at ??= now();
            $workItem->clinic_responded_by_user_id ??= auth()->id();
            $workItem->save();
            $this->persistResponseComposerAttachments($workItem);

            $meta = $existingResponse->meta ?? [];
            $meta['clinic_response_note'] = $responseNote;
            $meta['responded_by_role'] = 'clinic';
            $meta['edited_at'] = now()->toDateTimeString();
            $meta['edited_by_user_id'] = auth()->id();

            $existingResponse->forceFill([
                'description' => 'Clinic response updated.',
                'meta' => $meta,
            ])->save();

            $this->selectedWorkItemId = $workItem->getKey();
            $this->showResponseComposerModal = false;
            $this->responseComposerWorkItemId = null;
            $this->closeResponseComposer();

            Notification::make()
                ->title('Response updated')
                ->body('The latest clinic response has been updated for the verification team.')
                ->success()
                ->send();

            return;
        }

        $workItem->notes = $responseNote;
        $workItem->transitionStatus(BillingWorkItem::STATUS_IN_PROGRESS);
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

    public function responseAttachmentDownloadUrl(BillingWorkItemAttachment $attachment): string
    {
        return route('clinic.billing-work-item-attachments.download', $attachment);
    }

    protected function query(): Builder
    {
        $query = ClinicPanelScope::apply(BillingWorkItem::query(), 'clinic_id')
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
                'activities' => fn ($builder) => $builder
                    ->whereIn('activity_type', [self::REQUEST_ACTIVITY, self::RESPONSE_ACTIVITY])
                    ->with('user')
                    ->latest('created_at'),
                'attachments' => fn ($builder) => $builder->latest('created_at'),
            ])
            ->when($this->statusFilter === 'open', fn (Builder $builder) => $builder->where('status', BillingWorkItem::STATUS_AWAITING_CLINIC_RESPONSE))
            ->when($this->statusFilter === 'responded', fn (Builder $builder) => $builder->whereNotNull('clinic_responded_at'))
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
