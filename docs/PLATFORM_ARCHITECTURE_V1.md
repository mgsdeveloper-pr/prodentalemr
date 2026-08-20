# ProDental Platform Architecture V1

Version: 1.0
Status: Approved
Owner: Platform Architecture
Last Updated: 2026-08-05

---

# 1. Introduction

## Purpose

This document is the canonical technical architecture reference for the ProDental Platform.

It consolidates the approved product, platform, workspace, security, UI, and engineering standards into one enterprise architecture handbook. Future engineering work orders, implementation plans, code reviews, and AI-assisted development prompts must use this document as the primary platform reference.

## Vision

ProDental Platform is an enterprise dental SaaS platform built for scalability, maintainability, security, and productivity.

Laravel is the implementation framework. The product architecture is the primary asset.

## Scope

This architecture covers:

- Workspace-driven SaaS architecture.
- Enterprise AppShell.
- ProDental Design System.
- Work Context Engine.
- Focus Mode.
- Provider-driven context architecture.
- Laravel application layering.
- Security, authorization, tenant, and public ID strategy.
- Development standards and future platform roadmap.

This document does not replace detailed implementation docs, source code, policies, tests, or migration history. It defines the intent and standards that guide them.

## EWO-015 Boundary Note

The Organization Operations Workspace is constrained to insurance verification operations. It may compose organization, clinic, users, verification settings, portal credentials, verification documents, activity, and reports from existing records.

It must not introduce PMS or claims workflow behavior, duplicate CRUD, create new persistence, or change Verification workflow, Focus Mode, Work Context Engine, authorization, public IDs, or tenant architecture.

## Target Audience

This document is intended for:

- Product architects.
- Laravel engineers.
- Filament and Livewire developers.
- UX engineers.
- QA engineers.
- Security reviewers.
- AI coding assistants.
- New developers onboarding to ProDental Platform.

## Architecture Principles

The platform is governed by these principles:

- Enterprise Backend.
- Minimal Frontend.
- Maximum Productivity.
- Reuse before rewrite.
- Presentation does not own business logic.
- Workspaces remain separate.
- Verification is the flagship proving ground.
- Shared platform engines are preferred over local page patterns.
- Security, policies, tenant isolation, and auditability are architecture requirements.
- Documentation is part of the deliverable.

---

# 2. Platform Overview

ProDental Platform is a workspace-driven enterprise SaaS product for the United States dental industry.

The platform supports operational, administrative, and future clinical workflows through independent workspaces that share the same platform foundation. Each workspace has its own user intent, navigation model, permissions, and workflow boundaries.

## Supported Organization Types

The platform architecture supports:

- Solo Practice.
- Multi-Location Practice.
- Group Practice.
- Dental Service Organization (DSO).

The tenant boundary is the organization. Clinics and locations are nested inside that organization boundary. Future organization models should be configuration-driven wherever possible, not implemented as separate product architectures.

## Platform Philosophy

The product philosophy is:

- Enterprise Backend: durable Laravel architecture, policies, services, actions, auditability, and secure data handling.
- Minimal Frontend: productive, professional interfaces that avoid visual noise and duplicated UI patterns.
- Maximum Productivity: workflows reduce clicks, scrolling, navigation, and context switching.

The platform is not a collection of isolated Laravel resources. It is a reusable enterprise product system built from platform engines, workspaces, domain workflows, and governed UI primitives.

---

# 3. High-Level Architecture

The platform is organized into layered architecture:

```text
Presentation Layer
        |
Workspace Layer
        |
Platform Engine Layer
        |
Application Layer
        |
Domain Layer
        |
Infrastructure Layer
```

## Presentation Layer

The presentation layer includes Filament pages, Livewire components, Blade views, and PDS components.

Responsibilities:

- Render user interfaces.
- Display already-authorized data.
- Collect input.
- Trigger existing actions and services.
- Provide accessible, responsive, consistent experiences.

The presentation layer must not own business logic, authorization rules, workflow transitions, tenant rules, or database behavior.

## Workspace Layer

The workspace layer organizes the product by user intent.

Current and planned workspaces:

- Platform Workspace.
- Verification Workspace.
- Organization Workspace.
- Claims Workspace.
- PMS Workspace.

Responsibilities:

