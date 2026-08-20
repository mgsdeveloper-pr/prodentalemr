# ProDental Platform
## Product Engineering Guidelines

Version: 1.1
Status: Approved
Owner: Product Architecture
Last Updated: 2026-08-05

---

# 1. Vision

ProDental Platform is an Enterprise Dental SaaS Platform built for scalability, maintainability, security, and productivity.

The platform is not simply a Laravel application.

Laravel is the implementation framework.

The product architecture is the primary asset.

---

# 2. Governance Authority

The mandatory engineering policy is defined in [../PRODENTAL_ENGINEERING_CONSTITUTION.md](../PRODENTAL_ENGINEERING_CONSTITUTION.md). Every Engineering Work Order, Phoenix task, feature, refactor, bug fix, UI modernization, and documentation update must comply with it.

All architectural and product decisions must follow approved entries in [DECISION_LOG.md](DECISION_LOG.md).

The decision log is the permanent record of locked product architecture choices. Future developers and AI assistants must review it before proposing or implementing changes that affect workspaces, AppShell, design system, security, multi-tenancy, focus mode, verification workflows, or platform direction.

If a proposed change conflicts with an approved decision, the implementation must stop until a new decision entry is approved.

---

# 3. Product Philosophy

Enterprise Backend

Minimal Frontend

Maximum Productivity

Every implementation decision should support these three principles.

---

# 4. Core Principles

## Reuse Before Rewrite

Never rewrite existing functionality if it can be reused safely.

Priority:

1. Reuse
2. Extend
3. Create New

## Separation of Concerns

Business Logic

Actions

Services

Policies

Presentation Layer

Presentation should never contain business logic.

## User Productivity

Every screen should reduce clicks.

Reduce navigation.

Reduce scrolling.

Reduce context switching.

## Consistency

Every page should feel familiar.

Users should never need to learn another page.

## Client Management

Client registration and client administration should be centered in one Client Management workspace.

The workspace begins with two required product decisions:

1. Organization Type: Solo Practice, Multi Location, or DSO.
2. Verification Model: Self-Service, Managed Service, or Hybrid.

Existing resources such as Organizations, Clinics, Locations, Users, Client Enrollments, Subscriptions, Invoices, and Payments remain the detail-management surfaces, but the user entry point for client work should be one unified workspace.

---

# 5. Application Architecture

Application:

- Platform Workspace
- Verification Workspace
- Organization Workspace
- Claims Workspace (Future)
- PMS Workspace (Future)

Every workspace shares:

- AppShell
- PDS
- Authentication
- Authorization
- Theme
- Navigation standards

Verification and PMS must never be merged into a single workspace.

---

# 6. Verification First

Verification is the flagship product.

Every UX improvement begins here.

Every workflow enhancement begins here.

Every reusable workspace pattern is proven here first.

Future modules inherit from Verification.

---

# 7. UI Philosophy

Simple

Professional

Minimal

Fast

Accessible

Predictable

Avoid dashboard clutter.

Avoid unnecessary widgets.

Avoid excessive colors.

Whitespace is intentional.

---

# 8. AppShell

Every workspace must use the same AppShell.

The AppShell includes:

- Header
- Sidebar
- Workspace Header
- Status Bar
- Action Toolbar
- Content Container

No page creates its own shell.

Focus Mode may change AppShell visibility, but it must remain a shared platform capability rather than a custom page shell.

---

# 9. Design System (PDS)

Every reusable component belongs to PDS.

PDS includes:

- Buttons
- Forms
- Cards
- Tables
- Drawers
- Badges
- Dialogs
- Filters
- Loaders
- Empty States
- Icons
- Animations
- Focus Mode

No duplicate UI components are permitted.

---

# 10. Focus Mode

Focus Mode is a platform capability.

Purpose:

- Hide distractions.
- Maximize work area.
- Improve productivity.

Focus Mode currently begins with Verification.

Future:

- Claims
- Template Builder
- Clinical Notes
- Portal Management
- Document Review

Focus Mode must reuse existing business workflows and components.

---

# 11. Business Rules

Business Logic

Actions

Services

Policies

Repositories (Future)

DTOs (Future)

UI

The UI should never become the business layer.

---

# 12. Security

HIPAA compliant architecture.

Least privilege access.

Policies required.

Audit logging required.

Sensitive data encrypted.

Public IDs only in external URLs.

Security decisions must be reviewed against the approved decision log and architecture standards.

---

# 13. Multi-Tenancy

Organization is the tenant boundary.

Support:

- Solo Practice
- Multi-Location Practice
- DSO
- Group Practice
- Specialty Practice
- Community Clinics
- Hospital Dentistry
- Corporate Chains

Future organization types should require configuration, not redesign.

---

# 14. Development Rules

Never duplicate code.

Never bypass services.

Never bypass policies.

Never duplicate layouts.

Never duplicate components.

Never duplicate workflows.

Never contradict an approved decision-log entry.

---

# 15. Five-Year Rule

Before implementing anything ask:

Will this still make sense five years from now?

If not, redesign it before implementation.

---

# 16. Definition of Done

Every feature must satisfy:

- Architecture
- Security
- Permissions
- Multi-tenancy
- UI consistency
- Accessibility
- Responsive behavior
- Tests
- Documentation
- Decision-log alignment
- No duplicate code

---

# 17. Review Checklist

Before merging:

- [ ] Uses AppShell
- [ ] Uses PDS
- [ ] Uses Services
- [ ] Uses Policies
- [ ] Uses Public IDs
- [ ] Supports Organization scope
- [ ] Uses existing workflows
- [ ] Follows approved decision-log entries
- [ ] Contains no duplicated code
- [ ] Responsive
- [ ] Tested
- [ ] Documented

---

# 18. Future Roadmap

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

---

# 19. AI Development Standard

Every AI prompt must follow this document.

AI must:

- Preserve architecture.
- Reuse existing code.
- Avoid duplication.
- Protect business logic.
- Follow PDS.
- Follow AppShell.
- Respect multi-tenancy.
- Follow approved entries in [DECISION_LOG.md](DECISION_LOG.md).
- Never redesign without approval.

---

# Version History

| Version | Date | Owner | Notes |
| --- | --- | --- | --- |
| 1.0 | 2026-08-05 | Product Architecture | Initial product engineering guidelines approved. |
| 1.1 | 2026-08-05 | Product Architecture | Added governance authority, decision-log reference, Focus Mode as PDS capability, and updated roadmap sequence. |
