<?php

namespace App\Support;

use App\Models\BillingWorkItem;
use App\Models\Clinic;
use App\Models\VerificationFormQuestion;
use App\Models\VerificationTemplateSection;
use App\Models\VerificationTemplateVersion;
use Illuminate\Support\Facades\DB;

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
                    'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                    'is_active' => true,
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

            $version = VerificationTemplateVersion::query()->create([
                'template_key' => $templateKey,
                'scope' => VerificationTemplateVersion::SCOPE_CLINIC,
                'organization_id' => $clinic->organization_id,
                'clinic_id' => $clinic->id,
                'parent_version_id' => $master->id,
                'source_version_id' => $master->id,
                'version_number' => 1,
                'name' => $clinic->clinic_name . ' Master Template',
                'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                'is_active' => true,
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
        return DB::transaction(function () use ($published): VerificationTemplateVersion {
            $nextVersionNumber = ((int) VerificationTemplateVersion::query()
                ->where('scope', $published->scope)
                ->where('template_key', $published->template_key)
                ->when($published->clinic_id, fn ($query) => $query->where('clinic_id', $published->clinic_id))
                ->when(! $published->clinic_id, fn ($query) => $query->whereNull('clinic_id'))
                ->max('version_number')) + 1;

            $draft = VerificationTemplateVersion::query()->create([
                'template_key' => $published->template_key,
                'scope' => $published->scope,
                'organization_id' => $published->organization_id,
                'clinic_id' => $published->clinic_id,
                'parent_version_id' => $published->id,
                'source_version_id' => $published->source_version_id ?: $published->id,
                'version_number' => $nextVersionNumber,
                'name' => $published->name,
                'status' => VerificationTemplateVersion::STATUS_DRAFT,
                'is_active' => false,
                'created_by' => auth()->id(),
                'notes' => 'Draft cloned from published version ' . $published->version_number . '.',
            ]);

            $this->copySections($published, $draft, $published->clinic);
            $this->copyQuestions($published, $draft, $published->clinic);

            return $draft->refresh();
        });
    }

    public function publishDraft(VerificationTemplateVersion $draft): VerificationTemplateVersion
    {
        return DB::transaction(function () use ($draft): VerificationTemplateVersion {
            VerificationTemplateVersion::query()
                ->where('scope', $draft->scope)
                ->where('template_key', $draft->template_key)
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->when($draft->clinic_id, fn ($query) => $query->where('clinic_id', $draft->clinic_id))
                ->when(! $draft->clinic_id, fn ($query) => $query->whereNull('clinic_id'))
                ->update(['is_active' => false]);

            $draft->forceFill([
                'status' => VerificationTemplateVersion::STATUS_PUBLISHED,
                'is_active' => true,
                'published_at' => now(),
            ])->save();

            return $draft->refresh();
        });
    }

    public function attachSnapshotToWorkItem(BillingWorkItem $workItem): BillingWorkItem
    {
        if ($workItem->verification_template_version_id && filled($workItem->verification_template_snapshot)) {
            return $workItem;
        }

        $version = $workItem->clinic
            ? $this->ensureClinicPublishedVersion($workItem->clinic)
            : $this->ensureMasterVersion();

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