- Define workspace navigation.
- Compose AppShell regions.
- Host workspace-specific pages.
- Use workspace permissions.
- Provide context through providers.

Workspaces share platform engines, but they do not merge business responsibilities. Verification and PMS must remain separate.

## Platform Engine Layer

The platform engine layer contains reusable capabilities that are shared across workspaces.

Current and planned engines:

- Enterprise AppShell.
- ProDental Design System.
- Workspace Framework.
- Work Context Engine.
- Focus Mode.
- Provider Architecture.
- Navigation Engine (planned).

Responsibilities:

- Standardize layout.
- Standardize reusable UI.
- Provide shared productivity patterns.
- Enable provider-driven context.
- Prevent duplicated workspace implementations.

## Application Layer

The application layer coordinates business use cases.

Responsibilities:

- Controllers validate, authorize, call services or actions, and return responses.
- Services coordinate reusable domain operations.
- Actions represent single business operations.
- Policies enforce access decisions.
- Providers register framework, panel, and platform behavior.

## Domain Layer

The domain layer contains business entities, relationships, workflow state, and domain-specific models.

Responsibilities:

- Represent business records.
- Define relationships.
- Store domain state.
- Keep model responsibilities cohesive.

Large models should be reduced over time by moving permission logic, workflow logic, query logic, and business rules into services, actions, traits, policies, and providers.

## Infrastructure Layer

The infrastructure layer includes database migrations, queues, mail, storage, external gateways, PDFs, imports, exports, and integration boundaries.

Responsibilities:

- Persist data.
- Run asynchronous tasks.
- Manage file and document access.
- Integrate with payment, mail, portal, and future API systems.
- Support auditability and operational reliability.

---

# 4. Platform Engines

Platform engines are reusable product capabilities. They are not one-off page features.

## Enterprise AppShell

The Enterprise AppShell is the shared workspace frame layered onto Filament.

AppShell regions:

- Global Header.
- Sidebar.
- Workspace Header.
- Status Bar.
- Action Toolbar.
- Content Container.
- Compact Footer.

The AppShell extends Filament through render hooks and shared Blade partials. It does not fork vendor layouts, replace resources, change routes, or change workflows.

## ProDental Design System (PDS)

PDS is the reusable UI component foundation for the platform.

PDS components are presentation-only. They must not contain business logic, authorization logic, tenant logic, workflow transitions, database access, or validation rules.

PDS includes layout, buttons, status, cards, forms, table support surfaces, navigation, timeline, focus mode, feedback, loading, and container components.

## Workspace Framework

The Workspace Framework is the architectural pattern that keeps workspaces independent while allowing them to share AppShell, PDS, authentication, authorization, tenant standards, and platform engines.

The explicit `docs/WORKSPACE_FRAMEWORK.md` reference is not currently present. The framework is therefore defined by the approved AppShell, architecture standards, decision log, component inventory, and Work Context Engine.

## Work Context Engine

The Work Context Engine renders provider-supplied workspace context.

It does not fetch business data directly. It consumes generic context objects and renders reusable context cards.

Initial provider:

- VerificationContextProvider.

Future providers:

- OrganizationContextProvider.
- ClaimsContextProvider.
- PmsContextProvider.
- BillingContextProvider.
- SaasContextProvider.

## Navigation Engine (Planned)

The Navigation Engine is planned as a future platform capability.

Expected responsibilities:

- Workspace-aware navigation.
- Permission-aware visibility.
- Consistent workspace switching.
- Future command, quick create, and search integration.

Until implemented, existing Filament navigation and AppShell workspace switching remain authoritative.

## Focus Mode

Focus Mode is an AppShell and PDS platform capability.

It hides distractions and preserves the active workflow surface. The initial implementation begins with the Verification Form and reuses the existing verification form, Quick Reference, save actions, audit behavior, request-to-clinic workflow, template refresh behavior, and permissions.

Focus Mode must remain presentation-only.

## Provider Architecture

Providers convert workspace-owned, already-authorized data into generic platform engine payloads.

Provider responsibilities:

- Prepare display context for platform rendering.
- Respect workspace ownership.
- Reuse existing services, data, and permissions.
- Avoid database access in generic renderers.

Provider restrictions:

- Do not bypass policies.
- Do not create business workflow transitions.
- Do not duplicate business logic.
- Do not fetch cross-tenant data.

