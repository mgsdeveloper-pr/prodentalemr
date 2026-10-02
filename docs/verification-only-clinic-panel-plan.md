# Verification Only Clinic Panel Plan

Saved: 2026-10-01

Status: Proposed scope saved for later. This note does not authorize implementation, PMS deactivation, publication, or deployment.

## Purpose

Provide a complete clinic-facing verification workspace without exposing unnecessary PMS screens. Preserve shared records, existing requests, answers, template snapshots, and history. Hide navigation before considering service-level deactivation.

## Proposed Panel Scope

Organize navigation into Work, Records, and Administration rather than separate workspaces for each feature.

| Area | Scope |
| --- | --- |
| Dashboard | Pending and urgent requests, appointments awaiting verification, and clinic responses due. |
| Verification Requests | Create/import requests, select Short/Full forms, track status, review results, and download PDFs. |
| Clinic Responses | Answer questions raised by the verification team, provide missing information and attachments, and track resolution against the original request. |
| Appointments | Appointment list/import, patient/provider/date details, and linked verification status. |
| Patients and Insurance | Essential patient, subscriber, member ID, and insurance-policy maintenance without the full clinical PMS. |
| Shared Inbox | Clinic-scoped conversations, attachments, and request linking. |
| Document Centre | Supporting documents, verification PDFs, and request-linked attachments. |
| Templates and Builder | Independent Full/Short templates, sections/subsections, question editing/uploads, preview, naming, versions, and controlled publishing. |
| Users and Access | Invite/deactivate staff, assign roles, and restrict access by clinic/location. |
| Clinic Settings | Clinic profile, locations, providers, identifiers, portal credentials, mailbox configuration, and PDF output settings. |
| Reports and Activity | Request volumes, turnaround, outstanding responses, and audit history. |

## Panel Responsibilities

- Clinic panel: submit requests, maintain clinic information, respond to queries, review results, and process clinic-owned verification where enabled.
- Verification team panel: cross-clinic queues, assignment, processing, clinic queries, quality review, and completion.
- SaaS administration: subscriptions, service activation, platform configuration, and administrative oversight.
- Hybrid operation: clinic staff and the service team can both perform verification. Each request needs clear ownership and controlled handover to prevent conflicting updates.
- Clinic users must retain their clinic-facing verification portal; they should not automatically be routed into the service team's portal.

## Access Safeguards

- Enforce clinic/location boundaries on records, files, searches, exports, and direct URLs, not only navigation.
- Separate view, create, edit, delete, export, and publish permissions.
- Restrict user administration, mailbox credentials, and template publishing to authorized roles.
- Preserve published template versions, existing request snapshots, saved answers, and audit history.
- Keep clinic responses attached to their original requests.
- Retain clinic switching for multi-clinic users and automatically select the clinic for single-clinic users.
- Hide workspace switching for single-workspace users while preserving administrative access where needed.

## Dependencies Found in the Code Review

- Verification-only clinic routing already exists. A single enabled service can bypass the clinic workspace-choice screen.
- Patient and insurance-policy management are currently classified as PMS modules, although verification intake and imports read those records. Preserve records and provide essential maintenance before disabling their screens.
- Appointments are included in verification modules. Linked appointments supply patient, provider, location, and date information.
- Providers and locations are shared modules; subscription entitlements and permissions still govern access.
- Verification requires active verification service/enrollment configuration and uses shared BillingWorkItem infrastructure. Financial screens may be hidden, but these underlying services and records must remain.
- Document Centre and shared inbox depend on verification access, feature availability, and workspace state. Initialize the workspace correctly rather than merely hiding its selector.
- The global portal switcher and the clinic Verification/PMS workspace choice are different controls. Treat their routing and visibility separately.
- Previous verification history is not a substitute for payer-confirmed service/claims history; retain the ability to capture that information.

Code reference points: app/Support/ClinicWorkspace.php, app/Models/User.php, app/Support/SaasEntitlements.php, app/Services/Verification/VerificationIntakeService.php, app/Http/Middleware/EnsureClinicWorkspaceSelected.php, app/Filament/Clinic/Pages/DocumentCenter.php, and resources/views/filament/appshell/global-header.blade.php.

## Excluded PMS Screens

Keep charting, treatment planning, patient ledgers, claims management, and patient statements outside this proposed verification-focused clinic panel. This is a navigation/product-scope proposal, not a request to delete their data or shared infrastructure.

## Validation Before Implementation

The dependency review was source-level, supported by 12 existing related tests with 85 assertions. It was not a complete PMS-off workflow test.

Before deactivation, verify clinic-admin and staff access, subscriptions, patient/insurance maintenance, appointment imports, request creation, hybrid handover, clinic responses, inbox, documents, template publishing, PDFs, and historical record access. Confirm cross-clinic isolation and direct-link authorization. No module was disabled during the review.
