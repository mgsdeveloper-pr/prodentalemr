# ProDental Platform
## Product Engineering Guidelines
Version: 1.0
Status: Approved
Owner: Product Architecture

---

# 1. Vision

ProDental Platform is an Enterprise Dental SaaS Platform built for scalability,
maintainability, security and productivity.

The platform is not simply a Laravel application.

Laravel is the implementation framework.

The product architecture is the primary asset.

---

# 2. Product Philosophy

Enterprise Backend

Minimal Frontend

Maximum Productivity

Every implementation decision should support these three principles.

---

# 3. Core Principles

## Reuse Before Rewrite

Never rewrite existing functionality if it can be reused safely.

Priority:

Reuse

↓

Extend

↓

Create New

---

## Separation of Concerns

Business Logic

↓

Service Layer

↓

Presentation Layer

Presentation should never contain business logic.

---

## User Productivity

Every screen should reduce clicks.

Reduce navigation.

Reduce scrolling.

Reduce context switching.

---

## Consistency

Every page should feel familiar.

Users should never need to "learn" another page.

---

# 4. Application Architecture

Application

├── Platform Workspace

├── Verification Workspace

├── Organization Workspace

├── Claims Workspace

└── PMS Workspace

Every workspace shares:

AppShell

PDS

Authentication

Authorization

Theme

Navigation

---

# 5. Verification First

Verification is the flagship product.

Every UX improvement begins here.

Future modules inherit from Verification.

---

# 6. UI Philosophy

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

# 7. AppShell

Every workspace must use the same AppShell.

Header

Sidebar

Workspace Header

Status Bar

Toolbar

Content Container

No page creates its own shell.

---

# 8. Design System (PDS)

Every reusable component belongs to PDS.

Buttons

Forms

Cards

Tables

Drawers

Badges

Dialogs

Filters

Loaders

Empty States

Icons

Animations

No duplicate UI components.

---

# 9. Focus Mode

Focus Mode is a platform capability.

Purpose:

Hide distractions.

Maximize work area.

Improve productivity.

Focus Mode currently begins with Verification.

Future:

Claims

Template Builder

Clinical Notes

Portal Management

Document Review

---

# 10. Business Rules

Business Logic

↓

Actions

↓

Services

↓

Policies

↓

Repositories (Future)

↓

DTOs (Future)

↓

UI

The UI should never become the business layer.

---

# 11. Security

HIPAA compliant architecture.

Least privilege access.

Policies required.

Audit logging required.

Sensitive data encrypted.

Public IDs only in external URLs.

---

# 12. Multi-Tenancy

Organization is the tenant boundary.

Support:

Solo Practice

Multi-Location Practice

DSO

Group Practice

Specialty Practice

Community Clinics

Hospital Dentistry

Corporate Chains

Future organization types should require configuration, not redesign.

---

# 13. Development Rules

Never duplicate code.

Never bypass services.

Never bypass policies.

Never duplicate layouts.

Never duplicate components.

Never duplicate workflows.

---

# 14. Five-Year Rule

Before implementing anything ask:

Will this still make sense five years from now?

If not,

redesign it.

---

# 15. Definition of Done

Every feature must satisfy:

Architecture

Security

Permissions

Multi-tenancy

UI consistency

Accessibility

Responsive

Tests

Documentation

No duplicate code

---

# 16. Review Checklist

Before merging:

□ Uses AppShell

□ Uses PDS

□ Uses Services

□ Uses Policies

□ Uses Public IDs

□ Supports Organization scope

□ Uses existing workflows

□ No duplicated code

□ Responsive

□ Tested

□ Documented

---

# 17. Future Roadmap

Phase 1

Enterprise Foundation

Completed

Phase 2

Enterprise UI Foundation

Current

Phase 3

Verification Workspace

Phase 4

Organization Workspace

Phase 5

Platform Workspace

Phase 6

Claims

Phase 7

PMS

---

# 18. AI Development Standard

Every AI prompt must follow this document.

AI must:

Preserve architecture.

Reuse existing code.

Avoid duplication.

Protect business logic.

Follow PDS.

Follow AppShell.

Respect multi-tenancy.

Never redesign without approval.

---

END OF DOCUMENT