---

# 5. Workspace Architecture

## Workspace Framework

Workspaces are independent product areas with their own user intent, permissions, navigation, and workflow ownership.

Current workspaces:

- Platform Workspace.
- Verification Workspace.
- Organization Workspace.
- DSO / Organization Workspace.

Future workspaces:

- Claims Workspace.
- PMS Workspace.

## Workspace Regions

Each workspace uses the shared AppShell regions:

- Header for identity and global utilities.
- Sidebar for workspace navigation.
- Workspace Header for page context.
- Status Region for compact state.
- Action Toolbar for page actions.
- Content Container for primary work.
- Footer for compact metadata.

Focus Mode may adjust visibility of regions, but it remains a shared AppShell behavior.

## Workspace Lifecycle

A workspace page follows this lifecycle:

1. Authenticate the user.
2. Authorize workspace and panel access.
3. Resolve tenant and clinic scope.
4. Load the Filament resource or Livewire page.
5. Compose AppShell regions.
6. Render workspace content through Filament, Livewire, Blade, and PDS.
7. Trigger services or actions for business operations.
8. Record audit or activity events where required.

## Workspace Composition

Workspace pages should be composed from:

- Filament resources for resource CRUD, tables, and forms.
- Livewire pages for interactive workflows.
- PDS components for reusable UI patterns.
- AppShell regions for shared page structure.
- Work Context Engine providers for context panels.
- Services and actions for business behavior.

## Supported Workspace Types

Platform supports:

- Operational workspace: Verification.
- Administrative workspace: SaaS Administration.
- Organization workspace: Clinic and DSO management.
- Future financial workspace: Billing and claims.
- Future clinical workspace: PMS.

---

# 6. Work Context Engine

The Enterprise Work Context Engine is a provider-driven platform rendering layer.

Architecture:

```text
Workspace
    |
Context Provider
    |
WorkContext
    |
Context Cards
    |
PDS Work Context Panel
```

## Provider Pattern

Every workspace supplies context through `ContextProviderInterface`.

The provider returns a `WorkContext` payload. The renderer displays it without knowing which workspace supplied it.

## Context Cards

Each context card supports:

- Expanded.
- Collapsed.
- Loading.
- Empty.
- Error.
- Disabled.
- Pinned.
- Scrollable.
- Optional badge.
- Optional actions.
- Optional footer.

Context cards are reusable and provider-supplied.

## Context Panel

The Context Panel is rendered through PDS and is intended to be:

- Sticky left panel on desktop.
- Collapsible on tablet.
- Drawer-capable on mobile in future phases.

The initial implementation renders a responsive PDS panel without adding workflow behavior.

## Verification Provider

The first provider is the Verification provider.

It supplies:

- Quick Reference.
- Verification Summary.
- Insurance Summary.
- Patient Summary.
- Assigned User.
- Due Date.
- Priority.
- Internal Notes.
- Attachments.
- Timeline.
- Verification Metadata.
- AI Assistant reserved slot.

It reuses existing page-provided data and does not introduce new fields, relationships, routes, policies, or workflow changes.

## Future Providers

Future providers should include:

- OrganizationContextProvider.
- ClaimsContextProvider.
- PmsContextProvider.
- BillingContextProvider.
- SaasContextProvider.

Each provider should map workspace-owned data into generic cards.

## Future AI Slot

The engine reserves an AI Assistant card.

No AI logic exists in the current architecture sprint. Future AI may support missing information detection, duplicate attachment detection, previous verification lookup, suggested next actions, timeline summaries, completeness checks, and document summaries.

AI assists, never replaces, user judgment or business workflow ownership.

## Future Search Slot

The engine reserves a Context Search slot.

Future search must operate over provider-supplied context and must preserve authorization, tenant isolation, and PHI handling requirements.

---

# 7. Design System

## Component Philosophy

PDS exists to make the platform consistent, professional, fast, accessible, and predictable.

Reusable UI belongs in PDS or AppShell. Page-specific duplication is not permitted when a reusable component exists or can be safely extended.

## Naming Conventions

Blade components use:

```text
<x-pds.component-name>
```

Documentation names use the `Pds` prefix:

