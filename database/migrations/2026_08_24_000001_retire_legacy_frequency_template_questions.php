<?php

use App\Models\VerificationTemplateVersion;
use App\Support\VerificationTemplateVersionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('verification_template_versions')
            || ! Schema::hasTable('verification_form_questions')) {
            return;
        }

        /** @var VerificationTemplateVersionService $versions */
        $versions = app(VerificationTemplateVersionService::class);

        DB::transaction(function () use ($versions): void {
            $existingDrafts = VerificationTemplateVersion::query()
                ->where('template_key', 'template_3')
                ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
                ->get();

            foreach ($existingDrafts as $draft) {
                $versions->normalizeTemplateThreeVersion($draft);
            }

            $activeMaster = VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_MASTER)
                ->where('template_key', 'template_3')
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->first();

            $cleanMaster = $activeMaster
                ? $this->publishCleanSuccessor($versions, $activeMaster, 'Removed retired fixed Frequency worksheet questions.')
                : null;

            $activeClinicVersions = VerificationTemplateVersion::query()
                ->where('scope', VerificationTemplateVersion::SCOPE_CLINIC)
                ->where('template_key', 'template_3')
                ->where('status', VerificationTemplateVersion::STATUS_PUBLISHED)
                ->where('is_active', true)
                ->get();

            foreach ($activeClinicVersions as $activeClinicVersion) {
                $published = $this->publishCleanSuccessor(
                    $versions,
                    $activeClinicVersion,
                    'Removed retired inherited Frequency worksheet questions.',
                );

                if ($cleanMaster) {
                    $published->forceFill(['source_version_id' => $cleanMaster->id])->saveQuietly();
                }
            }

            VerificationTemplateVersion::query()
                ->where('template_key', 'template_3')
                ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
                ->get()
                ->each(fn (VerificationTemplateVersion $draft) => $versions->normalizeTemplateThreeVersion($draft));

            VerificationTemplateVersion::query()
                ->where('template_key', 'template_3')
                ->where('status', VerificationTemplateVersion::STATUS_DRAFT)
                ->get()
                ->groupBy(fn (VerificationTemplateVersion $draft): string => $draft->scope.'|'.($draft->clinic_id ?: 'master'))
                ->each(function ($drafts): void {
                    $ordered = $drafts->sortByDesc('id')->values();

                    foreach ($ordered as $index => $draft) {
                        $draft->forceFill(['is_working_draft' => $index === 0])->saveQuietly();
                    }
                });
        });
    }

    protected function publishCleanSuccessor(
        VerificationTemplateVersionService $versions,
        VerificationTemplateVersion $source,
        string $notes,
    ): VerificationTemplateVersion {
        $draft = $versions->createDraftFromSource($source, [
            'name' => $source->name,
            'form_type' => $source->form_type,
            'clinic_visibility' => $source->clinic_visibility,
            'starting_point' => 'specific_version',
        ]);

        return $versions->publishDraft($draft, $source->name, $notes);
    }

    public function down(): void
    {
        // Historical versions and request snapshots are intentionally retained.
    }
};
