# ProDental Design System Guide

Version: 1.3
Status: Implemented
Owner: Product Engineering
Last Updated: 2026-08-06

---

# 1. Purpose

The ProDental Design System, or PDS, is the shared UI component foundation for every ProDental workspace.

PDS components are presentation-only building blocks. They must not contain business logic, authorization logic, tenant logic, workflow transitions, database access, or validation rules.

PDS now inherits from PWDL, the ProDental Workspace Design Language. New reusable components should use PWDL tokens and layout hooks before introducing new Tailwind-only styling.

---

# 2. Naming Convention

PDS uses one Laravel anonymous Blade component convention:

`<x-pds.component-name>`

Examples:

```blade
<x-pds.button>Save</x-pds.button>
<x-pds.card title="Patient Details">...</x-pds.card>
<x-pds.status-pill status="success">Complete</x-pds.status-pill>
```

Human-readable component names use the `Pds` prefix in documentation:

- `PdsButton`
- `PdsCard`
- `PdsBadge`
- `PdsStatusPill`
- `PdsTableToolbar`
- `PdsWorkspaceTitle`

---

# 3. Component Categories

## Layout

- `PdsPageContainer`: Page width and responsive padding.
- `PdsPageSection`: Section grouping with optional title and description.
- `PdsContentSection`: Simple bordered content surface.
- `PdsSplitLayout`: Two-column responsive layout.
- `PdsGrid`: Responsive grid layout.
- `PdsStack`: Vertical rhythm layout.
- `PdsWorkspaceShell`: Reusable outer workspace composition surface.
- `PdsWorkspaceHeader`: Workspace identity, title, description, and optional action slot.
- `PdsWorkspaceToolbar`: Reusable toolbar surface for actions, filters, and status legends.
- `PdsWorkspaceBody`: Responsive workspace body grid.
- `PdsWorkspaceLeftPanel`: 300px wide-screen context rail.
- `PdsWorkspaceCenter`: Fluid primary work area.
- `PdsWorkspaceRightPanel`: 320px wide-screen awareness rail.
- `PdsWorkspaceFooter`: Compact workspace-level footer surface.

## Buttons

- `PdsButton`: Primary, secondary, success, danger, ghost, and toolbar variants.
- `PdsIconButton`: Square accessible icon button with required label.

## Status

- `PdsBadge`: Compact metadata label.
- `PdsStatusPill`: Workflow or record status label.
- `PdsProgressIndicator`: Accessible progress bar.
- `PdsPriorityIndicator`: Small priority marker.

## Cards

- `PdsCard`: Standard card.
- `PdsSectionCard`: Section-oriented card.
- `PdsStatisticCard`: Metric display.
- `PdsInfoCard`: Informational callout.

## Forms

- `PdsFormSection`: Fieldset-based form group.
- `PdsFieldGroup`: Label, field, and helper wrapper.
- `PdsReadonlyDisplay`: Read-only value display.
- `PdsHelperText`: Supporting field text.
- `PdsValidationSummary`: Error summary.

## Tables

- `PdsTableToolbar`: Toolbar container for table actions.
- `PdsSearchHeader`: Search input surface.
- `PdsFilterBar`: Filter chip/control container.
- `PdsBulkActionBar`: Multi-selection action area.
- `PdsEmptyState`: Empty data state.
- `PdsLoadingState`: Inline table loading state.

## Navigation

- `PdsBreadcrumb`: Accessible breadcrumb list.
- `PdsWorkspaceTitle`: Workspace/page title group.
- `PdsSectionTitle`: Compact section title group.
- `PdsActionToolbar`: Reusable action grouping.

## Timeline

- `PdsTimeline`: Vertical activity/history container.
- `PdsTimelineItem`: Single event row with title, metadata, tone, and content slot.

## Focus Mode

- `PdsFocusModeTopbar`: Compact focused-work header with record identity, save state, and exit action slot.
- `PdsStickyActionBar`: Sticky workflow action surface for existing authorized actions.
- `PdsAutoSaveIndicator`: Compact save-state display for saved, saving, and unsaved states.

## Feedback

- `PdsAlert`: Status message block.
- `PdsToast`: Toast-style message surface.
- `PdsBanner`: Banner message surface.
- `PdsInlineMessage`: Inline status text.
- `PdsConfirmationDialog`: Confirmation modal foundation.

## Loading

- `PdsSkeletonLoader`: Placeholder lines.
- `PdsSpinner`: Compact spinner.
- `PdsPageLoader`: Page-level loading state.
- `PdsEmptyPlaceholder`: Structural placeholder.

## Containers

