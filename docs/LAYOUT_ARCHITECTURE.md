# ProDental Platform Layout Architecture

Version: 1.0
Status: Implemented
Owner: Product Engineering
Last Updated: 2026-08-05

---

# 1. Purpose

This document defines the Enterprise AppShell layout foundation for ProDental Platform.

The AppShell is a shared workspace frame layered onto Filament panels. It standardizes layout regions without replacing Filament, changing business behavior, or redesigning existing pages.

---

# 2. Architecture Principle

Filament remains the primary application layout engine.

The AppShell extends Filament through panel render hooks and shared Blade partials:

- No vendor layout fork.
- No resource rewrite.
- No route change.
- No workflow change.
- No form or table replacement.

---

# 3. Shared Regions

| Region | Responsibility | Implementation |
| --- | --- | --- |
| Global Header | Workspace identity, authorized workspace switching, and global utility placeholders. | `filament.appshell.global-header` |
| Collapsible Sidebar | Existing Filament navigation with enterprise width, active state, and compact styling. | Filament sidebar plus AppShell CSS |
| Workspace Header | Page context extension and compact workspace status. | `filament.appshell.workspace-header` |
| Status Region | Compact status pills for page or workspace state. | `filament.appshell.status-bar` |
| Action Toolbar | Reserved shared toolbar rail for future search, filters, export, bulk actions, and primary actions. | `filament.appshell.action-toolbar` |
| Compact Footer | Version, environment, build, tenant, workspace, year, and copyright metadata. | `filament.appshell.compact-footer` |

The active shell order is Global Header, Workspace Header, Action Toolbar, Filament page content, and Compact Footer. AppShell must not add extra content-wrapper partials around the Filament page body.

---

# 4. Workspace Coverage

The AppShell foundation is registered for these current workspaces:

- Platform Workspace (`saas`)
- Verification Workspace (`admin`, path `/verification`)
- Organization Workspace (`clinic`)
- DSO / Organization Workspace (`dso`)

Future workspaces such as Claims and PMS should register the same AppShell instead of creating their own shell.

---

# 5. Access Model

Workspace switching must use the existing Filament panel access model.

The shell may display only workspaces the authenticated user can access through the existing `canAccessPanel` flow. This preserves Spatie permissions, policies, tenant access, and current panel authorization behavior.

---

# 6. Responsive Behavior

Desktop:

- Sidebar uses a 260px expanded target.
- Collapsed sidebar uses a 72px target.
- Header utilities and workspace switching are visible when space allows.
- Footer remains compact at 24-28px.

Mobile:

- Workspace links and utility labels collapse to preserve usable content space.
- Existing Filament responsive navigation behavior remains authoritative.
- Header and footer avoid wrapping into tall shell chrome.

---

# 7. Accessibility

The AppShell foundation provides:

- Semantic region labels.
- Visible keyboard focus state for workspace links.
- Text-based fallbacks for future global utilities.
- Compact status pills with readable labels.
- No keyboard trap and no JavaScript-only interaction requirement.

---

# 8. Extension Points

Future phases may attach richer functionality to existing shell regions:

- Global search
- Quick create
- Notification center
- Help launcher
- Focus Mode
- AI assistant drawer
- Context panel
- Timeline drawer
- Workspace-specific status indicators
- User preferences

These extensions must reuse the existing AppShell regions instead of introducing duplicate page shells.

---

# 9. Non-Goals

This phase does not:

- Redesign existing pages.
- Replace Filament resources.
- Change Livewire components.
- Change forms, tables, routes, permissions, policies, services, models, database schema, validation, or tenant behavior.
- Implement Focus Mode, AI drawers, or a new design system component library.

---

# 10. Validation Standard

Every AppShell change must validate:

- PHP syntax
- Blade compilation
- Route registration
- AppShell render behavior
- Current workflow preservation
- Full test suite baseline

---

END OF DOCUMENT
