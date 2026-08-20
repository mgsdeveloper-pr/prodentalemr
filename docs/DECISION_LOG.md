# ProDental Platform Decision Log

Version: 1.0
Status: Approved
Owner: Product Architecture
Last Updated: 2026-08-05

---

## Purpose

This document is the permanent decision record for ProDental Platform.

All major product, architecture, workspace, security, and UI decisions must be recorded here before they become platform standards. Future developers and AI assistants must treat approved entries as binding unless a later approved decision explicitly supersedes them.

---

## Governance Rules

1. Approved decisions are platform standards.
2. No implementation may contradict an approved decision.
3. Proposed changes must be reviewed against product vision, enterprise architecture, multi-tenant design, security, reusability, maintainability, and user productivity.
4. Prior decisions may only be changed by adding a new decision entry with an explicit supersedes reference.
5. Documentation, prompts, implementation plans, and code reviews must reference this log when architectural direction is relevant.

---

## Decision 001: Verification is the Primary Product

Status: APPROVED

Decision:

Verification is the flagship product of ProDental Platform.

Every UX improvement, workflow enhancement, and architectural refinement will be implemented in the Verification Workspace first.

Future modules should inherit proven patterns from Verification.

Rationale:

Verification is the most mature and workflow-rich area of the platform. It provides the best proving ground for productivity, AppShell, service-layer, policy, and focus-mode patterns.

Implications:

- Verification receives product-pattern priority.
- Claims, PMS, and future workspaces should reuse Verification patterns where appropriate.
- New shared platform capabilities should be validated against Verification workflows first.

---

## Decision 002: Workspace Separation

Status: APPROVED

Decision:

The application is divided into independent workspaces:

- Platform Workspace
- Verification Workspace
- Organization Workspace
- Claims Workspace (Future)
- PMS Workspace (Future)

Each workspace has its own navigation and responsibilities while sharing the same AppShell and Design System.

Verification and PMS must NEVER be merged into a single workspace.

Rationale:

Each workspace supports a different user intent and operational model. Merging them would weaken navigation clarity, permissions, tenant controls, and productivity.

Implications:

- Shared systems belong in platform infrastructure, not duplicated workspace implementations.
- Workspace-specific navigation must remain separate.
- Verification operational work and PMS clinical work remain distinct.

---

## Decision 003: Verification Panel Independence

Status: APPROVED

Decision:

Verification remains an independent workspace.

Verification users perform operational work.

Platform users manage the SaaS business.

Organization users manage their organization.

PMS users manage clinical operations (Future).

Each workspace serves a different business function.

Rationale:

Clear workspace ownership protects user productivity, permissions, auditability, and long-term platform expansion.

Implications:

- Platform users should not perform routine verification operations from the Platform Workspace.
- Verification users should have a focused operational environment.
- Organization and PMS experiences must be designed for their own business functions.

---

## Decision 004: Focus Mode

Status: APPROVED

Decision:

Focus Mode is an application capability.

Purpose:

- Hide distractions.
- Maximize usable workspace.
- Improve productivity during high-concentration tasks.

Initial implementation:

- Verification Form

Future implementation:

- Claims
- Template Builder
- Document Review
- Clinical Notes
- Portal Credential Management

Rationale:

High-concentration workflows benefit from reduced navigation, reduced clutter, and persistent action access.

Implications:

- Focus Mode must be reusable as a platform capability.
- Focus Mode must not create duplicate business logic.
- Focus Mode must preserve existing forms, quick references, permissions, and workflow behavior.

---

## Decision 005: Enterprise AppShell

Status: APPROVED

Decision:

Every workspace must use one reusable AppShell.

The AppShell includes:

- Header
- Sidebar
- Workspace Header
- Status Bar
- Action Toolbar
- Content Container

No page may introduce a custom application shell.

Rationale:

A single AppShell creates consistency, reduces duplicated layout code, and makes future workspace expansion safer.

Implications:

- Page-level shells are not permitted.
- Workspace-specific variation must be configured inside the shared AppShell.
- Focus Mode may adjust visibility but must remain a first-class AppShell behavior.

---

## Decision 006: ProDental Design System (PDS)

Status: APPROVED

Decision:

Every reusable UI component belongs to PDS.

PDS covers:

- Buttons
- Cards
- Tables
- Forms
- Badges
- Drawers
- Dialogs
- Filters
- Loaders
- Empty States
- Animations
- Icons

No duplicate UI components are permitted.

Rationale:

Reusable UI components improve speed, consistency, accessibility, maintainability, and long-term product quality.

Implications:

- New UI patterns must be added to PDS before repeated use.
- Screens should assemble approved components rather than create local one-off controls.
- Product consistency is a platform requirement, not polish.