- `PdsDrawer`: Side drawer foundation.
- `PdsSlidePanel`: Slide panel alias.
- `PdsModal`: Modal foundation.
- `PdsSidePanel`: Inline side panel.
- `PdsWorkContextPanel`: Generic provider-driven context panel for active workspace tasks.
- `PdsContextCard`: Reusable context card with expanded, collapsed, loading, empty, error, disabled, pinned, and scrollable states.
- `PdsWorkspaceReadinessCard`: Reusable non-analytics checklist card for workspace readiness using already-computed state.

---

# 4. Usage Guidelines

Use PDS when a UI pattern is reusable across screens or workspaces.

Reuse Filament components when Filament already solves the interaction, especially for forms, tables, actions, notifications, modals, filters, and bulk actions.

Use PDS for shared composition surfaces, wrappers, status displays, layout rhythm, and non-business presentation.

Do not copy component markup into a page. Extend the component through props and slots.

Do not add business behavior to PDS. Pass already-authorized data and already-computed state into components.

`PdsWorkspaceReadinessCard` is presentation-only. Pages or providers must supply existing-state readiness items; the component must not fetch data or define business rules.

---

# 5. Composition Examples

```blade
<x-pds.page-container width="wide">
    <x-pds.stack>
        <x-pds.workspace-title
            title="Verification Workspace"
            description="Operational verification queue"
        />

        <x-pds.table-toolbar>
            <x-pds.search-header placeholder="Search requests" />
            <x-pds.action-toolbar>
                <x-pds.button variant="secondary">Export</x-pds.button>
                <x-pds.button>New Request</x-pds.button>
            </x-pds.action-toolbar>
        </x-pds.table-toolbar>
    </x-pds.stack>
</x-pds.page-container>
```

```blade
<x-pds.card title="Plan Status">
    <x-pds.status-pill status="success">Verified</x-pds.status-pill>
    <x-pds.progress-indicator value="75" label="Completion" />
</x-pds.card>
```

---

# 6. Workspace Layout Composition

Enterprise workspace pages should compose existing PDS components instead of creating large custom surfaces.

The canonical shell order is Global Header, Workspace Header, Workspace Toolbar, Workspace Body, and Compact Footer. Do not add duplicate content containers or dashboard wrapper grids around Filament page content.

Use `<x-pds.workspace-shell>` for reusable workspace composition. Inside it, prefer `<x-pds.workspace-header>`, `<x-pds.workspace-toolbar>`, `<x-pds.workspace-body>`, `<x-pds.workspace-left-panel>`, `<x-pds.workspace-center>`, `<x-pds.workspace-right-panel>`, and `<x-pds.workspace-footer>` instead of hand-built page wrappers.

For operations workspaces, prefer a three-region structure when context and actions are both important:

- Left context rail: `PdsWorkContextPanel` or compact context cards.
- Center workspace: summary metrics, readiness state, recent work, and task-oriented sections.
- Right rail: activity timeline, quick actions, recent changes, and future notification placeholders.

Avoid oversized empty tables and dashboard-style clutter when a compact card, timeline, empty state, or action list better supports the workflow.

The Organization Operations Workspace EWO-015-UI-V2 reconstruction is the reference implementation for this pattern. It uses AppShell, PDS-compatible cards, PDS statistics principles, PDS readiness principles, timeline presentation, heroicons, and existing Work Context data without changing routes, policies, models, validation, database behavior, or business workflows.

---

# 7. Accessibility Guidance

All interactive components must have visible focus states.

Icon-only controls must provide an accessible label.

Dialogs must provide an accessible title through `aria-label`.

Progress indicators must expose `aria-valuemin`, `aria-valuemax`, and `aria-valuenow`.

Empty, loading, and validation states must use readable text and must not rely only on color.

---

# 8. Best Practices

Keep components simple and composable.

Prefer props for stable variants and slots for content.

Prefer existing Filament controls for resource actions, forms, tables, notifications, and modal workflows.

Use PDS layout components to reduce duplicated Tailwind markup.

Use PWDL tokens for brand, surfaces, borders, text, status, spacing, radius, and shadows.

Avoid large local page-specific style blocks when a PDS component can express the same pattern.

For Verification Workspace, the Filament table remains the primary work component. PDS should frame the queue with compact context, toolbar, and status legend. Use `pwdl-workspace-toolbar` for tokenized toolbar presentation instead of workspace-specific dashboard classes.

---

# 9. Non-Goals

This PDS sprint does not redesign existing business pages.

This sprint does not migrate every screen onto PDS.

This sprint does not replace Filament forms, tables, actions, or notifications.

This sprint does not introduce JavaScript behavior for drawers, dialogs, or toasts. Those components are structural foundations for future Livewire or Filament integration.

---

END OF DOCUMENT
