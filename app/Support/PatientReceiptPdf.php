<?php

namespace App\Support;

use App\Models\PatientLedgerEntry;
use App\Models\SaasSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class PatientReceiptPdf
{
    public static function fileName(PatientLedgerEntry $entry): string
    {
        $reference = $entry->reference_number ?: 'receipt-' . $entry->id;

        return str_replace([' ', '/'], '-', $reference) . '.pdf';
    }

    public static function output(PatientLedgerEntry $entry): string
    {
        $entry->loadMissing(['patient', 'provider.user', 'location', 'serviceItem', 'creator']);

        return Pdf::loadView('pdf.patient-receipts.show', [
            'entry' => $entry,
            'settings' => SaasSetting::current(),
        ])
            ->setPaper('a4')
            ->output();
    }
}
