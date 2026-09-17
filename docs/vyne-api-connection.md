# Vyne / Onederful connection

Source reviewed September 11, 2026: https://developers.onederful.co/documentation/

## Available now

- SaaS Settings > Eligibility Connections > Vyne / Onederful.
- Sandbox: POST https://sandbox.onederful.co/sandbox/eligibility with the vendor's fixed TEST PERSON identity. No credentials, no application patient data. Supported test payers: PRINCIPAL, AETNA_DENTAL_PLANS, DD_CALIFORNIA, METLIFE.
- Production authentication: POST https://production.onederful.co/oauth2/token with client_id and client_secret. Verify a Bearer token and feature:eligibility scope. Token is discarded, never displayed or persisted.
- Credentials stored as an atomic JSON pair inside the existing encrypted, hidden api_key column. Blank pair retains existing credentials; replacing requires both values. Removing requires disabling authentication tests.
- Explicit saved test enablement, administrator password confirmation for changes, two tests/minute/connection, bounded timeouts, no redirects or automatic retries. Audit records contain status only.
- Sandbox benefit rows preserve category, network, plan period and coverage level. Missing is not zero. No verification answer is overwritten.

## Still blocked

Live eligibility activation and patient submissions remain disabled. Authentication success and static sandbox responses do not establish payer coverage or clinic enrollment. The next release needs approved production testing, clinic/provider NPI and tax ID mapping, payer identifiers from the documented payer list, patient/subscriber mapping, tenant authorization at dispatch, reviewed benefit mapping, and response retention controls.

Documented future endpoints: GET https://production.onederful.co/payer/list and POST https://production.onederful.co/eligibility (version v2). These are not dispatched by this connection-settings feature. ClearCoverage is a separate product and is not implicitly enabled.

The public authentication example returns expires_in 3600, while its table describes milliseconds. No token cache is implemented; confirm lifetime semantics with Vyne before adding reuse.

## Deployment

Apply both existing eligibility connection migrations before configuring Vyne. No additional migration is needed for the encrypted credential pair. Do not commit real credentials. Tests use fake HTTP responses; live sandbox testing uses only the documented fictional payload.

September 11 validation: 41 automated connection tests passed (168 assertions). A direct request to the documented public sandbox returned HTTP 403. Sandbox access is not confirmed; ask Vyne whether account activation or access allowlisting is required. No production credentials or real patient data were sent.
