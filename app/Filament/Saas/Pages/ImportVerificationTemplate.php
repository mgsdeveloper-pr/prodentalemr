<?php

namespace App\Filament\Saas\Pages;

use App\Filament\Saas\Resources\VerificationFormQuestions\VerificationFormQuestionResource;
use App\Models\Clinic;

class ImportVerificationTemplate extends \App\Filament\Shared\Pages\ImportVerificationTemplate
{
    protected static function scopedClinic(): ?Clinic
    {
        return null;
    }

    protected static function isMasterImport(): bool
    {
        return true;
    }

    public function builderUrl(): string
    {
        return VerificationFormQuestionResource::getUrl('index', ['version' => $this->createdVersionId], panel: 'saas');
    }
}
