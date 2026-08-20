<?php

namespace App\Support\WorkContext\Providers;

use App\Models\BillingWorkItem;
use App\Support\WorkContext\ContextCard;
use App\Support\WorkContext\ContextProviderInterface;
use App\Support\WorkContext\WorkContext;
use Illuminate\Support\Collection;

class VerificationContextProvider implements ContextProviderInterface
{
    public function __construct(
        protected BillingWorkItem $record,
        protected array $quickReference = [],
        protected iterable $summaryCards = [],
        protected iterable $attachments = [],
        protected iterable $timeline = [],
        protected ?string $copyText = null,
    ) {}

    public function context(): WorkContext
    {
        return new WorkContext(
            title: 'Work Context',
            description: 'Context supplied by the Verification workspace provider.',
            cards: [
                $this->quickReferenceCard(),
                $this->verificationSummaryCard(),
                $this->patientSummaryCard(),
                $this->insuranceSummaryCard(),
                $this->notesCard(),
                $this->attachmentsCard(),
                $this->timelineCard(),
                $this->metadataCard(),
                $this->aiPlaceholderCard(),
            ],
            search: [
                'enabled' => false,
                'placeholder' => 'Context Search',
            ],
            ai: [
                'enabled' => false,
                'title' => 'AI Assistant',
            ],
        );
    }

    protected function quickReferenceCard(): ContextCard
    {
        $actions = filled($this->copyText)
            ? [[
                'label' => 'Copy',
                'onclick' => 'copyVerificationQuickReference(' . json_encode($this->copyText, JSON_HEX_APOS | JSON_HEX_QUOT) . ', this)',
            ]]
            : [];

        return new ContextCard(
            title: 'Quick Reference',
            type: 'rows',
            items: $this->rows([
                'Patient Name' => $this->quickReference['patient'] ?? '-',
                'Patient DOB' => $this->quickReference['dob'] ?? '-',
                'Member ID' => $this->quickReference['member_id'] ?? '-',
                'Subscriber' => $this->quickReference['subscriber_name'] ?? '-',
                'Subscriber DOB' => $this->quickReference['subscriber_dob'] ?? '-',
                'Coverage Role' => $this->quickReference['coverage_role'] ?? '-',
                'Insurance / TPA' => $this->quickReference['insurance_name'] ?? '-',
                'Insurance Phone' => $this->quickReference['phone'] ?? '-',
            ]),
            badge: 'Pinned',
            actions: $actions,
            pinned: true,
            scrollable: true,
        );
    }

    protected function verificationSummaryCard(): ContextCard
    {
        $status = BillingWorkItem::STATUS_OPTIONS[$this->record->normalized_status]
            ?? str($this->record->normalized_status)->headline()->toString();

        return new ContextCard(
            title: 'Verification Summary',
            type: 'rows',
            items: $this->rows([
                'Status' => $status,
                'Assigned User' => $this->record->assignedTo?->name ?? 'Unassigned',
                'Due Date' => $this->record->due_at?->format('M d, Y h:i A') ?? '-',
                'Priority' => BillingWorkItem::PRIORITY_OPTIONS[$this->record->priority]
                    ?? str((string) $this->record->priority)->headline()->toString(),
            ]),
            badge: $status,
        );
    }

    protected function patientSummaryCard(): ContextCard
    {
        return new ContextCard(
            title: 'Patient Summary',
            type: 'rows',
            items: $this->rows([
                'Patient' => $this->quickReference['patient'] ?? ($this->record->patient?->full_name ?? '-'),
                'DOB' => $this->quickReference['dob'] ?? '-',
                'Provider' => $this->quickReference['provider_name'] ?? '-',
                'Provider NPI' => $this->quickReference['provider_npi'] ?? '-',
            ]),
            state: filled($this->quickReference['patient'] ?? null) ? 'expanded' : 'empty',
        );
    }

    protected function insuranceSummaryCard(): ContextCard
    {
        return new ContextCard(
            title: 'Insurance Summary',
            type: 'rows',
            items: $this->rows([
                'Carrier' => $this->quickReference['insurance_name'] ?? '-',
                'Member ID' => $this->quickReference['member_id'] ?? '-',
                'Group Number' => $this->quickReference['group_number'] ?? '-',
                'Phone' => $this->quickReference['phone'] ?? '-',
            ]),
        );
    }

    protected function notesCard(): ContextCard
    {
        $notes = collect([
            ['label' => 'Internal Notes', 'value' => $this->record->internal_summary ?: $this->record->notes ?: null],
        ])->filter(fn (array $row): bool => filled($row['value']))->values()->all();

        return new ContextCard(
            title: 'Internal Notes',
            type: 'rows',
            items: $notes,
            state: $notes === [] ? 'empty' : 'expanded',
        );
    }

    protected function attachmentsCard(): ContextCard
    {
        $items = $this->toCollection($this->attachments)
            ->map(fn (array $attachment): array => [
                'label' => $attachment['title'] ?? 'Attachment',
                'value' => $attachment['subtitle'] ?? ($attachment['uploaded_at'] ?? '-'),
                'href' => $attachment['download_url'] ?? null,
            ])
            ->values()
            ->all();

        return new ContextCard(
            title: 'Attachments',
            type: 'list',
            items: $items,
            badge: (string) count($items),
            state: $items === [] ? 'empty' : 'expanded',
            scrollable: true,
        );
    }

    protected function timelineCard(): ContextCard
    {
        $items = $this->toCollection($this->timeline)
            ->map(fn (array $event): array => [
                'label' => $event['type'] ?? 'Activity',
                'value' => $event['description'] ?? null,
                'meta' => collect([$event['author'] ?? null, $event['created_at'] ?? null])->filter()->implode(' | '),
            ])
            ->values()
            ->all();

        return new ContextCard(
            title: 'Timeline',
            type: 'timeline',
            items: $items,
            badge: (string) count($items),
            state: $items === [] ? 'empty' : 'collapsed',
            scrollable: true,
        );
    }

    protected function metadataCard(): ContextCard
    {
        return new ContextCard(
            title: 'Verification Metadata',
            type: 'rows',
            items: $this->rows([
                'Reference' => $this->record->reference_number,
                'Created' => $this->record->created_at?->format('M d, Y h:i A') ?? '-',
                'Updated' => $this->record->updated_at?->format('M d, Y h:i A') ?? '-',
            ]),
            state: 'collapsed',
        );
    }

    protected function aiPlaceholderCard(): ContextCard
    {
        return new ContextCard(
            title: 'AI Assistant',
            type: 'placeholder',
            items: [],
            description: 'Reserved for future context intelligence.',
            badge: 'Reserved',
            state: 'disabled',
        );
    }

    protected function rows(array $values): array
    {
        return collect($values)
            ->map(fn (mixed $value, string $label): array => [
                'label' => $label,
                'value' => filled($value) ? (string) $value : '-',
            ])
            ->values()
            ->all();
    }

    protected function toCollection(iterable $items): Collection
    {
        return $items instanceof Collection ? $items : collect($items);
    }
}
