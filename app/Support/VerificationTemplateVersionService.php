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
    public function provisionRegisteredClinic(Clinic $clinic): void
    {
        DB::transaction(function () use ($clinic): void {
            Clinic::whereKey($clinic->id)->lockForUpdate()->firstOrFail();
            foreach (['full_form', 'short_form'] as $form) {
                if (VerificationTemplateVersion::where('scope', 'clinic')->where('clinic_id', $clinic->id)
                    ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
                    ->where('status', 'published')->where('active_'.$form, true)->exists()) continue;
                $master = VerificationTemplateVersion::where('scope', 'master')->whereNull('clinic_id')
                    ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
                    ->where('status', 'published')->where('active_'.$form, true)
                    ->whereIn('form_type', ['both', $form])
                    ->whereIn('clinic_visibility', [VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE, VerificationTemplateVersion::CLINIC_VISIBILITY_DEFAULT])
                    ->orderByDesc('version_number')->lockForUpdate()->first();
                if (! $master || ! $master->questions()->where('is_active', true)->whereIn('form_type', ['both', $form])->exists()) continue;
                $version = VerificationTemplateVersion::create([
                    'scope' => 'clinic', 'clinic_id' => $clinic->id, 'organization_id' => $clinic->organization_id,
                    'template_key' => $master->template_key, 'parent_version_id' => $master->id, 'source_version_id' => $master->id,
                    'version_number' => 1 + (int) VerificationTemplateVersion::where('scope', 'clinic')->where('clinic_id', $clinic->id)
                        ->where('template_key', $master->template_key)->where('form_type', $form)->max('version_number'),
                    'name' => $clinic->clinic_name.' - '.($form === 'full_form' ? 'Full Form' : 'Short Form'),
                    'form_type' => $form, 'status' => 'published', 'published_at' => now(), 'is_active' => true,
                    'active_full_form' => $form === 'full_form', 'active_short_form' => $form === 'short_form',
                    'is_working_draft' => false, 'uses_section_layout' => $master->uses_section_layout,
                    'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE, 'created_by' => auth()->id(),
                ]);
                $this->copySections($master, $version, $clinic);
                $this->copyQuestions($master, $version, $clinic);
                $this->filterDraftQuestionsForFormType($version, $form);
            }
        });
    }

    public function ensureMasterVersion(string $templateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY, ?string $formType = null): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($templateKey, $formType): VerificationTemplateVersion {
            $version = VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
                ->where('template_key', $templateKey)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->when($formType, fn ($query) => $query->where('active_'.$formType, true))
                ->first();

            if (! $version) {
                if ($formType && VerificationTemplateVersion::where('scope', 'master')->where('template_key', $templateKey)->where('status', 'published')->exists()) {
                    throw ValidationException::withMessages(['template' => 'No active master template is available for this form.']);
                }
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

    public function ensureClinicPublishedVersion(Clinic $clinic, string $templateKey = VerificationFormQuestion::DEFAULT_TEMPLATE_KEY, ?string $formType = null): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($clinic, $templateKey, $formType): VerificationTemplateVersion {
            Clinic::whereKey($clinic->id)->lockForUpdate()->firstOrFail();
            $existing = VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('clinic_id', $clinic->id)
                ->where('template_key', $templateKey)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->when($formType, fn ($query) => $query->where('active_'.$formType, true))
                ->first();

            if ($existing) {
                return $existing;
            }

            $master = VerificationTemplateVersion::query()->where('scope', 'master')->whereNull('clinic_id')
                ->where('template_key', $templateKey)->where('status', 'published')->where('is_active', true)
                ->when($formType, fn ($query) => $query->where('active_'.$formType, true))
                ->whereIn('clinic_visibility', [VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE, VerificationTemplateVersion::CLINIC_VISIBILITY_DEFAULT])
                ->latest('version_number')->first();
            if (! $master) {
                throw ValidationException::withMessages(['template' => 'Template setup pending: no current active master is available for this form.']);
            }

            if (! in_array($master->clinic_visibility, [
                VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
                VerificationTemplateVersion::CLINIC_VISIBILITY_DEFAULT,
            ], true)) {
                $visibleMaster = VerificationTemplateVersion::query()
                    ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
                    ->where('template_key', $templateKey)
                    ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                    ->when($formType, fn ($query) => $query->whereIn('form_type', ['both', $formType]))
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
                'version_number' => 1 + (int) VerificationTemplateVersion::where('clinic_id', $clinic->id)->where('template_key', $templateKey)->max('version_number'),
                'name' => $clinic->clinic_name.' Master Template',
                'form_type' => $formType ?? $master->form_type ?: VerificationTemplateVersion::FORM_TYPE_BOTH,
                'clinic_visibility' => VerificationTemplateVersion::CLINIC_VISIBILITY_VISIBLE,
                'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                'is_active' => true,
                'is_working_draft' => false,
                'published_at' => now(),
                'created_by' => auth()->id(),
                'notes' => 'Clinic working copy replicated from the active master template.',
                'uses_section_layout' => $master->uses_section_layout,
                'active_full_form' => in_array($formType ?? $master->form_type, ['both', 'full_form'], true),
                'active_short_form' => in_array($formType ?? $master->form_type, ['both', 'short_form'], true),
            ]);

            $this->copySections($master, $version, $clinic);
            $this->copyQuestions($master, $version, $clinic);
            if ($formType) $this->filterDraftQuestionsForFormType($version, $formType);

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
            \App\Models\VerificationTemplateImportReceipt::where('template_version_id', $lockedDraft->id)
                ->update(['template_version_id' => null]);
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
            if ($clinicId) Clinic::whereKey($clinicId)->lockForUpdate()->firstOrFail();
            else VerificationTemplateVersion::where('scope', 'master')->where('template_key', $templateKey)->orderBy('id')->lockForUpdate()->get();
            VerificationTemplateVersion::query()
                ->where('scope', $scope)
                ->where('template_key', $templateKey)
                ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
                ->whereIn('form_type', [$formType, 'both'])
                ->when($clinicId, fn ($query) => $query->where('clinic_id', $clinicId))
                ->when(! $clinicId, fn ($query) => $query->whereNull('clinic_id'))
                ->update(['is_working_draft' => false]);

            $nextVersionNumber = ((int) VerificationTemplateVersion::query()
                ->where('scope', $scope)
                ->where('template_key', $templateKey)
                ->whereIn('form_type', [$formType, 'both'])
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
                'uses_section_layout' => $source?->uses_section_layout ?? false,
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
        if ($version->template_key !== VerificationFormQuestion::DEFAULT_TEMPLATE_KEY || $version->uses_section_layout) {
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
                VerificationTemplateSection::query()->firstOrCreate(
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
                ->where('is_builtin', true)
                ->whereNotIn('section_key', collect($sectionDefinitions)->pluck(0)->all())
                ->delete();

            // Keep copied questions intact; publishing validates conflicting bindings.

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
        bool $activate = false,
        ?array $expectedActive = null,
    ): VerificationTemplateVersion {
        abort_unless(auth()->user()?->canPublishVerificationTemplate($draft->clinic), 403);
        if ($clinicVisibility !== null && ! array_key_exists($clinicVisibility, VerificationTemplateVersion::CLINIC_VISIBILITY_OPTIONS)) {
            throw ValidationException::withMessages([
                'clinic_visibility' => 'Select a valid clinic release option.',
            ]);
        }

        return DB::transaction(function () use ($draft, $name, $notes, $clinicVisibility, $activate, $expectedActive): VerificationTemplateVersion {
            if ($draft->clinic_id) {
                Clinic::whereKey($draft->clinic_id)->lockForUpdate()->firstOrFail();
                if ($activate && $expectedActive !== null) {
                    $this->assertActiveClinicForms($draft->clinic_id, $expectedActive);
                }
            }
            $lockedDraft = VerificationTemplateVersion::query()->where('scope', $draft->scope)
                ->where('template_key', $draft->template_key)->where('clinic_id', $draft->clinic_id)
                ->orderBy('id')->lockForUpdate()->get()->firstWhere('id', $draft->getKey());
            abort_unless($lockedDraft, 404);

            if (! $lockedDraft->canEditDirectly()) {
                throw ValidationException::withMessages([
                    'template' => $lockedDraft->lifecycleLockReason()
                        ?? 'Only an unused, unpublished draft can be published.',
                ]);
            }

            $this->assertPublishable($lockedDraft);

            $lockedDraft->forceFill([
                'name' => filled($name) ? trim((string) $name) : $lockedDraft->name,
                'notes' => filled($notes) ? trim((string) $notes) : $lockedDraft->notes,
                'clinic_visibility' => $clinicVisibility ?? $lockedDraft->clinic_visibility,
                'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                'is_active' => $activate,
                'active_full_form' => false,
                'active_short_form' => false,
                'is_working_draft' => false,
                'published_at' => now(),
            ])->save();

            if ($activate) {
                $this->activatePublishedVersion($lockedDraft);
            }

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

        $questions = $version->questions()->where('is_active', true)->get()->keyBy('id');
        $sections = $version->sections()->get()->keyBy('section_key');
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['template' => 'Add at least one active question before publishing this template.']);
        }
        if ($version->questions()->where('section_key', 'custom_layout_needs_mapping')->exists()) {
            throw ValidationException::withMessages(['template' => 'Move every Needs Mapping question to its reviewed section before publishing.']);
        }
        if ($questions->contains(fn ($question) => $question->field_key === 'vf_coverage_orthodontics_deductible_applies' && $question->secondary_field_key === 'vf_ortho_lifetime_maximum')) {
            throw ValidationException::withMessages(['template' => 'Orthodontic coverage percentage must not use the lifetime maximum answer. Create a corrected draft.']);
        }
        if ($version->uses_section_layout) {
            $bound = []; $frequency = [];
            foreach ($questions as $question) {
                if ($question->is_builtin) {
                    foreach (array_filter([$question->field_key, $question->secondary_field_key]) as $key) {
                        if (isset($bound[$key])) throw ValidationException::withMessages(['template' => "The answer {$key} is mapped more than once."]);
                        $bound[$key] = true;
                    }
                }
                if ($question->input_type === 'frequency_row') {
                    $signature = $question->frequencyCategory().'|'.mb_strtolower(trim($question->code.'|'.$question->prompt));
                    if (isset($frequency[$signature])) throw ValidationException::withMessages(['template' => 'Repeated frequency questions would share an answer. Give them distinct wording.']);
                    $frequency[$signature] = true;
                    if (! array_key_exists($question->answer_layout ?? '', VerificationFormQuestion::ANSWER_LAYOUT_OPTIONS)) throw ValidationException::withMessages(['template' => 'Select an answer layout for every frequency question.']);
                }
            }
        }
        foreach ($sections as $section) {
            $visited = [$section->section_key];
            $parentKey = $section->parent_section_key;
            if ($version->uses_section_layout && $section->is_active && ! $section->allow_empty
                && $section->section_key !== 'custom_layout_needs_mapping'
                && ! $sections->contains('parent_section_key', $section->section_key)
                && ! $questions->contains('section_key', $section->section_key)) {
                throw ValidationException::withMessages(['template' => "Review empty section: {$section->label}. Add questions or explicitly confirm it as intentionally empty."]);
            }
            if ($version->uses_section_layout && filled($parentKey) && filled($sections->get($parentKey)?->parent_section_key)) {
                throw ValidationException::withMessages(['template' => 'Use Section > Subsection > Question. Nested subsections are not supported.']);
            }
            while (filled($parentKey)) {
                if (! $sections->has($parentKey) || in_array($parentKey, $visited, true)) {
                    throw ValidationException::withMessages(['template' => 'A section has a missing parent or a circular hierarchy. Correct the section structure before publishing.']);
                }
                $visited[] = $parentKey;
                if ($section->is_active && ! $sections->get($parentKey)->is_active) {
                    throw ValidationException::withMessages(['template' => 'An active subsection has an inactive parent. Correct its visibility before publishing.']);
                }
                $parentKey = $sections->get($parentKey)->parent_section_key;
            }
        }
        foreach ($questions as $question) {
            foreach ($question->procedure_tags ?? [] as $tag) {
                if (($tag['status'] ?? '') !== 'directory_match') {
                    throw ValidationException::withMessages(['template' => 'This draft contains unverified procedure codes. Resolve their directory validation before publishing.']);
                }
            }
            if (($sections->has($question->section_key) && ! $sections->get($question->section_key)->is_active)
                || (! in_array($question->section_key, VerificationFormQuestion::TEMPLATE_3_LIVE_SECTION_KEYS, true) && ! $sections->has($question->section_key))) {
                throw ValidationException::withMessages(['template' => 'A question belongs to a missing or inactive custom section. Correct its section before publishing.']);
            }
            if (! array_key_exists($question->information_scope ?? 'unclassified', VerificationFormQuestion::INFORMATION_SCOPE_OPTIONS)
                || ! array_key_exists($question->reuse_policy ?? 'fresh_verification', VerificationFormQuestion::REUSE_POLICY_OPTIONS)
                || ($question->reuse_policy === 'review_required' && $question->information_scope !== 'plan')) {
                throw ValidationException::withMessages(['template' => 'Only plan information may be marked for reviewed reuse. Check the question information settings.']);
            }
            if ($question->question_kind !== VerificationFormQuestion::QUESTION_KIND_CONDITIONAL) {
                continue;
            }
            $parent = $questions->get($question->parent_question_id);
            if (! $parent || $parent->id === $question->id
                || ! array_key_exists((string) $question->trigger_answer, VerificationFormQuestion::CONDITIONAL_TRIGGER_OPTIONS)) {
                throw ValidationException::withMessages([
                    'template' => 'A conditional question has a missing or invalid parent or trigger. Review the draft before publishing.',
                ]);
            }
            $visited = [$question->id];
            while ($parent) {
                if (in_array($parent->id, $visited, true)) {
                    throw ValidationException::withMessages(['template' => 'Conditional questions contain a circular dependency.']);
                }
                $visited[] = $parent->id;
                $parent = $questions->get($parent->parent_question_id);
            }
        }
    }

    public function activatePublishedVersion(VerificationTemplateVersion $version, ?string $formType = null): void
    {
        abort_unless(auth()->user()?->canPublishVerificationTemplate($version->clinic), 403);
        $forms = $formType ? [$formType] : ($version->form_type === 'both' ? ['short_form', 'full_form'] : [$version->form_type]);
        foreach ($forms as $form) {
            if (! in_array($form, ['short_form', 'full_form'], true) || ! in_array($version->form_type, ['both', $form], true)) {
                throw ValidationException::withMessages(['template' => 'This template does not support the selected form.']);
            }
        }
        DB::transaction(function () use ($version, $forms): void {
            if ($version->clinic_id) Clinic::whereKey($version->clinic_id)->lockForUpdate()->firstOrFail();
            $query = VerificationTemplateVersion::query()->where('scope', $version->scope)
                ->where('template_key', $version->template_key)->where('clinic_id', $version->clinic_id);
            $versions = (clone $query)->orderBy('id')->lockForUpdate()->get();
            $selected = $versions->firstWhere('id', $version->id);
            if (! $selected || $selected->status !== 'published' || $selected->clinic_visibility === 'retired') {
                throw ValidationException::withMessages(['template' => 'Only a published, non-retired template can be activated.']);
            }
            foreach ($forms as $form) {
                if (! in_array($selected->form_type, ['both', $form], true)) {
                    throw ValidationException::withMessages(['template' => 'This template no longer supports the selected form. Review the selections again.']);
                }
                (clone $query)->update(['active_'.$form => false]);
                VerificationTemplateVersion::whereKey($selected->id)->update(['active_'.$form => true]);
            }
            (clone $query)->update(['is_active' => DB::raw('(active_short_form OR active_full_form)')]);
        });
    }

    public function activeClinicForms(int $clinicId): array
    {
        $versions = VerificationTemplateVersion::where('scope', 'clinic')->where('clinic_id', $clinicId)
            ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)->where('status', 'published')->get();
        return ['short_form' => $versions->firstWhere('active_short_form', true)?->id,
            'full_form' => $versions->firstWhere('active_full_form', true)?->id];
    }

    protected function assertActiveClinicForms(int $clinicId, array $expected): void
    {
        if ($this->activeClinicForms($clinicId) !== $expected) {
            throw ValidationException::withMessages(['activation' => 'Active forms changed after review. Close this review and review the selections again.']);
        }
    }

    public function activateClinicForms(Clinic $clinic, array $selections, array $expected): void
    {
        abort_unless(auth()->user()?->canPublishVerificationTemplate($clinic), 403);
        DB::transaction(function () use ($clinic, $selections, $expected): void {
            Clinic::whereKey($clinic->id)->lockForUpdate()->firstOrFail();
            $this->assertActiveClinicForms($clinic->id, $expected);
            foreach ($selections as $form => $id) {
                $version = VerificationTemplateVersion::where('scope', 'clinic')->where('clinic_id', $clinic->id)
                    ->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)->findOrFail($id);
                $this->activatePublishedVersion($version, $form);
            }
        });
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
        $form = $workItem->verificationProfile?->form_type ?: 'full_form';
        if (! in_array($form, ['short_form', 'full_form'], true)) {
            throw ValidationException::withMessages(['template' => 'Choose Short Form or Full Form before selecting a template.']);
        }
        $active = VerificationTemplateVersion::query()
            ->where('scope', $workItem->clinic_id ? 'clinic' : 'master')
            ->where('clinic_id', $workItem->clinic_id)->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
            ->where('status', 'published')->where('clinic_visibility', '!=', 'retired')
            ->whereIn('form_type', ['both', $form])->where('active_'.$form, true)->latest('id')->first();
        if ($active) return $active;
        $hasOtherForm = VerificationTemplateVersion::query()->where('scope', $workItem->clinic_id ? 'clinic' : 'master')
            ->where('clinic_id', $workItem->clinic_id)->where('template_key', VerificationFormQuestion::DEFAULT_TEMPLATE_KEY)
            ->where('status', 'published')->where('is_active', true)->exists();
        if ($hasOtherForm) {
            throw ValidationException::withMessages(['template' => 'No active template is configured for this form. Ask an administrator to activate it.']);
        }
        return $workItem->clinic
            ? $this->ensureClinicPublishedVersion($workItem->clinic, VerificationFormQuestion::DEFAULT_TEMPLATE_KEY, $form)
            : $this->ensureMasterVersion(VerificationFormQuestion::DEFAULT_TEMPLATE_KEY, $form);
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
                'uses_section_layout' => $version->uses_section_layout,
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
                $copy->semantic_key = $question->semantic_key ?: 'question:'.$question->id;
                $copy->organization_id = $clinic?->organization_id ?? $target->organization_id;
                $copy->clinic_id = $clinic?->id ?? $target->clinic_id;
                $copy->parent_question_id = null;
                if ($copy->field_key === 'vf_coverage_orthodontics_deductible_applies'
                    && $copy->secondary_field_key === 'vf_ortho_lifetime_maximum') {
                    $copy->secondary_field_key = 'vf_ortho_benefit';
                }
                if ($question->input_type === 'frequency_row') {
                    $copy->answer_layout = $question->answer_layout ?: $question->inferredAnswerLayout();
                    $copy->response_category = $question->frequencyCategory();
                }
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
