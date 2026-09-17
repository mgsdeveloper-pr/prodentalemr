# pVerify authentication connection

Source reviewed September 11, 2026: pVerify's official public Postman documentation, updated March 1, 2026.
https://www.postman.com/pverify/pverify-s-public-workspace/documentation/u6q5dba/pverify-api-documentation

## Available

SaaS Settings > Eligibility Connections > pVerify. Separate Test (internally sandbox) and Production credentials; both require a Client API ID and Client Secret issued by pVerify. The validated credential pair is stored atomically in the existing encrypted, hidden api_key column. Blank fields retain saved values; replacements require both; explicit removal requires tests disabled.

The documented authentication request uses POST with application/x-www-form-urlencoded body fields Client_Id, Client_Secret, grant_type=client_credentials and the Client-API-Id header:
- Test: https://testapi.pverify.com/Token
- Production: https://api.pverify.com/Token

Validate the returned token, Bearer token type and positive expiry. Tokens are immediately discarded, never displayed, logged or stored. Explicit saved test enablement, administrator password confirmation for changes, two tests per minute per connection, bounded timeouts, no redirects/retries and status-only audit events.

## Not enabled

No patient or eligibility calls, automatic routing, token reuse or form autofill. Authentication success does not establish dental API entitlement or payer coverage. Confirm the contracted dental endpoint, payer/provider mappings, clinic scope, response mapping and retention rules before implementing patient submissions. Production activation remains blocked by the provider catalog.

No live token call was made: approved credentials have not been supplied. Tests use fake HTTP responses. Apply existing eligibility connection migrations; no new schema migration is needed.
