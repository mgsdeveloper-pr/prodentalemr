<?php

namespace App\Services\Verification;

use App\Models\BillingWorkItem;
use App\Models\VerificationFormSubmission;
use App\Support\VerificationResultPdf;

class PDFService
{
    public function output(BillingWorkItem $request, string $mode = 'standard', array $sections = [], array $questionIds = [], ?bool $showBlankRows = null, ?VerificationFormSubmission $submission = null): string
    {
        return VerificationResultPdf::output($request, $mode, $sections, $questionIds, $showBlankRows, $submission);
    }

    public function fileName(BillingWorkItem $request, string $mode = 'standard', ?VerificationFormSubmission $submission = null): string
    {
        return VerificationResultPdf::fileName($request, $mode, $submission);
    }
}
