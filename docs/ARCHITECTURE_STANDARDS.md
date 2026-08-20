# ProDental Platform Architecture Standards

Version: 1.0
Status: Approved
Owner: Product Architecture
Last Updated: 2026-08-05

---

# 1. Purpose

This document defines the mandatory architecture standards for ProDental Platform.

It works with:

- [../PRODENTAL_ENGINEERING_CONSTITUTION.md](../PRODENTAL_ENGINEERING_CONSTITUTION.md)
- [PRODUCT_ENGINEERING_GUIDELINES.md](PRODUCT_ENGINEERING_GUIDELINES.md)
- [DECISION_LOG.md](DECISION_LOG.md)
- [UI_UX_DESIGN_SYSTEM.md](UI_UX_DESIGN_SYSTEM.md)

---

# 2. Architecture Philosophy

ProDental Platform follows:

- Enterprise Backend
- Minimal Frontend
- Maximum Productivity

Laravel is the implementation framework. The product architecture is the primary asset.

---

# 3. Workspace Architecture

The platform is organized into independent workspaces:

- Platform Workspace
- Verification Workspace
- Organization Workspace
- Claims Workspace (Future)
- PMS Workspace (Future)

Each workspace has its own navigation, permissions, responsibilities, and user intent.

All workspaces share:

- AppShell
- ProDental Design System
- Authentication
- Authorization
- Multi-tenant standards
- Security standards

Verification and PMS must remain separate workspaces.

---

# 4. Layering Standard

Business behavior must be organized through clear layers:

1. Actions
2. Services
3. Policies
4. Repositories (Future)
5. DTOs (Future)
6. UI

Controllers, Filament pages, Livewire components, and Blade views should not contain business logic.

---

# 5. Reuse Standard

Before creating anything new:

1. Reuse existing implementation.
2. Extend existing implementation.
3. Create new implementation only when necessary.

Rewrites require explicit approval.

Business logic, layouts, UI components, workflows, permissions, and tenant rules must not be duplicated.

---

# 6. Multi-Tenant Standard

Organization is the tenant boundary.

Every business model, workflow, service, policy, report, document, import, export, and notification must respect organization scope.

Clinic scope is nested inside organization scope.

Cross-tenant access is prohibited unless an approved super-admin bypass exists and is explicitly audited.

---

# 7. Security Standard

Security is mandatory architecture, not optional hardening.

Required standards:

- Least privilege access
- Laravel Policies
- Spatie Permission integration
- Public IDs for external URLs
- Sensitive data encryption
- Audit logging
- Secure document access
- Tenant isolation
- HIPAA-aligned handling of PHI

Security behavior must not be bypassed in UI, controller, service, import, export, or document code.

---

# 8. AppShell Standard

Every workspace must use the shared AppShell.

The AppShell includes:

- Header
- Sidebar
- Workspace Header
- Status Bar
- Action Toolbar
- Content Container

No page may introduce a custom application shell.

Focus Mode is an AppShell behavior, not a separate shell.

---

# 9. Verification-First Standard

Verification is the flagship product and proving ground for shared platform patterns.

New reusable workflow, productivity, AppShell, service-layer, and UI patterns should be validated in Verification first before expanding to Claims, PMS, or other modules.

---

# 10. Governance

Architecture changes require review against:

- Product Vision
- Enterprise Architecture
- Multi-Tenant Design
- Security
- Reusability
- Maintainability

Any architecture decision that changes workspace boundaries, shared platform standards, security posture, tenant behavior, AppShell, PDS, Focus Mode, or product roadmap must be recorded in [DECISION_LOG.md](DECISION_LOG.md).

Approved decision-log entries are binding. If a proposed implementation conflicts with an approved decision, implementation must stop until the conflict is resolved through a new approved decision.

---

# 11. Review Checklist

Before implementation:

- [ ] Aligns with Product Vision
- [ ] Aligns with approved decision-log entries
- [ ] Preserves workspace separation
- [ ] Preserves Verification as flagship product
- [ ] Uses AppShell
- [ ] Uses PDS
- [ ] Uses policies
- [ ] Uses services/actions where business logic exists
- [ ] Preserves public IDs
- [ ] Preserves tenant isolation
- [ ] Avoids duplicated logic
- [ ] Avoids duplicated UI components
- [ ] Requires no redesign of approved architecture unless formally approved

---

# Version History

| Version | Date | Owner | Notes |
| --- | --- | --- | --- |
| 1.0 | 2026-08-05 | Product Architecture | Initial approved architecture standards and governance model. |
