# ProDental Current Product Scope

## Active Product

ProDental currently provides four shared-shell workspaces outside the deferred PMS area:

- **SaaS:** clients, DSO/organization/clinic structure, plans, subscriptions, billing, platform users, master data, support controls, and platform settings.
- **Verification:** queues, assignments, form work, clinic requests and responses, audit, SLA, reports, documents, portal credentials, and notifications.
- **Clinic:** appointments, calendar, patients as request context, verification requests/results, clinic responses, providers, portal credentials, documents, users, and clinic settings.
- **DSO:** network overview, clinic visibility, cross-clinic reporting, users, roles, and permissions.

Appointments and Calendar are core Clinic modules on every plan. Insurance policies are maintained in the Patient record and are consumed by appointment and verification workflows.

## Verification Service Models

- **Self-Managed:** the clinic completes the verification.
- **Managed Service:** the Verification team completes the verification.
- **Hybrid:** the clinic chooses the route per request when its enrollment permits both.

The selected processing mode is frozen on each request so later account configuration changes do not rewrite historical ownership.

## Registration Decision

Public tenant registration is a future capability and is closed by default with `PRODENTAL_PUBLIC_REGISTRATION=false`. It must not be enabled until plan selection, agreements, email verification, activation/approval, and onboarding controls are approved.

## Historical Integrity

Completed verification forms, question snapshots, reports, and audit history remain tied to the request snapshot that produced them. Template changes affect new or explicitly refreshed editable requests only.
