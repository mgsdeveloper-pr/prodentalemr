<?php

namespace App\Http\Controllers;

use App\Services\WebsiteCheckout\CheckoutService;
use Illuminate\Http\Request;

class WebsiteCheckoutController extends Controller
{
    public function show(Request $request, CheckoutService $checkout)
    {
        return response()->json(['checkout' => $checkout->summary(app(WebsiteRegistrationController::class)->authorized($request))])->header('Cache-Control', 'no-store');
    }
    public function start(Request $request, CheckoutService $checkout)
    {
        $registration = app(WebsiteRegistrationController::class)->authorized($request);
        $data = $request->validate(['gateway' => ['required', 'string', 'max:30'], 'confirm_initial_payment' => ['required', 'accepted']]);
        return response()->json(['checkout' => $checkout->start($registration, $data['gateway'])])->header('Cache-Control', 'no-store');
    }
    public function refresh(Request $request, CheckoutService $checkout)
    {
        return response()->json(['checkout' => $checkout->refresh(app(WebsiteRegistrationController::class)->authorized($request))])->header('Cache-Control', 'no-store');
    }
    public function webhook(Request $request, string $gateway, CheckoutService $checkout)
    {
        abort_if(strlen($request->getContent()) > 1024 * 1024, 413);
        $checkout->webhook($gateway, $request);
        return response()->json(['received' => true]);
    }
}
