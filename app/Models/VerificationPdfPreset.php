<?php

namespace App\Models;

use App\Support\VerificationResultPdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationPdfPreset extends Model
{
    protected $fillable = [
        'clinic_id',
        'created_by',
        'updated_by',
        'name',
        'description',
        'output_mode',
        'section_keys',
        'question_ids',
        'show_blank_rows',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'section_keys' => 'array',
            'question_ids' => 'array',
            'show_blank_rows' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getOutputMode(): string
    {
        return VerificationResultPdf::normalizeOutputMode($this->output_mode);
    }

    public function getSectionKeys(): array
    {
        $sections = is_array($this->section_keys) ? $this->section_keys : [];

        return array_values(array_filter($sections, fn ($section): bool => is_string($section)));
    }

    public function getQuestionIds(): array
    {
        $questionIds = is_array($this->question_ids) ? $this->question_ids : [];

        return array_values(array_filter(
            $questionIds,
            fn ($questionId): bool => is_numeric($questionId) && (int) $questionId > 0
        ));
    }

    public function shouldShowBlankRows(): bool
    {
        return (bool) $this->show_blank_rows;
    }
}
