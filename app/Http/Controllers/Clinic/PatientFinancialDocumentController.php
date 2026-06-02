<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\PatientLedgerEntry;
use App\Models\PatientStatement;
use App\Support\PatientReceiptPdf;
use App\Support\PatientStatementPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PatientFinancialDocumentController extends Controller
{
    public function showStatement(Request $request, PatientStatement $statement): Response
    {
        $this->authorizeStatement($request, $statement);

        return response(PatientStatementPdf::output($statement), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . PatientStatementPdf::fileName($statement) . '"',
        ]);
    }

    public function downloadStatement(Request $request, PatientStatement $statement): Response
    {
        $this->authorizeStatement($request, $statement);

        return response(PatientStatementPdf::output($statement), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . PatientStatementPdf::fileName($statement) . '"',
        ]);
    }

    public function showReceipt(Request $request, PatientLedgerEntry $entry): Response
    {
        $this->authorizeLedgerEntry($request, $entry);

        abort_unless($entry->entry_type === 'patient_payment', 404);

        return response(PatientReceiptPdf::output($entry), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . PatientReceiptPdf::fileName($entry) . '"',
        ]);
    }

    public function downloadReceipt(Request $request, PatientLedgerEntry $entry): Response
    {
        $this->authorizeLedgerEntry($request, $entry);

        abort_unless($entry->entry_type === 'patient_payment', 404);

        return response(PatientReceiptPdf::output($entry), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . PatientReceiptPdf::fileName($entry) . '"',
        ]);
    }

    protected function authorizeStatement(Request $request, PatientStatement $statement): void
    {
        $user = $request->user();

        abort_unless($user && $user->canAccessClinicPatientLedger(), 403);
        abort_unless(
            (int) $user->organization_id === (int) $statement->organization_id
            && (int) $user->clinic_id === (int) $statement->clinic_id,
            403
        );
    }

    protected function authorizeLedgerEntry(Request $request, PatientLedgerEntry $entry): void
    {
        $user = $request->user();

        abort_unless($user && $user->canAccessClinicPatientLedger(), 403);
        abort_unless(
            (int) $user->organization_id === (int) $entry->organization_id
            && (int) $user->clinic_id === (int) $entry->clinic_id,
            403
        );
    }
}