- PdsButton.
- PdsCard.
- PdsStatusPill.
- PdsWorkspaceTitle.
- PdsWorkContextPanel.
- PdsContextCard.

## Component Reuse

Before creating a component:

1. Reuse existing component.
2. Extend existing component.
3. Create new component only when necessary.

New reusable components must be registered in the Component Inventory.

## Component Lifecycle

Component status values:

- Planned.
- Design.
- In Development.
- Completed.
- Deprecated.
- Future.

Components should be stable, cohesive, accessible, responsive, and framework-compatible.

## Accessibility

All interactive UI must support:

- Keyboard access.
- Visible focus states.
- Semantic markup.
- ARIA labels where needed.
- Sufficient color contrast.
- Screen-reader clarity.

## Responsiveness

Desktop experiences should be efficient and dense where appropriate. Tablet and mobile experiences must remain functional without overlap, clipping, or broken action surfaces.

---

# 8. Application Architecture

## Controllers

Controllers should:

- Validate.
- Authorize.
- Call services or actions.
- Return responses.

Controllers should not own reusable business logic.

## Services

Services coordinate domain behavior that is reused or important enough to centralize.

Current service domains include:

- Verification.
- Billing.
- Payment.
- Tenant Access.
- Documents.
- Audit.
- Templates.
- Reports.
- Mailboxes.
- Notifications.
- Imports.
- Exports.

## Actions

Actions represent one business operation.

Examples:

- AssignVerificationAction.
- CompleteVerificationAction.
- PublishVerificationAction.
- GenerateInvoiceAction.
- UploadDocumentAction.
- SyncMailboxAction.
- RefreshVerificationTemplateAction.

Actions should be small and single-purpose.

## Policies

Policies are the primary authorization mechanism for business records.

Policies should integrate with Laravel authorization and Spatie permissions. UI visibility must not replace server-side authorization.

## Providers

Providers register framework, panel, shell, and platform behavior.

Examples:

- Laravel service providers.
- Filament panel providers.
- Work Context providers.
- AppShell registration.

## Models

Models represent business data and relationships.

Models should not become large containers for workflow rules, permission logic, query orchestration, or template behavior. Those responsibilities should be extracted over time into services, actions, traits, scopes, policies, or providers.

## Livewire

Livewire supports interactive workspace workflows.

Livewire components may coordinate state and user interaction, but business behavior should remain in services, actions, policies, or existing domain workflows.

## Filament

Filament remains the primary admin, resource, table, form, and panel framework.

The platform extends Filament. It does not fork Filament internals or replace resource behavior unless explicitly approved.

## Blade

Blade views render presentation surfaces. Blade should compose existing components and avoid business logic.

---

# 9. Security Architecture

Security is mandatory architecture.

## Authentication

Authentication is provided through Laravel and Filament panel access flows.

Workspaces must respect authenticated user identity and panel access.

## Authorization

Authorization is enforced through:

- Laravel Policies.
- Spatie permissions.
- Filament panel access.
- Workspace-specific access rules.

UI affordances may hide unavailable actions, but server-side authorization remains authoritative.

## Policies

Policies should cover business entities including patients, appointments, claims, verifications, billing, invoices, portal credentials, documents, organizations, clinics, locations, and users.

## Permissions

Spatie permissions provide granular access control. Permissions must remain consistent across UI, policies, services, controllers, and Livewire workflows.

## Tenant Isolation

Organization is the tenant boundary.

Clinic and location scope are nested inside organization scope. Cross-tenant access is prohibited unless an explicit SaaS Super Admin bypass exists and is audited.

## Public IDs

Business tables should support public UUID or ULID identifiers for external URLs while preserving numeric primary keys for internal relationships.

Public IDs protect URL surfaces and improve long-term API compatibility.

## HIPAA-Aware Design Goals

The platform should support HIPAA-aligned handling of PHI.

Architecture goals include:

- Sensitive data encryption.
- Secure document authorization.
- Access logs.
- Document download logs.
- Immutable audit trails.
- Retention policy support.
- Break-glass emergency access controls in future hardening phases.

## Auditability

Important access, workflow, document, notification, and status events should be auditable.

Audit logs must be tamper-resistant where feasible and should preserve tenant and user context.

---

# 10. Development Standards

## Coding Standards

Development should follow:

