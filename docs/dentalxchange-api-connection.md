# DentalXChange XConnect connection

Reviewed September 11, 2026 against the standard Eligibility API 2.7.0:
- https://developer.dentalxchange.com/eligibility-api
- https://portal-assets.dentalxchange.com/docs/eligibility-api.yaml
- https://developer.dentalxchange.com/sandbox

## Delivered

SaaS Settings > Eligibility Connections > DentalXChange provides separate sandbox and production API-key storage, administrator password confirmation, credential removal/rotation and safe audit history. Credentials use the existing encrypted, hidden api_key column. Blank input keeps the saved key.

Health checks use the documented API-Key header:
- Sandbox: GET https://api.dentalxchange.com/sandbox/eligibility/health
- Production: GET https://api.dentalxchange.com/eligibility/health

Only an actual boolean healthy response is accepted. HTTP success alone is insufficient. No patient data, account username or account password is sent. Two requests per minute per connection, bounded timeouts, no redirects and no retries. Raw response bodies are not exposed or stored.

## Requirements and boundaries

Obtain sandbox access and an API key from DentalXChange. No live health check has been performed because no approved key is configured. A healthy response does not establish account authentication, payer access or patient benefit completeness.

This is the standard XConnect Eligibility API, not Enhanced Eligibility or the legacy SOAP API. Enhanced Eligibility has different authentication and account setup and is not silently substituted.

The documented POST /eligibility additionally requires DentalXChange account username/password headers and patient, provider and payer mappings. That endpoint is not dispatched in this release. Keep live activation disabled until those mappings, account access, secure response retention and tenant-authorized production testing are implemented. No account passwords are collected prematurely.

Apply the existing eligibility connection migrations before deployment. No new migration is required for this provider. Automated tests fake HTTP; do not substitute real patient data for sandbox testing.
