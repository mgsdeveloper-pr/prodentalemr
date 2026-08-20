# Workspace Component Library

Version: 1.0
Status: Foundation Implemented
Owner: Product Engineering
Last Updated: 2026-08-06

---

## Core Components

- AppShell Global Header
- Workspace Switcher
- Workspace Header
- Action Toolbar
- Content Container
- Compact Footer
- PDS Card
- PDS Button
- PDS Badge
- PDS Status Pill
- PDS Empty State
- PDS Timeline
- PDS Work Context Panel
- PDS Workspace Readiness Card
- PDS Workspace Shell
- PDS Workspace Header
- PDS Workspace Toolbar
- PDS Workspace Body
- PDS Workspace Left Panel
- PDS Workspace Center
- PDS Workspace Right Panel
- PDS Workspace Footer

## PWDL Utility Classes

- `pwdl-workspace`
- `pwdl-card`
- `pwdl-empty-state`
- `pwdl-three-column`

## Workspace Shell Components

- `<x-pds.workspace-shell>`: outer reusable workspace composition surface.
- `<x-pds.workspace-header>`: workspace identity, title, description, and optional actions.
- `<x-pds.workspace-toolbar>`: compact actions, filters, and status legends.
- `<x-pds.workspace-body>`: responsive workspace body grid.
- `<x-pds.workspace-left-panel>`: context rail for summary, quick reference, and readiness.
- `<x-pds.workspace-center>`: primary work area for queues, forms, tables, reports, and configuration.
- `<x-pds.workspace-right-panel>`: awareness rail for activity, quick actions, notes, and future AI.
- `<x-pds.workspace-footer>`: compact workspace-level footer when AppShell footer is not sufficient.

## Governance

Reusable UI belongs in PDS or AppShell. Workspace pages may compose, but should not duplicate, shared patterns.

---

END OF DOCUMENT
