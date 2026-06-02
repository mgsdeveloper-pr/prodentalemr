<?php

namespace App\Support;

use App\Models\PatientStatement;
use App\Models\SaasSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class PatientStatementPdf
{
    public static function fileName(PatientStatement $statement): string
    {
        return $statement->statement_number . '.pdf';
    }

    public static function output(PatientStatement $statement): string
    {
        $statement->loadMissing(['patient', 'location', 'creator']);
        $entries = $statement->ledgerEntries()->with(['serviceItem', 'provider.user'])->get();

        return Pdf::loadView('pdf.patient-statements.show', [
            'statement' => $statement,
            'entries' => $entries,
            'settings' => SaasSetting::current(),
        ])
            ->setPaper('a4')
            ->output();
    }
}
