# ProDental Platform UI/UX Design System

Version: 1.0
Status: Approved
Owner: Product Architecture
Last Updated: 2026-08-05

---

# 1. Purpose

The ProDental Design System (PDS) defines the reusable UI, layout, and interaction standards for ProDental Platform.

PDS exists to make every workspace feel consistent, professional, fast, accessible, and predictable.

---

# 2. UI Philosophy

Simple

Professional

Minimal

Fast

Accessible

Predictable

Whitespace is intentional.

Dashboard clutter is avoided.

Unnecessary widgets are avoided.

Excessive colors are avoided.

---

# 3. PDS Component Standard

Every reusable UI component belongs to PDS.

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

# 4. AppShell Relationship

PDS components must operate inside the shared AppShell.

The AppShell includes:

- Header
- Sidebar
- Workspace Header
- Status Bar
- Action Toolbar
- Content Container

No page may introduce a custom shell or one-off workspace layout.

---

# 5. Focus Mode

Focus Mode is an official Design System component and platform capability.

## Purpose

Focus Mode exists to:

- Hide distractions.
- Maximize usable workspace.
- Improve productivity during high-concentration tasks.
- Preserve existing business workflows while improving work surface clarity.

## Behavior

Focus Mode adjusts layout visibility only.

It must not:

- Change business logic.
- Duplicate forms.
- Duplicate Quick Reference.
- Duplicate save, audit, request-to-clinic, or template-refresh workflow logic.
- Bypass policies.
- Bypass tenant rules.

It must:

- Reuse existing workflow components.
- Preserve existing permissions.
- Preserve existing autosave and action behavior.
- Provide a clear exit path.

## Layout

Initial Verification Form Focus Mode displays:

- Compact Header
- Quick Reference (existing component)
- Verification Form
- Sticky Action Bar
- Auto Save Status
- Exit Focus Mode

Initial Verification Form Focus Mode hides:

- Sidebar
- Workspace Header
- Status Bar
- Dashboard widgets
- Filters
- Navigation elements

## Visibility Rules

Focus Mode visibility is task-specific.

Controls required to complete the active workflow must remain visible.

Controls unrelated to the active workflow should be hidden.

Critical status, save state, validation errors, and exit controls must remain available.

Authorization and tenant visibility rules remain unchanged.

## Future Expansion

Focus Mode may expand to:

- Claims
- Template Builder
- Document Review
- Clinical Notes
- Portal Credential Management

Future Focus Mode implementations must reuse the same PDS and AppShell capability.

---

# 6. Verification-First UX Standard

Verification is the flagship product.

New productivity, layout, focus, and workflow UI patterns should be proven in Verification first.

Future modules should inherit proven patterns from Verification instead of inventing separate experiences.

---

# 7. UX Review Checklist

Before approving UI work:

- [ ] Uses AppShell
- [ ] Uses PDS components
- [ ] Avoids duplicate UI components
- [ ] Preserves workspace separation
- [ ] Reduces clicks
- [ ] Reduces scrolling
- [ ] Reduces context switching
- [ ] Preserves permissions
- [ ] Preserves tenant visibility
- [ ] Supports responsive behavior
- [ ] Supports accessibility
- [ ] Aligns with [DECISION_LOG.md](DECISION_LOG.md)

---

# Version History

| Version | Date | Owner | Notes |
| --- | --- | --- | --- |
| 1.0 | 2026-08-05 | Product Architecture | Initial approved PDS standards, including Focus Mode as an official design-system component. |
