<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Support\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $signature = $request->header('Stripe-Signature');

        if (blank($signature)) {
            return response()->json(['message' => 'Missing Stripe signature.'], 400);
        }

        try {
            $event = StripeGateway::constructWebhookEvent($request->getContent(), $signature);
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 400);
        }

        StripeGateway::handleWebhookEvent($event);

        return response()->json(['received' => true]);
    }
}
