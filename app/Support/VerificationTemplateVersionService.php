<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Models\VerificationTemplateVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificationTemplateVersionService
{
    public function ensureMasterVersion(string $templateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($templateKey): VerificationTemplateVersion {
            $version = VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
                ->where('template_key', $templateKey)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->first();

            if (! $version) {
                $version = VerificationTemplateVersion::query()->create([
                    'template_key' => $templateKey,
                    'scope' => VerificationTemplateVersion::SCOPE_MASTER,
                    'version_number' => 1,
                    'name' => 'Master Template',
                    'form_type' => VerificationTemplateVersion::FORM_TYPE_BOTH,
                    'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_DEFAULT,
                    'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                    'is_active' => true,
                    'is_working_draft' => false,
                    'published_at' => now(),
                    'created_by' => auth()->id(),
                    'notes' => 'Initial published master template version.',
                ]);
            }

            VerificationTemplateSection::query()
                ->whereNull('template_version_id')
                ->whereNull('clinic_id')
                ->where('template_key', $templateKey)
                ->update(['template_version_id' => $version->id]);

            VerificationFormQuestion::query()
                ->whereNull('template_version_id')
                ->whereNull('clinic_id')
                ->where('template_key', $templateKey)
                ->update(['template_version_id' => $version->id]);

            return $version->refresh();
        });
    }

    public function ensureClinicPublishedVersion(Clinic $clinic, string $templateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($clinic, $templateKey): VerificationTemplateVersion {
            $existing = VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('clinic_id', $clinic->id)
                ->where('template_key', $templateKey)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->first();

            if ($existing) {
                return $existing;
            }

            $master = $this->ensureMasterVersion($templateKey);

            if (! in_array($master->clinic_visibility, [
                VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
                VerificationTemplateVersion::CLINIC_VISIBILITY_DEFAULT,
            ], true)) {
                $visibleMaster = VerificationTemplateVersion::query()
                    ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
                    ->where('template_key', $templateKey)
                    ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                    ->whereIn('clinic_visibility', [
                        VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
                        VerificationTemplateVersion::CLINIC_VISIBILITY_DEFAULT,
                    ])
                    ->latest('version_number')
                    ->latest('id')
                    ->first();

                if (! $visibleMaster) {
                    throw ValidationException::withMessages([
                        'template' => 'No published Master Template is currently available to clinics.',
                    ]);
                }

                $master = $visibleMaster;
            }

            $version = VerificationTemplateVersion::query()->create([
                'template_key' => $templateKey,
                'scope' => VerificationTemplateVersion::SCOPE_CLINIC,
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'parent_version_id' => $master->id,
                'source_version_id' => $master->id,
                'version_number' => 1,
                'name' => $clinic->clinic_name.' Master Template',
                'form_type' => $master->form_type ?: VerificationTemplateVersion::FORM_TYPE_BOTH,
                'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
                'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                'is_active' => true,
                'is_working_draft' => false,
                'published_at' => now(),
                'created_by' => auth()->id(),
                'notes' => 'Clinic working copy replicated from the active master template.',
            ]);

            $this->copySections($master, $version, $clinic);
            $this->copyQuestions($master, $version, $clinic);

            return $version->refresh();
        });
    }

    public function createDraftFromPublished(VerificationTemplateVersion $published): VerificationTemplateVersion
    {
        return $this->createDraftFromSource($published, [
            'name' => $published->name.' Draft',
            'form_type' => $published->form_type ?: VerificationTemplateVersion::FORM_TYPE_BOTH,
            'clinic_visibility' => $published->clinic_visibility ?: VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN,
        ]);
    }

    public function updateUnusedDraft(VerificationTemplateVersion $draft, array $data): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($draft, $data): VerificationTemplateVersion {
            $lockedDraft = VerificationTemplateVersion::query()
                ->lockForUpdate()
                ->findOrFail($draft->getKey());

            if (! $lockedDraft->canEditDirectly()) {
                throw ValidationException::withMessages([
                    'template' => $lockedDraft->lifecycleLockReason()
                        ?? 'Only an unused, unpublished draft can be edited directly.',
                ]);
            }

            $name = trim((string) ($data['name'] ?? $lockedDraft->name));
            $formType = (string) ($data['form_type'] ?? $lockedDraft->form_type);

            if ($name === '') {
                throw ValidationException::withMessages([
                    'name' => 'Enter a template name.',
                ]);
            }

            if (! array_key_exists($formType, VerificationTemplateVersion::FORM_TYPE_OPTIONS)) {
                throw ValidationException::withMessages([
                    'form_type' => 'Choose a valid form type.',
                ]);
            }

            $lockedDraft->forceFill([
                'name' => $name,
                'form_type' => $formType,
                'notes' => array_key_exists('notes', $data)
                    ? trim((string) $data['notes']) ?: null
                    : $lockedDraft->notes,
            ])->save();

            return $lockedDraft->refresh();
        });
    }

    public function deleteUnusedDraft(VerificationTemplateVersion $draft): void
    {
        DB::transaction(function () use ($draft): void {
            $lockedDraft = VerificationTemplateVersion::query()
                ->lockForUpdate()
                ->findOrFail($draft->getKey());

            if (! $lockedDraft->canDeletePermanently()) {
                throw ValidationException::withMessages([
                    'template' => $lockedDraft->lifecycleLockReason()
                        ?? 'Only an unused, unpublished draft can be deleted permanently.',
                ]);
            }

            $scope = $lockedDraft->scope;
            $templateKey = $lockedDraft->template_key;
            $clinicId = $lockedDraft->clinic_id;
            $wasWorkingDraft = (bool) $lockedDraft->is_working_draft;

            $lockedDraft->questions()->delete();
            $lockedDraft->sections()->delete();
            $lockedDraft->forceDelete();

            if ($wasWorkingDraft) {
                $replacementDraft = VerificationTemplateVersion::query()
                    ->where('scope', $scope)
                    ->where('template_key', $templateKey)
                    ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
                    ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                    ->when(! $clinicId, fn ($query) => $query->whereNull('clinic_id'))
                    ->latest('version_number')
                    ->latest('id')
                    ->first();

                if ($replacementDraft) {
                    $replacementDraft->forceFill(['is_working_draft' => true])->save();
                }
            }
        });
    }

    public function archiveUnusedDraft(VerificationTemplateVersion $draft): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($draft): VerificationTemplateVersion {
            $lockedDraft = VerificationTemplateVersion::query()
                ->lockForUpdate()
                ->findOrFail($draft->getKey());

            if (! $lockedDraft->canEditDirectly()) {
                throw ValidationException::withMessages([
                    'template' => $lockedDraft->lifecycleLockReason()
                        ?? 'Only an unused, unpublished draft can be archived.',
                ]);
            }

            $lockedDraft->forceFill([
                'status' => VerificationTemplateVersion::STATUS_ARCHIVED,
                'is_active' => false,
                'is_working_draft' => false,
            ])->save();

            return $lockedDraft->refresh();
        });
    }

    public function createDraftFromSource(?VerificationTemplateVersion $source, array $options = []): VerificationTemplateVersion
    {
        $templateKey = $source?->template_key ?? ($options['template_key'] ?? VerificationFormQuestion::DEFAULT_TEMPLATE_KEY);
        $scope = $source?->scope ?? ($options['scope'] ?? VerificationTemplateVersion::SCOPE_MASTER);
        $organizationId = $source?->organization_id ?? ($options['organization_id'] ?? null);
        $clinicId = $source?->clinic_id ?? ($options['clinic_id'] ?? null);
        $formType = $options['form_type'] ?? 'both';
        $clinicVisibility = $options['clinic_visibility'] ?? VerificationTemplateVersion::CLINIC_VISIBILITY_HIDDEN;
        $name = trim((string) ($options['name'] ?? ($source?->name ?: 'Master Template Draft')));
        $startingPoint = $options['starting_point'] ?? (filled($source) ? 'current_master' : 'fresh');

        return DB::transaction(function () use ($source, $templateKey, $scope, $organizationId, $clinicId, $formType, $clinicVisibility, $name, $startingPoint): VerificationTemplateVersion {
            VerificationTemplateVersion::query()
                ->where('scope', $scope)
                ->where('template_key', $templateKey)
                ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->when(! $clinicId, fn ($query) => $query->whereNull('clinic_id'))
                ->update(['is_working_draft' => false]);

            $nextVersionNumber = ((int) VerificationTemplateVersion::query()
                ->where('scope', $scope)
                ->where('template_key', $templateKey)
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->when(! $clinicId, fn ($query) => $query->whereNull('clinic_id'))
                ->max('version_number')) + 1;

            $draft = VerificationTemplateVersion::query()->create([
                'template_key' => $templateKey,
                'scope' => $scope,
                'organization_id' => $organizationId,
                'clinic_id' => $clinicId,
                'parent_version_id' => $source?->id,
                'source_version_id' => $source?->source_version_id ?: $source?->id,
                'version_number' => $nextVersionNumber,
                'name' => filled($name) ? $name : 'Master Template Draft',
                'form_type' => $formType,
                'clinic_visibility' => $clinicVisibility,
                'status' => VerificationTemplateVersion::STATUS_DRAFT,
                'is_active' => false,
                'is_working_draft' => true,
                'created_by' => auth()->id(),
                'notes' => $this->draftCreationNotes($source, $formType, $startingPoint),
            ]);

            if ($source) {
                $this->copySections($source, $draft, $source->clinic);
                $this->copyQuestions($source, $draft, $source->clinic);
                $this->filterDraftQuestionsForFormType($draft, $formType);
            }

            $this->normalizeTemplateThreeVersion($draft);

            return $draft->refresh();
        });
    }

    protected function draftCreationNotes(?VerificationTemplateVersion $source, string $formType, string $startingPoint): string
    {
        $formLabel = match ($formType) {
            'full_form' => 'Full Form',
            'short_form' => 'Short Form',
            default => 'Full + Short',
        };

        if (! $source) {
            return 'Fresh draft created for '.$formLabel.'.';
        }

        $sourceLabel = $startingPoint === 'specific_version'
            ? 'specific version '.$source->version_number
            : 'current master version '.$source->version_number;

        return 'Draft replicated from '.$sourceLabel.' for '.$formLabel.'.';
    }

    protected function filterDraftQuestionsForFormType(VerificationTemplateVersion $draft, string $formType): void
    {
        $allowedFormTypes = match ($formType) {
            'full_form' => ['full_form', 'both'],
            'short_form' => ['short_form', 'both'],
            default => ['full_form', 'short_form', 'both'],
        };

        VerificationFormQuestion::query()
            ->where('template_version_id', $draft->id)
            ->where('template_key', $draft->template_key)
            ->whereNotIn('form_type', $allowedFormTypes)
            ->delete();
    }

    public function normalizeTemplateThreeVersion(VerificationTemplateVersion $version): VerificationTemplateVersion
    {
        if ($version->template_key !== VerificationFormQuestion::DEFAULT_TEMPLATE_KEY) {
            return $version;
        }

        return DB::transaction(function () use ($version): VerificationTemplateVersion {
            $sectionDefinitions = [
                ['template_3_patient_subscriber', null, 'Patient & Subscriber Information', 10],
                ['template_3_insurance', null, 'Insurance Information', 20],
                ['template_3_maximums_deductibles', null, 'Maximums & Deductibles', 30],
                ['template_3_coverage_category', null, 'Deductible & Coverage Category', 40],
                ['template_3_plan_provisions', null, 'Plan Provisions', 50],
                ['template_3_service_history', null, 'Service History', 60],
                ['template_3_frequency_percentage', null, 'Frequency & Percentage', 70],
                ['template_3_frequency_diagnostic_preventative', 'template_3_frequency_percentage', 'Diagnostic & Preventative', 71],
                ['template_3_frequency_basic', 'template_3_frequency_percentage', 'Basic', 72],
                ['template_3_frequency_major', 'template_3_frequency_percentage', 'Major', 73],
                ['template_3_frequency_orthodontics', 'template_3_frequency_percentage', 'Orthodontics', 74],
                ['template_3_verification_information', null, 'Verification Information', 80],
            ];

            foreach ($sectionDefinitions as [$sectionKey, $parentSectionKey, $label, $sortOrder]) {
                VerificationTemplateSection::query()->updateOrCreate(
                    [
                        'template_version_id' => $version->id,
                        'template_key' => $version->template_key,
                        'section_key' => $sectionKey,
                        'organization_id' => $version->organization_id,
                        'clinic_id' => $version->clinic_id,
                    ],
                    [
                        'parent_section_key' => $parentSectionKey,
                        'label' => $label,
                        'sort_order' => $sortOrder,
                        'is_builtin' => true,
                        'is_active' => true,
                    ],
                );
            }

            $legacyFrequencySectionMap = [
                'frequency_diagnostic_preventative' => 'template_3_frequency_diagnostic_preventative',
                'template_3_frequency_general' => 'template_3_frequency_diagnostic_preventative',
                'frequency_basic' => 'template_3_frequency_basic',
                'frequency_major' => 'template_3_frequency_major',
                'frequency_orthodontics_benefit' => 'template_3_frequency_orthodontics',
            ];

            foreach ($legacyFrequencySectionMap as $from => $to) {
                // Preserve questions deliberately added by an administrator or
                // clinic, but do not promote the retired fixed worksheet rows.
                VerificationFormQuestion::query()
                    ->where('template_version_id', $version->id)
                    ->where('template_key', $version->template_key)
                    ->where('section_key', $from)
                    ->where('is_builtin', false)
                    ->update(['section_key' => $to]);

                VerificationFormQuestion::query()
                    ->where('template_version_id', $version->id)
                    ->where('template_key', $version->template_key)
                    ->where('section_key', $from)
                    ->where('is_builtin', true)
                    ->delete();
            }

            VerificationFormQuestion::query()
                ->where('template_version_id', $version->id)
                ->where('template_key', $version->template_key)
                ->where('is_builtin', true)
                ->whereIn('field_key', VerificationTemplateThreeDefaults::legacyFrequencyFieldKeys())
                ->delete();

            VerificationFormQuestion::query()
                ->where('template_version_id', $version->id)
                ->where('template_key', $version->template_key)
                ->whereIn('section_key', [
                    'core_details',
                    'coverage_matrix',
                    'plan_provisions',
                    'history',
                    'service_history',
                    'verification_information',
                ])
                ->delete();

            VerificationTemplateSection::query()
                ->where('template_version_id', $version->id)
                ->where('template_key', $version->template_key)
                ->whereNotIn('section_key', collect($sectionDefinitions)->pluck(0)->all())
                ->delete();

            $this->removeDuplicateQuestions($version);

            return $version->refresh();
        });
    }

    protected function removeDuplicateQuestions(VerificationTemplateVersion $version): void
    {
        VerificationFormQuestion::query()
            ->where('template_version_id', $version->id)
            ->where('template_key', $version->template_key)
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (VerificationFormQuestion $question): string => implode('|', [
                $question->section_key,
                $question->field_key ?: 'prompt:'.trim((string) $question->prompt),
                $question->code ?: '',
            ]))
            ->each(function ($duplicates): void {
                $duplicates->skip(1)->each->delete();
            });
    }

    public function publishDraft(
        VerificationTemplateVersion $draft,
        ?string $name = null,
        ?string $notes = null,
        ?string $clinicVisibility = null,
    ): VerificationTemplateVersion {
        if ($clinicVisibility !== null && ! array_key_exists($clinicVisibility, VerificationTemplateVersion::CLINIC_VISIBILITY_OPTIONS)) {
            throw ValidationException::withMessages([
                'clinic_visibility' => 'Select a valid clinic release option.',
            ]);
        }

        return DB::transaction(function () use ($draft, $name, $notes, $clinicVisibility): VerificationTemplateVersion {
            $lockedDraft = VerificationTemplateVersion::query()
                ->lockForUpdate()
                ->findOrFail($draft->getKey());

            if (! $lockedDraft->canEditDirectly()) {
                throw ValidationException::withMessages([
                    'template' => $lockedDraft->lifecycleLockReason()
                        ?? 'Only an unused, unpublished draft can be published.',
                ]);
            }

            $this->assertPublishable($lockedDraft);

            VerificationTemplateVersion::query()
                ->where('scope', $lockedDraft->scope)
                ->where('template_key', $lockedDraft->template_key)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->when($lockedDraft->clinic_id, fn ($query) => $query->where('clinic_id', $lockedDraft->clinic_id))
                ->when(! $lockedDraft->clinic_id, fn ($query) => $query->whereNull('clinic_id'))
                ->update(['is_active' => false]);

            $lockedDraft->forceFill([
                'name' => filled($name) ? trim((string) $name) : $lockedDraft->name,
                'notes' => filled($notes) ? trim((string) $notes) : $lockedDraft->notes,
                'clinic_visibility' => $clinicVisibility ?? $lockedDraft->clinic_visibility,
                'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                'is_active' => true,
                'is_working_draft' => false,
                'published_at' => now(),
            ])->save();

            return $lockedDraft->refresh();
        });
    }

    public function assertPublishable(VerificationTemplateVersion $version): void
    {
        if ($version->template_key !== VerificationFormQuestion::DEFAULT_TEMPLATE_KEY) {
            return;
        }

        $legacySectionKeys = [
            'core_details',
            'coverage_matrix',
            'frequency_diagnostic_preventative',
            'frequency_basic',
            'frequency_major',
            'frequency_orthodontics_benefit',
            'history',
            'plan_provisions',
            'service_history',
            'verification_information',
            'template_3_frequency_general',
        ];

        $hasLegacyRows = $version->questions()
            ->where(function ($query) use ($legacySectionKeys): void {
                $query
                    ->whereIn('section_key', $legacySectionKeys)
                    ->orWhere(function ($query): void {
                        $query
                            ->where('is_builtin', true)
                            ->whereIn('field_key', VerificationTemplateThreeDefaults::legacyFrequencyFieldKeys());
                    });
            })
            ->exists();

        if ($hasLegacyRows) {
            throw ValidationException::withMessages([
                'template' => 'This draft still contains retired template questions. Rebuild the draft from the current Master Template before publishing.',
            ]);
        }

        $duplicateFieldKey = $version->questions()
            ->whereNotNull('field_key')
            ->where('field_key', '!=', '')
            ->selectRaw('field_key, count(*) as copies')
            ->groupBy('field_key')
            ->havingRaw('count(*) > 1')
            ->value('field_key');

        if ($duplicateFieldKey) {
            throw ValidationException::withMessages([
                'template' => "The field {$duplicateFieldKey} is included more than once. Remove the duplicate before publishing.",
            ]);
        }
    }

    public function markWorkingDraft(VerificationTemplateVersion $draft): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($draft): VerificationTemplateVersion {
            $lockedDraft = VerificationTemplateVersion::query()
                ->lockForUpdate()
                ->findOrFail($draft->getKey());

            if (! $lockedDraft->canEditDirectly()) {
                throw ValidationException::withMessages([
                    'template' => $lockedDraft->lifecycleLockReason()
                        ?? 'Only an unused, unpublished draft can be selected as the working draft.',
                ]);
            }

            VerificationTemplateVersion::query()
                ->where('scope', $lockedDraft->scope)
                ->where('template_key', $lockedDraft->template_key)
                ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
                ->when($lockedDraft->clinic_id, fn ($query) => $query->where('clinic_id', $lockedDraft->clinic_id))
                ->when(! $lockedDraft->clinic_id, fn ($query) => $query->whereNull('clinic_id'))
                ->whereKeyNot($lockedDraft->getKey())
                ->update(['is_working_draft' => false]);

            $lockedDraft->forceFill(['is_working_draft' => true])->save();

            return $lockedDraft->refresh();
        });
    }

    public function attachSnapshotToWorkItem(BillingWorkItem $workItem): BillingWorkItem
    {
        if ($workItem->verification_template_version_id && filled($workItem->verification_template_snapshot)) {
            return $workItem;
        }

        $version = $this->latestPublishedVersionForWorkItem($workItem);

        return $this->replaceWorkItemSnapshot($workItem, $version);
    }

    public function latestPublishedVersionForWorkItem(BillingWorkItem $workItem): VerificationTemplateVersion
    {
        return $workItem->clinic
            ? $this->ensureClinicPublishedVersion($workItem->clinic)
            : $this->ensureMasterVersion();
    }

    public function workItemUsesLatestPublishedVersion(BillingWorkItem $workItem): bool
    {
        return (int) $workItem->verification_template_version_id === (int) $this->latestPublishedVersionForWorkItem($workItem)->getKey();
    }

    public function refreshWorkItemSnapshot(BillingWorkItem $workItem): BillingWorkItem
    {
        if ($workItem->normalized_status === BillingWorkItem::STATUS_DONE) {
            throw new \LogicException('Completed verification requests keep their original template snapshot for audit history.');
        }

        return $this->replaceWorkItemSnapshot($workItem, $this->latestPublishedVersionForWorkItem($workItem));
    }

    protected function replaceWorkItemSnapshot(BillingWorkItem $workItem, VerificationTemplateVersion $version): BillingWorkItem
    {
        $workItem->forceFill([
            'verification_template_version_id' => $version->id,
            'verification_template_snapshot' => $this->snapshot($version),
            'verification_template_snapshot_at' => now(),
        ])->saveQuietly();

        return $workItem->refresh();
    }

    public function snapshot(VerificationTemplateVersion $version): array
    {
        return [
            'version' => [
                'id' => $version->id,
                'template_key' => $version->template_key,
                'scope' => $version->scope,
                'organization_id' => $version->organization_id,
                'clinic_id' => $version->clinic_id,
                'version_number' => $version->version_number,
                'name' => $version->name,
                'form_type' => $version->form_type,
                'clinic_visibility' => $version->clinic_visibility,
                'status' => $version->status,
                'published_at' => optional($version->published_at)->toIso8601String(),
            ],
            'sections' => $version->sections()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (VerificationTemplateSection $section): array => $this->snapshotAttributes($section->getAttributes()))
                ->values()
                ->all(),
            'questions' => $version->questions()
                ->where('is_active', true)
                ->orderBy('section_key')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (VerificationFormQuestion $question): array => $this->snapshotAttributes($question->getAttributes()))
                ->values()
                ->all(),
        ];
    }

    protected function copySections(VerificationTemplateVersion $source, VerificationTemplateVersion $target, ?Clinic $clinic = null): void
    {
        $source->sections()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (VerificationTemplateSection $section) use ($target, $clinic): void {
                $copy = $section->replicate(['id', 'created_at', 'updated_at', 'deleted_at']);
                $copy->template_version_id = $target->id;
                $copy->source_section_id = $section->id;
                $copy->organization_id = $clinic?->organization_id ?? $target->organization_id;
                $copy->clinic_id = $clinic?->id ?? $target->clinic_id;
                $copy->save();
            });
    }

    protected function copyQuestions(VerificationTemplateVersion $source, VerificationTemplateVersion $target, ?Clinic $clinic = null): void
    {
        $sourceQuestions = $source->questions()
            ->orderBy('section_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $questionIdMap = [];

        $sourceQuestions
            ->each(function (VerificationFormQuestion $question) use ($target, $clinic, &$questionIdMap): void {
                $copy = $question->replicate(['id', 'created_at', 'updated_at', 'deleted_at']);
                $copy->template_version_id = $target->id;
                $copy->source_question_id = $question->id;
                $copy->organization_id = $clinic?->organization_id ?? $target->organization_id;
                $copy->clinic_id = $clinic?->id ?? $target->clinic_id;
                $copy->parent_question_id = null;
                $copy->save();

                $questionIdMap[$question->id] = $copy->id;
            });

        $sourceQuestions
            ->filter(fn (VerificationFormQuestion $question): bool => filled($question->parent_question_id))
            ->each(function (VerificationFormQuestion $question) use ($questionIdMap): void {
                $copiedQuestionId = $questionIdMap[$question->id] ?? null;
                $copiedParentId = $questionIdMap[$question->parent_question_id] ?? null;

                if ($copiedQuestionId && $copiedParentId) {
                    VerificationFormQuestion::query()
                        ->whereKey($copiedQuestionId)
                        ->update(['parent_question_id' => $copiedParentId]);
                }
            });
    }

    protected function snapshotAttributes(array $attributes): array
    {
        unset($attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);

        return $attributes;
    }
}