- PSR-12.
- Dependency injection.
- Typed properties and method signatures where practical.
- Small cohesive methods.
- Clear class ownership.
- PHPDoc where it improves understanding.

Avoid:

- Duplicated code.
- Static helper sprawl.
- Business logic in Blade.
- Business logic in controllers.
- Fat models.
- Unused imports and traits.
- Circular dependencies.

## Folder Organization

Enterprise folders should support clear architecture:

- Actions.
- Services.
- DTOs.
- Repositories.
- Policies.
- Observers.
- Events.
- Listeners.
- Jobs.
- Notifications.
- Enums.
- Traits.
- Domain.
- Support.

## Naming Conventions

Names should be explicit, stable, and domain-appropriate.

Use:

- `*Service` for reusable orchestration.
- `*Action` for a single business operation.
- `*Data` for DTOs.
- `*Policy` for authorization.
- `*Provider` for platform or workspace context supply.
- `Pds*` for documented design-system components.

## Testing Expectations

Testing should scale with risk.

Expected test categories:

- Feature tests.
- Unit tests.
- Authorization tests.
- Tenant isolation tests.
- Document authorization tests.
- HIPAA audit tests.
- Queue tests.
- Presentation tests for reusable UI engines.

## Documentation Expectations

Every architecture or platform engine phase should document:

- Overview.
- Files created.
- Files modified.
- Rationale.
- Compatibility.
- Risks.
- Validation.
- Future roadmap.

## Review Process

Before merging, review:

- Architecture alignment.
- Decision Log alignment.
- Reuse of existing services and components.
- Security and tenant behavior.
- Backward compatibility.
- Tests and documentation.
- No duplicated logic or UI.

## Engineering Work Orders

EWOs define approved incremental work.

Each EWO should:

- State objective and scope.
- List non-goals and protected areas.
- Preserve existing behavior unless explicitly approved.
- Validate with appropriate tests.
- Update architecture and component docs where needed.

---

# 11. Folder Structure

Recommended platform structure:

```text
app/
    Actions/
    DTOs/
    Domain/
    Enums/
    Events/
    Filament/
    Http/
    Jobs/
    Listeners/
    Models/
    Notifications/
    Observers/
    Policies/
    Providers/
    Repositories/
    Services/
    Support/
        AppShell/
        WorkContext/
    Traits/

database/
    factories/
    migrations/
    seeders/

docs/
    ARCHITECTURE_STANDARDS.md
    COMPONENT_INVENTORY.md
    DECISION_LOG.md
    LAYOUT_ARCHITECTURE.md
    PDS_GUIDE.md
    PLATFORM_ARCHITECTURE_V1.md
    PRODUCT_ENGINEERING_GUIDELINES.md
    UI_UX_DESIGN_SYSTEM.md
    WORK_CONTEXT_ENGINE.md

resources/
    views/
        components/
            pds/
        filament/
            appshell/

routes/

tests/
    Feature/
    Unit/
```

## Directory Responsibilities

`app/Actions`

Single-purpose business operations.

`app/Services`

Reusable application orchestration and domain services.

`app/DTOs`

Structured data transfer objects for large payloads.

`app/Repositories`

Query abstraction only where it provides clear value.

`app/Policies`

Authorization boundaries for business models.

`app/Support`

Platform support systems such as AppShell and Work Context Engine.

`app/Filament`

Filament resources, pages, and panel-specific presentation.

`app/Http`

Controllers, requests, and HTTP boundaries.

`app/Models`

Eloquent business entities and relationships.

`resources/views/components/pds`

Reusable ProDental Design System components.

`resources/views/filament/appshell`

Shared AppShell partials and styles.

`docs`

Architecture, governance, product, and phase documentation.

`tests`

Automated validation for application, architecture, authorization, and presentation behavior.

---

# 12. Product Philosophy

## Platform First

New capabilities should be built as platform capabilities when they are reusable across workspaces.

## Reuse Before Create

Always evaluate:

1. Reuse.
2. Extend.
3. Create.

## Presentation Does Not Own Business Logic

UI renders state and triggers authorized operations. It does not own business rules.

## Workspace First

The user works inside a workspace that matches their intent. Workspace boundaries protect clarity, security, and long-term product expansion.

## Enterprise Consistency

