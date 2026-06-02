<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Support\PayPalGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PayPalCheckoutController extends Controller
{
    public function show(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 401);
        abort_if($invoice->trashed(), 404);

        $invoice = PayPalGateway::createOrRefreshOrder($invoice);

        return redirect()->away((string) $invoice->paypal_approval_url);
    }

    public function complete(Request $request, Invoice $invoice): View
    {
        $orderId = $request->string('token')->toString();

        abort_if(blank($orderId), 404);

        try {
            $invoice = PayPalGateway::captureApprovedOrder($invoice, $orderId);
            $error = null;
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }

        return view('billing.invoices.payment-success', [
            'invoice' => $invoice->fresh('organization'),
            'gateway' => 'PayPal',
            'error' => $error,
        ]);
    }

    public function cancel(Invoice $invoice): View
    {
        return view('billing.invoices.payment-cancelled', [
            'invoice' => $invoice->fresh('organization'),
            'gateway' => 'PayPal',
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $headers = [
            'PAYPAL-AUTH-ALGO' => $request->header('PAYPAL-AUTH-ALGO'),
            'PAYPAL-CERT-URL' => $request->header('PAYPAL-CERT-URL'),
            'PAYPAL-TRANSMISSION-ID' => $request->header('PAYPAL-TRANSMISSION-ID'),
            'PAYPAL-TRANSMISSION-SIG' => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'PAYPAL-TRANSMISSION-TIME' => $request->header('PAYPAL-TRANSMISSION-TIME'),
        ];

        if (! PayPalGateway::verifyWebhook($headers, $payload)) {
            return response()->json(['message' => 'PayPal webhook verification failed.'], 400);
        }

        PayPalGateway::handleWebhookEvent($payload);

        return response()->json(['received' => true]);
    }
}