---

## Decision 007: Enterprise Backend + Minimal Frontend

Status: APPROVED

Decision:

The platform philosophy is:

- Enterprise Backend
- Minimal Frontend
- Maximum Productivity

Every future feature must support this philosophy.

Rationale:

ProDental Platform is a workflow product. Its competitive advantage comes from secure, maintainable backend capability and fast, predictable user workflows rather than decorative interface complexity.

Implications:

- Backend architecture must be durable, tested, and reusable.
- Frontend design must remain simple, fast, accessible, and predictable.
- UI should reduce work rather than create visual noise.

---

## Decision 008: Reuse Before Rewrite

Status: APPROVED

Decision:

Before creating anything new, developers must follow this order:

1. Reuse existing implementation.
2. Extend existing implementation.
3. Create new implementation only when necessary.

Business logic must never be duplicated.

Rationale:

The platform already contains functional modules. Enterprise readiness is achieved by strengthening architecture incrementally, not by replacing working systems.

Implications:

- Existing services, actions, policies, components, and workflows must be reviewed before new code is introduced.
- Rewrites require explicit approval.
- Duplication is treated as architectural debt.

---

## Decision 009: Verification Form Focus Workspace

Status: APPROVED

Decision:

When a user starts or edits a Verification, the application should support Focus Mode.

Focus Mode hides:

- Sidebar
- Workspace Header
- Status Bar
- Dashboard widgets
- Filters
- Navigation elements

Focus Mode displays:

- Compact Header
- Quick Reference (existing component)
- Verification Form
- Sticky Action Bar
- Auto Save Status
- Exit Focus Mode

Focus Mode must reuse the existing Verification Form and existing Quick Reference component.

No business logic changes are allowed.

Rationale:

Verification form completion is a high-concentration workflow. Focus Mode should improve productivity without creating a parallel verification implementation.

Implications:

- Focus Mode is a presentation capability only.
- Existing verification save, audit, request-to-clinic, template refresh, and workflow rules must remain unchanged.
- Quick Reference and Verification Form must be reused.

---

## Decision 010: Future Product Roadmap

Status: APPROVED

Decision:

Development order is fixed:

1. Enterprise Foundation (Completed)
2. AppShell
3. ProDental Design System
4. Verification Workspace
5. Focus Mode
6. Organization Workspace
7. Platform Workspace
8. Claims
9. PMS

No phase should redesign previous architecture unless formally approved.

Rationale:

A fixed sequence protects architectural stability and prevents premature redesign of future modules before the shared platform foundation is proven.

Implications:

- Future work should follow the approved sequence.
- Architectural changes must reference the decision log.
- Earlier phases should be refined only through approved incremental changes.

---

## Version History

## Decision 011: Organization Operations Workspace Product Boundary

Status: APPROVED

Decision:

The Organization Operations Workspace is a verification operations workspace, not a PMS workspace.

It may compose existing organization, clinic, user, verification request, portal credential, verification document, notification, activity, template, and report capabilities.

It must not introduce appointment scheduling, patient registration, patient charting, clinical notes, treatment plans, clinical procedures, recall scheduling, imaging, claims workflow, new persistence, or duplicate CRUD.

Rationale:

The current product boundary is Enterprise Insurance Verification. Organization operations should prepare administrators for successful verification without expanding into future PMS or claims modules.

Implications:

- Workspace readiness is presentation-only and evaluates existing state.
- Work Context remains provider-driven.
- Existing verification workflow and Focus Mode remain unchanged.
- Future PMS and claims integrations must be documented as future boundaries.

---

## Decision 012: Project Phoenix PWDL Foundation

Status: APPROVED

Decision:

PWDL is the official visual foundation for ProDental Platform workspaces.

All future workspace presentation should use PWDL design tokens, the AppShell global header rule, the standard workspace skeleton, and PDS/AppShell components before local one-off UI.

The global header contains only logo, workspace switcher, global search, notifications, help, and user profile.

Rationale:

A token-led workspace design language prevents fragmented UI and allows future brand changes without rewriting page markup.

Implications:

- Presentation may be modernized without changing business behavior.
- New reusable UI belongs in PDS or AppShell.
- Brand color changes should begin in design tokens.
- Workspace-specific context must live inside the workspace, not the global header.

---

| Version | Date | Owner | Notes |
| --- | --- | --- | --- |
| 1.2 | 2026-08-06 | Product Architecture | Added Decision 012 for Project Phoenix PWDL Foundation. |
| 1.0 | 2026-08-05 | Product Architecture | Initial approved decision log with Decisions 001-010. |
