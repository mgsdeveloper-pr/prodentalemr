# ProDental Workspace Design Language

Version: 1.0
Status: Foundation Implemented
Owner: Product Architecture
Last Updated: 2026-08-06

---

## Vision

PWDL is the visual foundation for ProDental Platform workspaces. It standardizes how workspaces look, feel, and compose without changing business workflows.

The target product feeling is modern enterprise SaaS: calm, compact, premium, and task-focused.

## Principles

- Workspace first, panel second.
- Context belongs on the left.
- Primary work belongs in the center.
- Awareness belongs on the right.
- Cards have one purpose and natural height.
- Empty states are compact and actionable.
- Tables appear only when tabular work is the primary task.
- Brand changes flow through design tokens.
- Color usage follows 90% neutral, 8% brand teal, and 2% semantic color.

## Color System

Brand:

- Teal for identity, active state, primary action emphasis, and focus.

Neutral:

- White for primary surfaces.
- Soft gray for application background and subtle fills.
- Medium gray for secondary text and borders.
- Dark gray for primary text and high-emphasis controls.

Semantic:

- Green means success.
- Orange means warning.
- Red means error.
- Blue means information.

Semantic color must never be decorative. Large layout areas should stay neutral.

## Global Header Standard

The global header belongs to the platform, not to an individual workspace.

It has exactly four zones:

1. Brand: PD logo and `ProDental`.
2. Workspace switcher: one dropdown for Verification, Organization, Reports, Revenue, Administration, and Future AI.
3. Global search: one centered global search surface.
4. User utilities: notifications, help, avatar, and user dropdown.

Do not place workspace-specific controls in the global header. Do not duplicate product branding. Inside the AppShell header, the product name is `ProDental`; footer, browser title, and documentation may continue to use `ProDental EMR`.

## Foundation

PWDL introduces reusable tokens for brand, surfaces, borders, text, status, radius, spacing, shadows, typography, and workspace layout columns.

Primary implementation surfaces:

- `resources/css/pwdl.css`
- `resources/views/filament/appshell/styles.blade.php`
- PDS components under `resources/views/components/pds`
- PDS Workspace Shell components for reusable workspace composition
- Organization Operations Workspace as the first PWDL reference workspace
- Verification Workspace as the flagship work surface for operational queue design

## Workspace Shell Framework

PWDL workspace screens should compose the reusable PDS shell components in this order:

1. `<x-pds.workspace-shell>`
2. `<x-pds.workspace-header>`
3. `<x-pds.workspace-toolbar>`
4. `<x-pds.workspace-body>`
5. `<x-pds.workspace-left-panel>`
6. `<x-pds.workspace-center>`
7. `<x-pds.workspace-right-panel>`
8. `<x-pds.workspace-footer>`

The body uses a single column by default and expands on wide screens to:

- Left context panel: `300px`.
- Center work area: fluid.
- Right awareness panel: `320px`.

Shell components are presentation-only. They must receive already-authorized, already-computed content from Filament pages, Livewire components, providers, or view models.

## Flagship Workspace

Verification Workspace is the product reference for PWDL. It must answer, within five seconds:

- Where am I?
- What work requires attention?
- What should I do next?
- What changed?

The queue remains the centerpiece. Context supports the queue; awareness explains movement around the queue.

## Presentation Layer Rule

Workspace presentation must remain a clean shell:

1. Global Header
2. Workspace Header
3. Workspace Toolbar
4. Workspace Body
5. Compact Footer

Do not add dashboard-first wrapper grids, duplicate content containers, placeholder widget regions, or workspace-specific dashboard CSS when a PDS component or PWDL utility already exists.

Reusable workspace presentation classes belong in `resources/css/pwdl.css`. Filament inline shell styles may expose the same tokenized class only when needed for panel-loaded assets.

## Non-Goals

PWDL does not change services, actions, policies, models, database schema, authorization, routes, validation, or business workflows.

---

END OF DOCUMENT
