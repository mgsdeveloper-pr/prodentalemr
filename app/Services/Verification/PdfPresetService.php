<?php

namespace App\Services\Verification;

use App\Models\Clinic;
use App\Models\VerificationPdfPreset;
use Illuminate\Support\Collection;

class PdfPresetService
{
    public function optionsForClinic(Clinic $clinic): array
    {
        return $this->queryForClinic($clinic)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function queryForClinic(Clinic $clinic)
    {
        return VerificationPdfPreset::query()
            ->where('clinic_id', $clinic->getKey())
            ->where('is_active', true);
    }

    public function defaultForClinic(Clinic $clinic): ?VerificationPdfPreset
    {
        if ($clinic->default_verification_pdf_preset_id) {
            $preset = $this->queryForClinic($clinic)
                ->whereKey($clinic->default_verification_pdf_preset_id)
                ->first();

            if ($preset) {
                return $preset;
            }
        }

        return $this->queryForClinic($clinic)
            ->where('is_default', true)
            ->orderByDesc('updated_at')
            ->first();
    }

    public function saveForClinic(Clinic $clinic, array $data, ?VerificationPdfPreset $preset = null): VerificationPdfPreset
    {
        $preset ??= new VerificationPdfPreset([
            'clinic_id' => $clinic->getKey(),
            'created_by' => auth()->id(),
        ]);

        $preset->fill([
            'clinic_id' => $clinic->getKey(),
            'updated_by' => auth()->id(),
            'name' => trim((string) ($data['name'] ?? 'Verification PDF Preset')),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'output_mode' => $data['output_mode'] ?? 'standard',
            'section_keys' => $data['section_keys'] ?? [],
            'question_ids' => $data['question_ids'] ?? [],
            'show_blank_rows' => (bool) ($data['show_blank_rows'] ?? ! \App\Support\VerificationResultPdf::isCustomOutputMode($data['output_mode'] ?? 'standard')),
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => true,
        ]);
        $preset->save();

        if ($preset->is_default) {
            VerificationPdfPreset::query()
                ->where('clinic_id', $clinic->getKey())
                ->whereKeyNot($preset->getKey())
                ->update(['is_default' => false]);

            $clinic->default_verification_pdf_preset_id = $preset->getKey();
            $clinic->verification_pdf_output_mode = $preset->getOutputMode();
            $clinic->verification_pdf_output_sections = \App\Support\VerificationResultPdf::isCustomOutputMode($preset->getOutputMode()) ? $preset->getSectionKeys() : [];
            $clinic->verification_pdf_output_question_ids = \App\Support\VerificationResultPdf::isCustomOutputMode($preset->getOutputMode()) ? $preset->getQuestionIds() : [];
            $clinic->save();
        }

        return $preset->refresh();
    }

    public function seedDefaultsForClinic(Clinic $clinic): Collection
    {
        if ($this->queryForClinic($clinic)->exists()) {
            return $this->queryForClinic($clinic)->get();
        }

        return collect([
            $this->saveForClinic($clinic, [
                'name' => 'Full Verification Report',
                'description' => 'Complete verification report with all standard output.',
                'output_mode' => 'standard',
                'section_keys' => [],
                'question_ids' => [],
                'show_blank_rows' => true,
                'is_default' => true,
            ]),
            $this->saveForClinic($clinic, [
                'name' => 'Clinic Copy',
                'description' => 'Clinic-facing report preset for routine download and review.',
                'output_mode' => 'custom_landscape',
                'section_keys' => [],
                'question_ids' => [],
                'show_blank_rows' => false,
                'is_default' => false,
            ]),
        ]);
    }
}
