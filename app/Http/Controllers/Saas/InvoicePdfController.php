<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\InvoicePdf;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice): Response
    {
        abort_unless(auth()->user()?->hasAnyRole(['saas_admin', 'saas_manager', 'saas_user']), 403);

        return response(InvoicePdf::output($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . InvoicePdf::fileName($invoice) . '"',
        ]);
    }

    public function download(Invoice $invoice): Response
    {
        abort_unless(auth()->user()?->hasAnyRole(['saas_admin', 'saas_manager', 'saas_user']), 403);

        return response()->streamDownload(
            fn () => print(InvoicePdf::output($invoice)),
            InvoicePdf::fileName($invoice),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
