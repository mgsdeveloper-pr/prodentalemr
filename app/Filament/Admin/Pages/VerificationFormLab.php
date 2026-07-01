<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Support\AdminClinicScope;
use App\Support\SaasEntitlements;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use UnitEnum;

class VerificationFormLab extends Page
{
    public string $labTemplateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY;

    public ?string $labSectionKey = null;

    public ?string $labSubSectionKey = null;

    public string $labNewSectionLabel = '';

    public string $labNewSubSectionLabel = '';

    public ?string $labParentSectionKey = null;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Verifications';

    protected static ?string $navigationLabel = 'Form Lab';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = '';

    protected static ?string $slug = 'verification-form-lab';

    protected string $view = 'filament.admin.pages.verification-form-lab';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canAccessVerificationWorkspace() ?? false)
            && SaasEntitlements::userFeatureAllowed($user, 'verification_requests', AdminClinicScope::selectedClinic());
    }

    public function mount(): void
    {
        $this->labTemplateKey = VerificationFormQuestion::defaultTemplateKey();
        $this->labSectionKey = array_key_first($this->getSectionOptions());
        $this->labSubSectionKey = null;
        $this->labParentSectionKey = $this->labSectionKey;
    }

    public function updatedLabTemplateKey(): void
    {
        $this->labSectionKey = array_key_first($this->getSectionOptions());
        $this->labSubSectionKey = null;
        $this->labParentSectionKey = $this->labSectionKey;
    }

    public function updatedLabSectionKey(): void
    {
        $subSections = $this->getSubSectionOptions();
        $this->labSubSectionKey = empty($subSections) ? null : array_key_first($subSections);
        $this->labParentSectionKey = $this->labSectionKey;
    }

    public function focusLabScope(string $sectionKey, ?string $subSectionKey = null): void
    {
        $this->labSectionKey = $sectionKey;
        $this->labParentSectionKey = $sectionKey;
        $this->labSubSectionKey = filled($subSectionKey) ? $subSectionKey : null;
    }

    public function getQuestionManagerUrl(): string
    {
        return VerificationFormQuestionResource::getUrl('index');
    }

    public function getQuestionBuilderUrl(): string
    {
        return VerificationFormQuestionResource::getUrl('create');
    }

    public function getQuestionArrangementUrl(): string
    {
        return url('/verification/verification-question-arrangement');
    }

    public function canManageLabSections(): bool
    {
        return auth()->user()?->canManageVerificationTemplateSections() ?? false;
    }

    public function createLabSection(): void
    {
        if (! $this->canManageLabSections()) {
            Notification::make()
                ->title('Permission denied')
                ->danger()
                ->send();

            return;
        }

        $clinic = AdminClinicScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->danger()
                ->send();

            return;
        }

        $label = trim($this->labNewSectionLabel);

        if ($label === '') {
            Notification::make()
                ->title('Enter a section name')
                ->warning()
                ->send();

            return;
        }

        $sectionKey = VerificationTemplateSection::makeSectionKey($label, null);
        $baseKey = $sectionKey;
        $counter = 2;

        while (VerificationTemplateSection::query()
            ->where('clinic_id', $clinic->id)
            ->where('template_key', $this->labTemplateKey)
            ->where('section_key', $sectionKey)
            ->exists()) {
            $sectionKey = $baseKey . '_' . $counter++;
        }

        VerificationTemplateSection::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'template_key' => $this->labTemplateKey,
            'section_key' => $sectionKey,
            'parent_section_key' => null,
            'label' => $label,
            'sort_order' => ((int) VerificationTemplateSection::query()
                ->where('clinic_id', $clinic->id)
                ->where('template_key', $this->labTemplateKey)
                ->whereNull('parent_section_key')
                ->max('sort_order')) + 10,
            'is_active' => true,
        ]);

        $this->labNewSectionLabel = '';
        $this->labSectionKey = $sectionKey;
        $this->labParentSectionKey = $sectionKey;
        $this->labSubSectionKey = null;

        Notification::make()
            ->title('Section created')
            ->success()
            ->send();
    }

    public function createLabSubSection(): void
    {
        if (! $this->canManageLabSections()) {
            Notification::make()
                ->title('Permission denied')
                ->danger()
                ->send();

            return;
        }

        $clinic = AdminClinicScope::selectedClinic();

        if (! $clinic) {
            Notification::make()
                ->title('Select a clinic first')
                ->danger()
                ->send();

            return;
        }

        $label = trim($this->labNewSubSectionLabel);
        $parentKey = $this->labParentSectionKey;

        if ($label === '') {
            Notification::make()
                ->title('Enter a sub-section name')
                ->warning()
                ->send();

            return;
        }

        if (! filled($parentKey)) {
            Notification::make()
                ->title('Choose a parent section')
                ->warning()
                ->send();

            return;
        }

        $sectionKey = VerificationTemplateSection::makeSectionKey($label, $parentKey);
        $baseKey = $sectionKey;
        $counter = 2;

        while (VerificationTemplateSection::query()
            ->where('clinic_id', $clinic->id)
            ->where('template_key', $this->labTemplateKey)
            ->where('section_key', $sectionKey)
            ->exists()) {
            $sectionKey = $baseKey . '_' . $counter++;
        }

        VerificationTemplateSection::query()->create([
            'organization_id' => $clinic->organization_id,
            'clinic_id' => $clinic->id,
            'template_key' => $this->labTemplateKey,
            'section_key' => $sectionKey,
            'parent_section_key' => $parentKey,
            'label' => $label,
            'sort_order' => ((int) VerificationTemplateSection::query()
                ->where('clinic_id', $clinic->id)
                ->where('template_key', $this->labTemplateKey)
                ->where('parent_section_key', $parentKey)
                ->max('sort_order')) + 10,
            'is_active' => true,
        ]);

        $this->labNewSubSectionLabel = '';
        $this->labSectionKey = $parentKey;
        $this->labSubSectionKey = $sectionKey;

        Notification::make()
            ->title('Sub-section created')
            ->success()
            ->send();
    }

    /**
     * @return array<int, array<string, int|string|null>>
     */
    public function getArrangementQuestionCards(): array
    {
        $clinicId = AdminClinicScope::selectedClinicId();
        $scopeKey = $this->getSelectedScopeKey();

        if (! filled($clinicId) || ! filled($scopeKey)) {
            return [];
        }

        $sectionKeys = [$scopeKey];

        if ($scopeKey === $this->labSectionKey && ! filled($this->labSubSectionKey)) {
            $childKeys = array_keys($this->getSubSectionOptions());

            if (! empty($childKeys)) {
                $sectionKeys = array_values(array_unique(array_merge($sectionKeys, $childKeys)));
            }
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', $this->labTemplateKey)
            ->where('is_active', true)
            ->whereIn('section_key', $sectionKeys)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerificationFormQuestion $question): array => [
                'id' => $question->getKey(),
                'prompt' => $question->prompt,
                'section_key' => $question->section_key,
                'section_label' => VerificationFormQuestion::sectionLabel($question->section_key, $question->template_key, $question->clinic_id),
                'sort_order' => $question->sort_order,
                'input_type' => VerificationFormQuestion::INPUT_TYPE_OPTIONS[$question->input_type] ?? str($question->input_type)->headline()->toString(),
            ])
            ->all();
    }

    /**
     * @param  array<int, int|string>  $orderedIds
     */
    public function saveArrangementOrder(array $orderedIds): bool
    {
        $clinicId = AdminClinicScope::selectedClinicId();
        $scopeKey = $this->getSelectedScopeKey();

        if (! filled($clinicId) || ! filled($scopeKey)) {
            Notification::make()
                ->title('Select a clinic and section first')
                ->danger()
                ->send();

            return false;
        }

        $cards = $this->getArrangementQuestionCards();
        $existingIds = collect($cards)
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->values()
            ->all();

        $incomingIds = collect($orderedIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (empty($existingIds)) {
            Notification::make()
                ->title('No questions available to reorder')
                ->warning()
                ->send();

            return false;
        }

        if ($incomingIds !== $existingIds && array_diff($existingIds, $incomingIds) === [] && array_diff($incomingIds, $existingIds) === []) {
            DB::transaction(function () use ($incomingIds): void {
                foreach ($incomingIds as $index => $id) {
                    VerificationFormQuestion::query()
                        ->whereKey($id)
                        ->update(['sort_order' => ($index + 1) * 10]);
                }
            });

            Notification::make()
                ->title('Question order saved')
                ->success()
                ->send();

            return true;
        }

        if ($incomingIds === $existingIds) {
            Notification::make()
                ->title('No order change detected')
                ->success()
                ->send();

            return false;
        }

        Notification::make()
            ->title('Invalid reorder payload')
            ->danger()
            ->send();

        return false;
    }

    public function getSelectedClinicName(): string
    {
        return AdminClinicScope::selectedClinic()?->clinic_name
            ?? 'No clinic selected';
    }

    public function getTemplateOptions(): array
    {
        return VerificationFormQuestion::templateOptionsForUi();
    }

    public function getSectionOptions(): array
    {
        return VerificationFormQuestion::topLevelSectionOptionsForTemplate(
            $this->labTemplateKey,
            AdminClinicScope::selectedClinicId(),
        );
    }

    public function getSubSectionOptions(): array
    {
        return VerificationFormQuestion::childSectionOptionsForTemplate(
            $this->labTemplateKey,
            AdminClinicScope::selectedClinicId(),
            $this->labSectionKey,
        );
    }

    public function getSelectedScopeKey(): ?string
    {
        return filled($this->labSubSectionKey)
            ? $this->labSubSectionKey
            : $this->labSectionKey;
    }

    public function getCurrentSectionLabel(): string
    {
        $label = $this->getSectionOptions()[$this->labSectionKey] ?? null;

        return filled($label) ? $label : 'Choose section';
    }

    public function getCurrentSubSectionLabel(): string
    {
        if (! filled($this->labSubSectionKey)) {
            return 'No sub-section';
        }

        return $this->getSubSectionOptions()[$this->labSubSectionKey] ?? 'No sub-section';
    }

    /**
     * @return array<int, array{key:string,label:string,is_builtin:bool,question_count:int,children:array<int, array{key:string,label:string,is_builtin:bool,question_count:int}>}>
     */
    public function getTemplateSectionTree(): array
    {
        $clinicId = AdminClinicScope::selectedClinicId();
        $topLevelOptions = $this->getSectionOptions();
        $tree = [];

        foreach ($topLevelOptions as $key => $label) {
            $children = [];

            foreach (VerificationFormQuestion::childSectionOptionsForTemplate($this->labTemplateKey, $clinicId, $key) as $childKey => $childLabel) {
                $children[] = [
                    'key' => $childKey,
                    'label' => $childLabel,
                    'is_builtin' => ! VerificationTemplateSection::query()
                        ->where('clinic_id', $clinicId)
                        ->where('template_key', $this->labTemplateKey)
                        ->where('section_key', $childKey)
                        ->exists(),
                    'question_count' => $this->countQuestionsForScope($childKey),
                ];
            }

            $tree[] = [
                'key' => $key,
                'label' => $label,
                'is_builtin' => ! VerificationTemplateSection::query()
                    ->where('clinic_id', $clinicId)
                    ->where('template_key', $this->labTemplateKey)
                    ->where('section_key', $key)
                    ->exists(),
                'question_count' => $this->countQuestionsForScope($key),
                'children' => $children,
            ];
        }

        return $tree;
    }

    public function countQuestionsForScope(string $sectionKey): int
    {
        $clinicId = AdminClinicScope::selectedClinicId();

        if (! filled($clinicId)) {
            return 0;
        }

        return VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', $this->labTemplateKey)
            ->where('section_key', $sectionKey)
            ->where('is_active', true)
            ->count();
    }

    public function getPreviewAnchor(): string
    {
        return match ($this->normalizeLabScopeKey($this->getSelectedScopeKey())) {
            'template_2_patient_subscriber' => '#uel-patient',
            'template_2_insurance' => '#uel-insurance',
            'template_2_maximums_deductibles',
            'template_2_coverage_category' => '#uel-maximums',
            'template_2_plan_provisions' => '#uel-maximums',
            'template_2_service_history' => '#uel-history',
            'template_2_frequency_percentage',
            'template_2_frequency_general',
            'template_2_frequency_basic',
            'template_2_frequency_major',
            'template_2_frequency_orthodontics' => '#uel-codes',
            'template_2_verification_information' => '#uel-verify',
            default => '#uel-patient',
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getPreviewSections(): array
    {
        return [
            ['label' => 'Patient & Subscriber', 'anchor' => '#uel-patient'],
            ['label' => 'Insurance', 'anchor' => '#uel-insurance'],
            ['label' => 'Maximums / Category / Provisions', 'anchor' => '#uel-maximums'],
            ['label' => 'Service History', 'anchor' => '#uel-history'],
            ['label' => 'Frequency & Percentage', 'anchor' => '#uel-codes'],
            ['label' => 'Verification Info', 'anchor' => '#uel-verify'],
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function getQuestionStats(): array
    {
        $clinicId = AdminClinicScope::selectedClinicId();
        $scopeKey = $this->getSelectedScopeKey();

        if (! filled($clinicId) || ! filled($scopeKey)) {
            return [
                'total' => 0,
                'notes' => 0,
                'multi' => 0,
                'conditional' => 0,
            ];
        }

        $query = VerificationFormQuestion::query()
            ->where('clinic_id', $clinicId)
            ->where('template_key', $this->labTemplateKey)
            ->where('is_active', true);

        if ($scopeKey === $this->labSectionKey && ! filled($this->labSubSectionKey)) {
            $sectionKeys = [$this->labSectionKey];
            $childKeys = array_keys($this->getSubSectionOptions());
            if (! empty($childKeys)) {
                $sectionKeys = array_values(array_unique(array_merge($sectionKeys, $childKeys)));
            }

            $query->whereIn('section_key', $sectionKeys);
        } else {
            $query->where('section_key', $scopeKey);
        }

        $questions = $query->get(['input_type', 'has_note', 'frequency_response_mode']);

        return [
            'total' => $questions->count(),
            'notes' => $questions->where('has_note', true)->count(),
            'multi' => $questions->whereIn('input_type', ['multi_select', 'frequency_row'])->count(),
            'conditional' => $questions->filter(fn (VerificationFormQuestion $question): bool => in_array($question->input_type, ['yes_no', 'select', 'frequency_row'], true))->count(),
        ];
    }

    public function getArrangementSummary(): string
    {
        $count = count($this->getArrangementQuestionCards());

        if ($count === 0) {
            return 'No active questions are configured in this scope yet.';
        }

        if (filled($this->labSubSectionKey)) {
            return "Drag the {$count} question(s) in this sub-section and save the new order.";
        }

        return "Drag the {$count} question(s) in this section scope and save the new order.";
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getSelectedSectionBehaviors(): array
    {
        return match ($this->normalizeLabScopeKey($this->getSelectedScopeKey())) {
            'template_2_patient_subscriber' => [
                [
                    'title' => 'Identity capture',
                    'description' => 'Best for patient name, member ID, subscriber name, and subscriber DOB using clean one-line fields.',
                ],
                [
                    'title' => 'Guided dropdowns',
                    'description' => 'Use dropdown answers for Relationship and COB so the clinic sees exactly the expected response format.',
                ],
                [
                    'title' => 'Quick reference ready',
                    'description' => 'Anything created here should feed the left quick-reference sidebar in the live verification form.',
                ],
            ],
            'template_2_insurance' => [
                [
                    'title' => 'Database-assisted answers',
                    'description' => 'Insurance Provider, Plan Type, and Fee Schedule should prefer dropdown-driven values wherever the database already has them.',
                ],
                [
                    'title' => 'Structured details',
                    'description' => 'Dates, payer ID, claims address, network status, and phone number should stay in predictable single-response fields.',
                ],
                [
                    'title' => 'Add-if-missing path',
                    'description' => 'If the insurer is not found, the clinic should still be able to add it without leaving the workflow.',
                ],
            ],
            'template_2_maximums_deductibles' => [
                [
                    'title' => 'One-line financial answers',
                    'description' => 'Keep annual max, remaining max, and deductible values as simple currency or number answers.',
                ],
                [
                    'title' => 'Separate family vs individual',
                    'description' => 'Individual and family deductible areas stay distinct so the verification team does not misread them.',
                ],
                [
                    'title' => 'No hidden logic',
                    'description' => 'This section should stay fast to enter, with optional notes only where the clinic truly needs context.',
                ],
            ],
            'template_2_plan_provisions' => [
                [
                    'title' => 'Conditional follow-up',
                    'description' => 'Yes/No and dropdown answers here can trigger a follow-up detail input or follow-up table when the answer requires extra explanation.',
                ],
                [
                    'title' => 'Examples',
                    'description' => 'Waiting Period = Yes should reveal waiting-period detail rows; COB = Other should reveal a note or detail field.',
                ],
                [
                    'title' => 'One-line final answer',
                    'description' => 'Even if multiple internal response controls exist, the verification form should still resolve into one clear final answer row.',
                ],
            ],
            'template_2_service_history' => [
                [
                    'title' => 'History-first capture',
                    'description' => 'Use a single combined answer field for code, service history, or last service date when the team only needs one line.',
                ],
                [
                    'title' => 'Note support',
                    'description' => 'Keep short note placeholders like prior frequency concern, major history, or replacement history only where useful.',
                ],
                [
                    'title' => 'Eligibility context',
                    'description' => 'This section supports decision-making, so the placeholders should guide what kind of history the clinic should enter.',
                ],
            ],
            'template_2_frequency_percentage',
            'template_2_frequency_general',
            'template_2_frequency_basic',
            'template_2_frequency_major',
            'template_2_frequency_orthodontics' => [
                [
                    'title' => 'Two row modes',
                    'description' => 'Each row can be either a Formal Question or an ADA/CDT Code, never both at once.',
                ],
                [
                    'title' => 'Current vs advanced response',
                    'description' => 'Current response locks the core fields like % and Frequency while still allowing clinic choices such as Pre-Auth and Notes; Advanced response exposes richer payer-rule detail.',
                ],
                [
                    'title' => 'Hide empty groups',
                    'description' => 'If a sub-section has no configured questions, the whole group should stay hidden in the live verification form.',
                ],
            ],
            'template_2_verification_information' => [
                [
                    'title' => 'System-led output',
                    'description' => 'This section should remain mostly generated by the system, with only deliberate human notes left open for editing.',
                ],
                [
                    'title' => 'Audit-friendly',
                    'description' => 'Representative name, reference number, and verification method should read clearly for export and downstream review.',
                ],
                [
                    'title' => 'Minimal manual effort',
                    'description' => 'The clinic or verifier should only need to add final comment context here, not re-enter structured data already captured above.',
                ],
            ],
            default => [
                [
                    'title' => 'Section logic',
                    'description' => 'Choose the section first, then define how the answer should be captured and when a follow-up should appear.',
                ],
            ],
        };
    }

    protected function normalizeLabScopeKey(?string $scopeKey): ?string
    {
        if (! is_string($scopeKey) || $scopeKey === '') {
            return $scopeKey;
        }

        return str_starts_with($scopeKey, 'template_3_')
            ? 'template_2_' . substr($scopeKey, strlen('template_3_'))
            : $scopeKey;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getAnswerPatternLibrary(): array
    {
        return [
            [
                'title' => 'Simple capture',
                'description' => 'Text, date, month/year, currency, and dropdown answers for one-line verification responses.',
            ],
            [
                'title' => 'Conditional response',
                'description' => 'Yes/No or select answers that reveal detail notes, extra text, or follow-up sub-questions when needed.',
            ],
            [
                'title' => 'Multi response',
                'description' => 'Use only when the clinic needs to choose several response flags but the final form should still resolve to one clean line.',
            ],
            [
                'title' => 'Frequency row',
                'description' => 'Special row builder for General, Basic, Major, and Orthodontics with Code or Formal Question mode plus Current or Advanced response.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getBuilderTracks(): array
    {
        return [
            [
                'eyebrow' => 'Phase 1',
                'title' => 'Builder shell',
                'description' => 'Trial the new verification-builder workspace, section map, and preview flow in a safe sandbox.',
                'status' => 'Ready now',
            ],
            [
                'eyebrow' => 'Phase 2',
                'title' => 'Question logic',
                'description' => 'Connect section, sub-section, answer-type, and conditional behavior cleanly before drag-and-drop.',
                'status' => 'Ready now',
            ],
            [
                'eyebrow' => 'Phase 3',
                'title' => 'Drag and order',
                'description' => 'Add SortableJS-powered section and question arrangement only after the builder foundation feels right.',
                'status' => 'Active now',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getModernStack(): array
    {
        return [
            [
                'title' => 'Tailwind CSS',
                'description' => 'Layout, spacing, visual rhythm, and responsive structure for the new builder surfaces.',
            ],
            [
                'title' => 'Flowbite',
                'description' => 'Reusable input and modal patterns once we convert this trial shell into production components.',
            ],
            [
                'title' => 'Heroicons + Alpine',
                'description' => 'Lightweight interaction, toggles, and icon polish without making the builder heavy.',
            ],
            [
                'title' => 'SortableJS',
                'description' => 'Now represented in the lab as the arrangement studio so we can validate drag-and-order behavior before deeper builder rollout.',
            ],
        ];
    }
}
