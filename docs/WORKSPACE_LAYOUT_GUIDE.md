# Workspace Layout Guide

Version: 1.0
Status: Foundation Implemented
Owner: Product Architecture
Last Updated: 2026-08-06

---

## Standard Skeleton

Every workspace uses this skeleton:

1. Global Header
2. Workspace Header: `<x-pds.workspace-header>`.
3. Workspace Toolbar: `<x-pds.workspace-toolbar>`.
4. Workspace Body: `<x-pds.workspace-body>`.
5. Compact Footer: `<x-pds.workspace-footer>` or AppShell compact footer.

No additional dashboard wrapper, nested content container, duplicate page shell, or placeholder widget region should sit between these layers.

## Global Header Ownership

The global header is platform-level navigation. It should contain only brand, workspace switching, global search, and user utilities.

Workspace filters, workspace tabs, queue controls, report controls, and record actions belong in the workspace header, workspace toolbar, or center work area.

## Client Management Workspace

Client-related SaaS work should be organized through one Client Management workspace.

The workspace should expose:

- Client Registration by organization type.
- Verification Model comparison.
- Existing client structure management.
- Billing and subscription management links.
- Users and access management links.

Separate resource screens may remain available as detail surfaces, but the primary client workflow should not require users to discover unrelated sidebar sections.

## Columns

- Left context panel: `<x-pds.workspace-left-panel>`, 300px on wide screens.
- Center work area: `<x-pds.workspace-center>`, fluid and primary.
- Right awareness panel: `<x-pds.workspace-right-panel>`, 320px on wide screens.

Use `<x-pds.workspace-shell>` as the outer reusable composition surface. The workspace body collapses to one column on smaller screens.

## Left Column

Use for summary, quick reference, status, readiness, pinned information, and compact context.

Do not place large tables in the left column.

## Center Column

Use for queues, forms, configuration, reports, users, and documents.

Large tables are allowed only when they are the user's primary task.

In Verification Workspace, the verification queue is the primary task and remains the center of the product.

## Right Column

Use for timeline, notifications, quick actions, future AI, pinned notes, and recent activity.

Right column should be sticky on wide screens.

## Component Example

```blade
<x-pds.workspace-shell label="Verification Workspace">
    <x-pds.workspace-header
        eyebrow="Verification"
        title="Verification Workspace"
        description="Operational queue and work context."
    />

    <x-pds.workspace-toolbar>
        Toolbar actions, filters, and state legends.
    </x-pds.workspace-toolbar>

    <x-pds.workspace-body>
        <x-pds.workspace-left-panel>Context</x-pds.workspace-left-panel>
        <x-pds.workspace-center>Primary work</x-pds.workspace-center>
        <x-pds.workspace-right-panel>Awareness</x-pds.workspace-right-panel>
    </x-pds.workspace-body>
</x-pds.workspace-shell>
```

## Verification Workspace Standard

The Verification Workspace uses:

- Workspace header: AppShell-provided workspace and page context.
- Workspace toolbar: compact queue context and status legend.
- Workspace body: Filament Verification Queue as the primary work surface.
- Workspace footer: AppShell compact footer.

Future left and right rails may be added only when the data and workflow require them. Do not add decorative dashboard cards or empty awareness regions.

---

END OF DOCUMENT
