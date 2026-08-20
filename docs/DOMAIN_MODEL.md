# ProDental Domain Model

## Purpose

This document describes the current enterprise domain model at a product level. It is documentation only and does not change database behavior.

## Tenant Model

Organization is the primary tenant boundary.

Nested scope:

1. DSO
2. Organization
3. Clinic
4. Location
5. User, verification document, billing, notification, portal credential, and workflow records

SaaS Super Admin bypass exists where implemented by the application. Normal workspace access must remain organization and clinic scoped.

## Core Domains

| Domain | Primary Models | Notes |
| --- | --- | --- |
| Organization | Organization, Clinic, Location, Dso | Tenant and operating unit structure |
| Access | User, Role, Permission | Uses Spatie permissions and existing panel access rules |
| Verification | BillingWorkItem, BillingWorkItemActivity, VerificationFormQuestion, VerificationTemplateVersion, VerificationTemplateSection | Flagship workflow and template system |
| Clinic Scheduling | Patient, Appointment, ClinicService, ClinicOperatory, Provider | Appointments and Calendar are core; patient insurance is the source for verification intake |
| Deferred PMS | Encounter, TreatmentPlan, clinical charting and claims operations | Outside the current product-readiness scope |
| Providers | Provider, Staff, User | Clinic-owned provider records support scheduling and verification context |
| Documents | BillingWorkItemAttachment | Verification request supporting documents |
| Billing | Invoice, Payment, PatientLedgerEntry, PatientStatement, Subscription | SaaS and patient financial records |
| Security and Audit | AuditLog, SaasEntitlementAuditLog, PortalCredential, PortalCredentialPasswordHistory | Security-sensitive records and operational traceability |
| Notifications | VerificationNotification and existing notification support classes | Verification notification surfaces |

## Organization Operations Workspace Domain Use

The Organization Operations Workspace currently composes existing models for insurance verification operations only:

- Organization profile from `Organization`.
- Clinic list and selected clinic context from `Clinic`.
- User counts from `User`.
- Verification summary from `BillingWorkItem`.
- Verification document summary from `BillingWorkItemAttachment`.
- Portal readiness from `PortalCredential`.
- Template readiness from `VerificationFormQuestion`.
- Notification visibility from `VerificationNotification`.
- Recent activity from `AuditLog`.

The workspace does not introduce new persistence in this phase.

## Product Boundary

The Organization Operations Workspace does not implement:

- Patients
- Appointments
- Treatment plans
- Clinical records
- Claims workflow
- PMS scheduling
- Clinical procedures

Future integrations may reference those domains after the verification platform boundary is explicitly expanded.

## Public Identity

Business models that already use public IDs continue to do so through `App\Traits\HasPublicId`.

Numeric primary keys remain the database relationship keys.

## Deferred Domain Work

- Centralized repositories for complex cross-domain reporting.
- Dedicated organization profile service if profile editing expands.
- Unified activity stream beyond existing audit records.
- Dedicated verification operations readiness evaluator if existing-state checks become more complex.
- Formal domain diagrams after the remaining workspaces stabilize.

---

END OF DOCUMENT
