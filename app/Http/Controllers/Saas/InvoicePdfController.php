<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Billing\InvoiceDocumentService;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice, InvoiceDocumentService $documents): Response
    {
        abort_unless(auth()->user()?->can('download', $invoice), 403);

        return $documents->inlineResponse($invoice);
    }

    public function download(Invoice $invoice, InvoiceDocumentService $documents): Response
    {
        abort_unless(auth()->user()?->can('download', $invoice), 403);

        return $documents->downloadResponse($invoice);
    }
}
