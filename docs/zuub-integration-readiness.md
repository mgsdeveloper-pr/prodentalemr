# Zuub integration readiness

## Provider overview

`/saas/eligibility-connections` is the entry point for Zuub, DentalXChange,
pVerify, Vyne/Onederful and Stedi. Configure opens a provider-specific detail
page; the existing singular URL still defaults to Zuub for compatibility.
Only Zuub has credential preparation so far. The others show explicit pending
setup pages, not copied or guessed authentication forms.

The production `eligibility_enabled` flag is distinct from the existing
non-patient probe `enabled` flag. All eligibility adapters are currently
incomplete, so activation is blocked in the UI and on the server. Disabling
preserves credentials and history. Future inquiry dispatch must enforce both
the activation flag and verified adapter/enrollment readiness; a key or a
successful probe is never sufficient. Apply the additional
`2026_09_11_130000_add_eligibility_activation_to_connections.php` migration.

## Current delivery

SaaS administrators can open Settings > Eligibility Connection at
`/saas/eligibility-connection`. Sandbox and production credentials are stored
separately with Laravel encrypted casts. Secrets are hidden from serialization,
never refilled into Livewire state, and cleared after save. Saving requires the
current administrator password. Blank credentials retain the existing value;
explicit removal deletes it. Events contain only actor, event, environment via
connection, and status, never credentials or vendor response bodies.

The local demonstration is fictional, does not use Zuub, and cannot write to
verification answers. It exercises partial benefits, inactive coverage, and
unavailable-payer presentation without chargeable requests or patient data.

## Important boundary

This is integration preparation, not a completed Zuub eligibility connector.
Zuub's public website directs partners to request API documentation. No endpoint,
authentication convention, payer identifier, or response schema is assumed.
An API key alone cannot unlock patient eligibility queries in this release.
There is no auto-fill, scheduled submission, webhook endpoint, or live inquiry.

The transport includes a strictly configured non-patient GET connectivity probe.
It is disabled by default. After Zuub supplies documentation, a developer may
configure `config/eligibility.php` with the exact approved HTTPS hostname,
non-patient endpoint, credential header/prefix, and safe-probe confirmation.
Do not configure a public health endpoint as proof of authenticated access.
If Zuub requires OAuth, POST authentication, or another mechanism, implement
that documented flow instead of coercing it into this probe. A 2xx response
only reports endpoint reachability, never completed eligibility verification.
Redirects and automatic retries are disabled. Tests are rate limited.
The host allowlist is deployment-controlled, never taken from browser input.
Keep HTTP debug/proxy logging off to avoid capturing authentication headers.

## Monday call checklist

- Partner/OpenAPI documentation and API version/support policy.
- Separate sandbox/production URLs and authentication details: key, OAuth,
  required headers, token expiration and rotation.
- Documented non-patient authentication test and synthetic sandbox patients.
- Clinic/location/provider enrollment and identifiers; multi-tenant SaaS rights.
- Payer directory, supported dental benefits, credential/MFA requirements.
- Subscriber/dependent schemas, date-of-service rules, procedure inquiry limits.
- Sync versus async responses; polling, webhooks and signature verification.
- Sample responses for active, inactive, partial, invalid member, rate limit,
  pending, and unavailable cases. Obtain source timestamps and transaction IDs.
- Patient versus payer coinsurance interpretation and benefit-period semantics.
- Price per inquiry, failed inquiries/retries, minimums and duplicate handling.
- Applicable data agreements, permitted use, retention and regional hosting.

## Finish after documentation arrives

1. Implement documented authentication and sandbox contract with HTTP fakes.
2. Add clinic and payer mappings with tenant-scoped authorization.
3. Validate request context and preserve encrypted original responses with
   retrieval time, transaction ID, source and explicit unknown values.
4. Show normalized returned benefits alongside current answers, never overwrite
   automatically. Approval must preserve audit history and template version.
5. Test retries/idempotency and billing behavior using vendor documentation.
6. Validate approved synthetic sandbox cases before authorizing patient data.
7. Run a limited production pilot; retain manual verification for missing fields.

## Deployment

Apply `2026_09_11_120000_create_eligibility_connections.php` through the existing
protected update process. It adds two tables and changes no patient records.
The settings page shows an update-required state if these tables are absent.
Back up the application encryption key with existing operational safeguards;
losing it prevents decryption of stored credentials.

Run `php vendor/bin/pest tests/Feature/EligibilityConnectionTest.php`.
No test calls Zuub or sends patient data.
