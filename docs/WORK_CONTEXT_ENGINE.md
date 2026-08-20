# Enterprise Work Context Engine

## Architecture

The Enterprise Work Context Engine is a platform rendering layer.

Flow:

1. Enterprise AppShell
2. Workspace
3. Context Provider
4. Work Context Engine
5. Context Cards
6. Workspace Content

The engine does not own business logic, authorization rules, validation, workflow transitions, database queries, or tenant rules.

## Provider Pattern

Every workspace supplies context through `App\Support\WorkContext\ContextProviderInterface`.

Implemented provider:

- `App\Support\WorkContext\Providers\VerificationContextProvider`
- `App\Support\WorkContext\Providers\OrganizationContextProvider`

Future documented providers:

- `ClaimsContextProvider`
- `PmsContextProvider`
- `BillingContextProvider`
- `SaasContextProvider`

Providers convert already-available workspace data into generic `WorkContext` and `ContextCard` objects.

## Context Engine

The reusable engine is rendered through:

- `resources/views/components/pds/work-context-panel.blade.php`
- `resources/views/components/pds/context-card.blade.php`

The panel accepts a `WorkContext` object and renders all cards without knowing which workspace produced the context.

## Context Cards

Each card supports:

- Expanded
- Collapsed
- Loading
- Empty
- Error
- Disabled
- Pinned
- Scrollable
- Optional badge
- Optional actions
- Optional footer

Cards are provider-supplied and individually reusable.

## Verification Provider

The Verification provider supplies:

- Quick Reference
- Verification Summary
- Patient Summary
- Insurance Summary
- Assigned User
- Due Date
- Priority
- Internal Notes
- Attachments
- Timeline
- Verification Metadata
- AI Assistant reserved slot

It reuses existing page-provided data and does not add database fields, relationships, workflow behavior, policies, routes, or permissions.

## Organization Provider

The Organization provider supplies:

- Organization Summary
- Clinics
- Users
- Verification Configuration
- Recent Activity
- Documents
- Workspace Readiness
- Future Workspace Intelligence reserved slot

It receives workspace-owned data from the Clinic dashboard and does not add database fields, relationships, workflow behavior, policies, routes, or permissions.

## Extensibility

Future workspaces should implement `ContextProviderInterface`, map their already-authorized workspace context to cards, and render the same PDS panel.

No provider should bypass policies, tenant boundaries, services, or existing workflow ownership.

## Future AI Integration

The engine reserves an AI Assistant card. No AI logic is implemented in EWO-011.

Future AI capabilities may include:

- Missing information detection
- Duplicate attachment detection
- Previous verification lookup
- Suggested next action
- Timeline summary
- Verification completeness
- Document summary

## Future Search Integration

The `WorkContext` object reserves a search configuration slot. No search behavior is implemented in EWO-011.

Future Context Search should operate over provider-supplied context only.

## Workspace Integration

Initial integration:

- Verification edit page
- Clinic dashboard Organization Workspace

Future integrations:

- SaaS Administration workspace
- Claims workspace
- PMS workspace
- Billing workspace

---

END OF DOCUMENT
