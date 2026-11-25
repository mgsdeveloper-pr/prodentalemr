# Denentiq checkout — first billing period

## Customer flow

1. Start or resume the website registration using the verified email link.
2. Finish the practice details, choose a plan, and submit the registration.
3. Select **Continue to checkout**. The checkout displays the saved plan and exact initial amount. Monthly means the first month; yearly means the first year. One-time plans take one payment. Custom quotes and zero-price plans do not create online charges.
4. Choose an available gateway and confirm the initial payment. Stripe and PayPal are implemented. The browser cannot supply the charge amount, currency, return destination or paid status.
5. Complete payment on the provider's hosted page. The website never receives card numbers. The app saves the checkout and payment attempt before contacting the provider.
6. Return to the website. It checks the gateway through the app; a success/cancel query string does not prove payment. PayPal approvals are captured by the server. Verified webhooks also update the record if the customer closes the browser.
7. Use **Check payment status** or resume later with an email link. An expired website session does not lose the payment record. Refunds/reversals reported by the supported provider flows are retained separately from the original settlement.
8. Staff review the registration and confirmed payment in the app, then activate through the existing onboarding wizard. Checkout does not create an automatic subscription, renew a plan, provision a workspace, or log the customer into the app.

## What the app stores

- One checkout per website registration, with an immutable selected-offer snapshot, integer minor-unit amount, currency, initial-payment confirmation timestamp and normalized payment status.
- Each gateway attempt, its test/live environment, provider checkout/payment references, saved continuation URL, last check, and whether reconciliation needs attention.
- Processed webhook event identifiers for duplicate detection. Raw webhook payloads, card details and full provider responses are not retained by this integration.
- The existing registration/onboarding link, which continues to reference the organization after staff activation.

Open **SaaS → Billing → Website Payments** for payment history and **Check gateway status**. The registration list also shows payment status. Financial records are in this separate registration-payment ledger because the applicant may not have an organization or invoice yet; they are not copied into legacy Invoice Payments. Finance should use this ledger for the initial payment and handle renewal invoices separately. Existing invoice gateways remain unchanged.

Supported states include pending, paid, failed, expired, refunded, partially refunded, disputed/reversed where supplied by the adapter, and review required for conflicting settlements. A timeout is not classified as a failed charge. It stays available for reconciliation with the original idempotency key.

## cPanel setup without SSH

1. Back up the app/database. Upload this app release and use the existing **System Updates** screen to apply the checkout migration and refresh application caches. It creates three payment tables and does not remove customer data. Inspect all pending migrations shown by that screen.
2. Upload the new website deployment ZIP contents to the website document root. Keep `config.local.php` above that root and retain the existing registration bridge settings. Checkout uses that same authenticated PHP relay. Enable PHP cURL, sessions, HTTPS and outbound provider/app HTTPS connections. The relay allows 75 seconds for checkout verification; configure hosting request/PHP limits to accommodate multi-step PayPal verification (for example 90 seconds).
3. In **SaaS → Settings → Payment Credentials**, configure one or both provider API credentials. Start with Stripe test keys or PayPal sandbox credentials. The provider must be enabled there. Set the matching environment; do not point test-labelled checkout at live credentials.
4. In the app's private `.env`, configure:

   ```dotenv
   WEBSITE_CHECKOUT_ENABLED=true
   WEBSITE_CHECKOUT_RETURN_URL=https://denentiq.com/checkout
   WEBSITE_CHECKOUT_STRIPE_WEBHOOK_SECRET=your_separate_website_endpoint_secret
   WEBSITE_CHECKOUT_PAYPAL_WEBHOOK_ID=your_separate_website_webhook_id
   ```

   Only configure the webhook secret/ID for providers you intend to enable. Keep unused providers disabled. `APP_URL` must be the app's public HTTPS address so the URLs displayed in Payment Credentials are correct. Apply changes through the existing cache-refresh/release process.
