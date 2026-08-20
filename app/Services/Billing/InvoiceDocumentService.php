<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Support\InvoicePdf;
use Symfony\Component\HttpFoundation\Response;

class InvoiceDocumentService
{
    public function inlineResponse(Invoice $invoice): Response
    {
        return response(InvoicePdf::output($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . InvoicePdf::fileName($invoice) . '"',
        ]);
    }

    public function downloadResponse(Invoice $invoice): Response
    {
        return response()->streamDownload(
            fn () => print(InvoicePdf::output($invoice)),
            InvoicePdf::fileName($invoice),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
