# Stedi connection setup

Official sources reviewed September 11, 2026:
- https://www.stedi.com/docs/healthcare/api-reference
- https://www.stedi.com/docs/healthcare/api-reference/get-payers
- https://www.stedi.com/docs/healthcare/test-mode

## Available

SaaS Settings > Eligibility Connections > Stedi. Separate Test (internally sandbox) and Production encrypted API-key storage, administrator password confirmation, explicit removal, rotation and status-only audit history. Blank input retains the saved key. No new migration is needed beyond the existing eligibility connection migrations.

The read-only connection test uses GET https://payers.us.stedi.com/2024-04-01/payers?pageSize=10 with the raw API key in the Authorization header, without a Bearer prefix. A response must contain a list of payer records; HTTP success alone does not pass validation. No pagination is followed or payer data imported. Two calls per minute per connection, bounded timeouts, no redirects or retries. Raw response bodies are not exposed or logged.

## Important boundaries

Stedi's test/production mode is determined by the key, not by a different API hostname. Selecting Test in our UI does not turn a production key into a test key. The directory response does not establish the key's mode or its eligibility permissions. Test keys have limited supported endpoints and may be rejected by the payer directory. This must not be interpreted as proof that they are invalid for mock eligibility.

No mock or live eligibility transaction is submitted in this release. Before enabling those, use Stedi's approved mock requests and a confirmed test key, then implement clinic authorization, patient/payer/provider mappings, dental transaction support and enrollment checks, response mapping and retention controls. All production activation remains blocked.

No live API test was made because no approved key has been supplied. Automated tests use fake HTTP responses and verify all five provider settings remain isolated.
