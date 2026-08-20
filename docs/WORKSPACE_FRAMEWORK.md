# ProDental Workspace Framework

## Purpose

The Workspace Framework defines how ProDental product areas are composed without duplicating shell, navigation, UI, authorization, tenant, or context patterns.

Workspaces are product surfaces with distinct user intent. They share platform engines, but they do not merge business ownership.

## Shared Platform Capabilities

Every workspace should use:

- Enterprise AppShell for shared panel frame, header, sidebar, status bar, toolbar, and content boundary.
- ProDental Design System for reusable presentation components.
- Existing Filament resources and pages for CRUD and workflow surfaces.
- Existing policies, permissions, and tenant scope.
- Work Context Engine when a page benefits from side context.
- Public IDs where existing routes and resources support public identifiers.

## Current Workspaces

| Workspace | Current Surface | Primary Intent | Status |
| --- | --- | --- | --- |
| Platform Workspace | SaaS panel | Platform administration and tenant operations | Active |
| Verification Workspace | Verification panel and clinic verification resources | Insurance verification workflow | Active |
| Organization Operations Workspace | Clinic panel dashboard and verification operations | Organization, clinic, users, verification settings, portal credentials, verification documents, reports | Active |
| DSO Workspace | DSO panel | Multi-organization visibility | Active |
| Claims Workspace | Future | Claims workflow | Planned |
| PMS Workspace | Clinic panel modules | Clinic operations and patient care | Active by module |

## Workspace Composition Standard

Workspace pages should compose:

1. Existing Filament page or resource.
2. AppShell regions registered through the panel provider.
3. PDS components for reusable layout and visual structure.
4. Workspace-owned data prepared by the page, service, or resource.
5. Optional Work Context provider for reusable context cards.

Presentation components must receive already-authorized and already-scoped data. They must not fetch tenant data directly.

## Organization Operations Workspace Standard

The Organization Operations Workspace begins in the existing Clinic panel dashboard at `/clinic`.

The workspace uses existing domain records only:

- Organization
- Clinic
- User
- BillingWorkItem
- BillingWorkItemAttachment
- PortalCredential
- VerificationFormQuestion
- VerificationNotification
- AuditLog

It exposes links to existing Filament resources only when those resources already allow access.

No new routes, database tables, policies, permissions, or workflow transitions are introduced by the Organization Workspace phase.

It must remain inside the insurance verification product boundary. PMS, claims, appointment, clinical, treatment, and patient-registration workflows are future integration boundaries and are not part of this workspace implementation.

## Work Context Standard

Workspace context is supplied by providers implementing:

`App\Support\WorkContext\ContextProviderInterface`

Implemented providers:

- `VerificationContextProvider`
- `OrganizationContextProvider`

The renderer remains workspace-agnostic through:

- `resources/views/components/pds/work-context-panel.blade.php`
- `resources/views/components/pds/context-card.blade.php`

## Boundaries

The Workspace Framework must not:

- Replace Filament resources.
- Fork Filament layouts.
- Bypass policies.
- Bypass organization or clinic scope.
- Create duplicate CRUD screens.
- Merge Verification and PMS workflows.
- Add fake metrics or unsupported settings.

## Review Checklist

- Uses existing panel and route structure.
- Uses existing business records.
- Uses AppShell and PDS composition.
- Links to existing resources instead of duplicating them.
- Preserves policies, permissions, public IDs, and tenant boundaries.
- Keeps workflow actions in their current modules.
- Documents deferred work.

---

END OF DOCUMENT