5. In Stripe, add a separate endpoint at `https://app.denentiq.com/website-payments/webhook/stripe`. Subscribe to `checkout.session.completed`, `checkout.session.expired`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `charge.refunded`, `charge.dispute.created`, and `charge.dispute.closed`. Put this endpoint's signing secret in `WEBSITE_CHECKOUT_STRIPE_WEBHOOK_SECRET`; do not replace the existing invoice endpoint's secret.
6. In PayPal, add `https://app.denentiq.com/website-payments/webhook/paypal` to the intended sandbox/live application. Subscribe to `CHECKOUT.ORDER.APPROVED`, `CHECKOUT.ORDER.COMPLETED`, `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.PENDING`, `PAYMENT.CAPTURE.DENIED`, `PAYMENT.CAPTURE.REFUNDED`, and `PAYMENT.CAPTURE.REVERSED`. Set this webhook's ID in `WEBSITE_CHECKOUT_PAYPAL_WEBHOOK_ID`. The app asks PayPal to verify callback signatures before processing them.
7. Publish the actual plans and review their final initial amount and currency. This checkout charges exactly that saved amount; it does not add automatic taxes, discounts or usage fees. Supported configured currencies use two decimal places. PayPal is offered for USD, EUR, GBP, CAD and AUD; Stripe also permits the configured INR offering, subject to the merchant account's capabilities.
8. Keep the app's Laravel scheduler running using the existing cPanel Cron Jobs setup. It now includes `website-payments:reconcile --limit=5` every 15 minutes as a fallback for missed callbacks. No SSH session is needed for cPanel Cron Jobs. If the scheduler is not configured, callbacks still work and staff can use **Check gateway status**. For a new cron setup, use your host's documented PHP executable and the full app path for `artisan schedule:run`; these vary by hosting account.
9. Run a complete provider sandbox checkout on the real HTTPS domains. Check successful payment, browser cancellation, closing the browser before return, duplicate callback delivery, resume from a new browser, and failure/timeout recovery. Verify that test records are labelled **test** in the app. These local tests used fake provider responses; live credentials and inbox/provider delivery have not been verified.
10. Switch API credentials, environment and the separate webhook configuration together for live operation. Create a fresh registration for live acceptance testing. Historical test attempts remain test records; they cannot be silently converted to live charges or used to activate an account.

## Reconciliation and changes

An active payment cannot switch gateways midway; this prevents two payable sessions for one registration. Continue or reconcile it first. A confirmed expiry/failure allows a new attempt. A browser cancellation alone does not revoke a payable gateway session. Uncertain creations older than five hours require staff/provider investigation rather than automatic creation with an expired idempotency key. Staff can inspect references in Payment History and check the original gateway; this release intentionally has no “mark paid” or “discard uncertain payment” button.

Registration editing/reopening is blocked after checkout starts to preserve the offer being charged. If the offer needs to change, finance must reconcile the existing payment first; automatic repricing, refund initiation, cancellation of remote sessions, and migration of an in-progress checkout between gateways are not included. Refunds are initiated in the provider dashboard and reflected through callbacks/status checks. No automated access revocation is performed after a post-activation refund or dispute; staff review is required.

Once checkout has started, the staff activation guard requires a matching confirmed **live** payment. When checkout is enabled, a positive, non-quote website offer also requires payment before activation even if the customer has not started paying. Quote-only/no-charge offers retain staff-controlled onboarding. A different plan cannot be substituted for the paid offer without billing reconciliation.

## Adding another provider

Implement `App\Services\WebsiteCheckout\RegistrationPaymentGateway` and add it to `config/website_checkout.php` under `drivers`. The implementation owns availability, supported currencies, test/live environment, hosted URL allowlist, idempotent creation, authenticated status lookup, capture if required, and webhook verification. The common service owns the order, amount/reference checks, history, locks, duplicate handling and normalized status.

The current shared creation retry window assumes the provider retains idempotency keys for at least six hours; a new adapter with a shorter guarantee must shorten that policy before enablement. A new currency with different minor units requires extending `CheckoutMoney`, not guessing decimal places. Configure provider secrets only in the app, never in website assets or the browser. No source changes to registration or checkout UI are needed for an adapter that follows this contract.

Implementation references: [Stripe Checkout sessions](https://docs.stripe.com/api/checkout/sessions/create), [Stripe session lookup](https://docs.stripe.com/api/checkout/sessions/retrieve), [PayPal Orders](https://developer.paypal.com/api/orders/v2), [PayPal idempotency](https://developer.paypal.com/api/rest/reference/idempotency/), and [PayPal webhook verification](https://developer.paypal.com/api/rest/webhooks/rest/).