Every page should feel familiar. Shared AppShell, PDS, providers, and platform engines exist to prevent fragmented user experiences.

## AI Assists, Never Replaces

Future AI capabilities should support user productivity, summarize context, detect gaps, and suggest next steps. AI must not bypass authorization, policies, tenant boundaries, auditability, or human workflow ownership.

## Documentation Is Part Of The Deliverable

Architecture, component inventory, decision records, and phase reports are product assets.

---

# 13. Future Roadmap

The approved development sequence is:

1. Enterprise Foundation.
2. AppShell.
3. ProDental Design System.
4. Verification Workspace.
5. Focus Mode.
6. Organization Workspace.
7. Platform Workspace.
8. Claims.
9. PMS.

Future platform evolution includes:

- Organization Workspace maturation.
- SaaS Workspace maturation.
- Claims Workspace.
- PMS Workspace.
- Workspace Intelligence Layer.
- Assistive AI.
- Analytics and reporting expansion.
- API Platform.
- Mobile surfaces.
- Payment, document, PMS, payer, and mailbox integrations.
- Queue and event expansion for long-running tasks.
- HIPAA hardening and immutable audit architecture.

---

# 14. Architectural Decision Summary

## Public IDs

Business records should support public UUID or ULID identifiers for URLs while preserving numeric primary keys for relationships.

## AppShell

Every workspace uses one shared AppShell. Page-level shells are not permitted.

## ProDental Design System

Reusable UI belongs to PDS. Duplicate components are not permitted.

## Workspace Framework

Workspaces remain independent and share platform engines. Verification and PMS must never be merged.

## Work Context Engine

Context rendering is provider-driven and workspace-agnostic. The engine does not fetch business data directly.

## Focus Mode

Focus Mode is an AppShell and PDS capability. It is presentation-only and preserves existing workflows.

## Provider Pattern

Providers supply generic platform payloads from workspace-owned data.

## Presentation-Only Enhancements

UI modernization phases must not change business logic, workflows, permissions, policies, routes, database behavior, validation, or model relationships unless explicitly approved.

---

# 15. Engineering Checklist

Every future Engineering Work Order should confirm:

## Architecture

- [ ] Aligns with Platform Architecture V1.
- [ ] Aligns with Product Engineering Guidelines.
- [ ] Aligns with Architecture Standards.
- [ ] Aligns with the Decision Log.
- [ ] Preserves workspace boundaries.
- [ ] Uses AppShell where page structure is involved.
- [ ] Uses PDS for reusable UI.

## Reusability

- [ ] Reuses existing code before extending.
- [ ] Extends before creating new patterns.
- [ ] Avoids duplicated components.
- [ ] Avoids duplicated workflows.
- [ ] Registers new reusable components in Component Inventory.

## Performance

- [ ] Avoids duplicate queries.
- [ ] Avoids unnecessary rendering.
- [ ] Moves long-running work to queues when appropriate.
- [ ] Preserves Filament and Livewire framework behavior.

## Accessibility

- [ ] Supports keyboard navigation.
- [ ] Provides visible focus indicators.
- [ ] Uses semantic markup.
- [ ] Maintains readable contrast.
- [ ] Works on desktop, tablet, and mobile.

## Testing

- [ ] Adds tests proportional to risk.
- [ ] Runs focused tests.
- [ ] Runs route registration when routes or panels may be affected.
- [ ] Runs Blade compilation when views or components change.
- [ ] Documents full-suite baseline if failures pre-exist.

## Documentation

- [ ] Updates architecture docs when platform behavior changes.
- [ ] Updates PDS guide when reusable components are added.
- [ ] Updates Component Inventory when reusable components are added.
- [ ] Adds or updates phase reports for EWOs.

## Backward Compatibility

- [ ] Preserves existing routes.
- [ ] Preserves existing database behavior.
- [ ] Preserves policies and permissions.
- [ ] Preserves public ID behavior.
- [ ] Preserves tenant isolation.
- [ ] Preserves business workflows.
- [ ] Preserves existing user-facing functionality unless explicitly approved.

---

# 16. Version History

| Version | Date | Author | Summary |
| --- | --- | --- | --- |
| 1.0 | 2026-08-05 | Platform Architecture | Initial canonical enterprise architecture handbook for ProDental Platform. |

---

END OF DOCUMENT
