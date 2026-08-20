# Enterprise Authorization Phase Report

## Policy Inventory

Policies were introduced for the current business authorization surface:

- Organization, Clinic, User
- BillingWorkItem, VerificationProfile, VerificationFormQuestion, VerificationTemplateVersion, VerificationFormSubmission
- PortalCredential, UserMailbox, VerificationInboxMessage, VerificationInboxAttachment
- Invoice, Payment
- PatientDocument, BillingWorkItemAttachment
- VerificationNotification, AuditLog, SaasSetting

Future PMS and Claims resources still use their existing module checks until their domain policies are introduced in a later incremental pass.

## Policy Coverage Matrix

| Area | Policy Coverage |
| --- | --- |
| Platform administration | viewAny, view, create, update, delete |
| Verification work | viewAny, view, create, update, delete, assign, approve, download, export, import |
| Verification templates | viewAny, view, create, update, delete, publish |
| Mailbox and inbox | view, update, download |
| Billing | viewAny, view, create, update, delete, download |
| Documents | viewAny, view, create, update, delete, download |
| Notifications | view, update |
| Audit and settings | viewAny, view, update; destructive audit actions disabled |

## Permission Matrix

The policies preserve Spatie Permission as the permission engine. They call the existing `User` helpers backed by `PanelPermissionMatrix`.

| Panel | Roles | Modules |
| --- | --- | --- |
| Platform | saas_admin, saas_manager, saas_user | verification, organizations, clinics, locations, users, managed services, enrollments, invoices, payments, subscriptions, service items, insurance directory, portal credentials, templates, billing settings, settings, roles |
| Verification | verification_admin, verification_manager, verification_user, saas_admin override | verification, portal credentials, insurance directory, templates, reports, notifications, users, roles, settings |
| Clinic | clinic_admin, clinic_manager, doctor, receptionist, staff, saas_admin override | users, patients, providers, appointments, encounters, treatment plans, charting, documents, insurance, ledger, claims, statements, operatories, consent, verification requests, templates, services, roles |
| DSO | dso_admin, dso_manager, dso_viewer | dashboard, clinics, reports, users, roles, settings |
| Future PMS and Claims | not expanded in this phase | preserve existing checks until domain policies are added |

## Filament Authorization Report

Updated high-value business resources to call Laravel policies directly:

- SaaS Organizations
- SaaS Verification Requests
- SaaS Portal Credentials
- SaaS Invoices
- SaaS Payments

Remaining Filament resources still use existing user helper methods to preserve behavior and should be migrated incrementally.

## Controller Cleanup Report

Moved selected controller authorization checks to policies:

- SaaS invoice PDF show/download now use `InvoicePolicy::download`.
- SaaS verification request attachment preview/download now use `BillingWorkItemAttachmentPolicy::download`.
- Verification user mailbox previews/downloads now use `UserMailboxPolicy`.
- Verification inbox message previews now use `VerificationInboxMessagePolicy`.
- Verification inbox attachment downloads now use `VerificationInboxAttachmentPolicy`.
- Verification notification open/read actions now include `VerificationNotificationPolicy::update`.

## Spatie Integration Report

Policies do not replace roles or permissions. They delegate permission checks to existing Spatie-backed methods such as `canAccessSaasModule`, `canPerformSaasModuleAction`, `canAccessVerificationModule`, `canPerformVerificationModuleAction`, `canAccessClinicModule`, and `canPerformClinicModuleAction`.

## Backward Compatibility Report

No database changes were introduced in this authorization phase. Numeric IDs, public IDs, routes, Filament resources, Livewire flows, and existing business workflows are preserved.

## Risks

- Some Filament pages and future-domain resources still contain direct authorization helpers by design; moving all of them at once would carry higher regression risk.
- Verification result PDF and clinic financial/document controllers retain more specialized route-context checks and should be migrated after dedicated policies for those clinic/PMS financial records exist.

## Validation

Run:

- `php artisan route:list`
- `php artisan test tests\\Feature\\EnterpriseAuthorizationPolicyTest.php`
- `php artisan test tests\\Feature\\PublicIdStrategyTest.php`
- `php artisan test tests\\Feature\\Saas\\RevenueOperationsSmokeTest.php --filter "allows verification managers|refreshes a verification request"`
