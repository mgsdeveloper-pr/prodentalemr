<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\StripeGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoicePaymentPageController extends Controller
{
    public function show(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 401);
        abort_if($invoice->trashed(), 404);

        $invoice = StripeGateway::createOrRefreshCheckoutSession($invoice);

        return redirect()->away((string) $invoice->stripe_checkout_url);
    }

    public function success(Request $request, Invoice $invoice): View
    {
        abort_unless($request->hasValidSignature(), 401);

        return view('billing.invoices.payment-success', [
            'invoice' => $invoice->fresh('organization'),
            'gateway' => 'Stripe',
        ]);
    }

    public function cancel(Request $request, Invoice $invoice): View
    {
        abort_unless($request->hasValidSignature(), 401);

        return view('billing.invoices.payment-cancelled', [
            'invoice' => $invoice->fresh('organization'),
            'gateway' => 'Stripe',
        ]);
    }
}
