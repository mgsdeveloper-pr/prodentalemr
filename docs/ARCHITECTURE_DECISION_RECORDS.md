# ProDental Architecture Decision Records

## ADR-001: Organization Operations Workspace Boundary

Status: Accepted

Date: 2026-08-06

## Context

The platform contains current and future dental operations capabilities, but the active Organization Operations Workspace phase must support insurance verification operations only.

## Decision

Implement Organization Operations as a verification operations workspace using existing Clinic panel, AppShell, PDS, Work Context Engine, Filament pages/resources, policies, permissions, and tenant scope.

Do not introduce PMS, claims, appointment, clinical, treatment, patient-registration, new persistence, or duplicate CRUD behavior.

## Consequences

- Dashboard content remains verification-focused.
- Workspace readiness uses existing state only.
- Provider, patient-document, subscription, appointment, and clinical modules are future or separate surfaces.
- Existing Verification workflow and Focus Mode are preserved.

---

END OF DOCUMENT
