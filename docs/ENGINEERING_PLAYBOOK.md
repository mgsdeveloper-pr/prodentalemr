# ProDental Engineering Playbook

## Purpose

This playbook defines implementation behavior for enterprise work orders.

All work must comply with the mandatory [ProDental Engineering Constitution](../PRODENTAL_ENGINEERING_CONSTITUTION.md).

## Rules

- Reuse existing code before creating new code.
- Preserve Verification workflow, Focus Mode, Work Context Engine, AppShell, PDS, policies, permissions, public IDs, and tenant architecture.
- Keep presentation separate from business behavior.
- Do not add persistence unless explicitly required.
- Do not duplicate Filament resources or CRUD flows.
- Validate each phase only with safe commands. Before running `php artisan test`, verify that tests use an isolated testing database.
- Never run or recommend destructive database commands such as `php artisan migrate:fresh`, `php artisan db:wipe`, or `php artisan db:reset`.

## Organization Operations Workspace Standard

The Organization Operations Workspace must remain inside the insurance verification product boundary.

Allowed integrations:

- Organization profile
- Clinics
- Users
- Verification requests
- Verification settings
- Portal credentials
- Verification documents
- Verification notifications
- Verification reports
- Existing activity records

Excluded integrations:

- PMS workflows
- Claims workflow
- Appointment scheduling
- Patient registration
- Clinical charting
- Treatment planning
- Clinical imaging

## Documentation

Each work order must update or explicitly mark authoritative documents as requiring no update.

## PWDL Presentation Standard

Presentation work must follow the ProDental Workspace Design Language.

Required defaults:

- Use PWDL design tokens for brand, surfaces, borders, text, status, radius, spacing, shadows, and workspace layout.
- Use AppShell for global header, workspace frame, and compact footer.
- Use PDS before creating local reusable UI.
- Keep workspace-specific context inside the workspace, not the global header.
- Preserve business logic, services, actions, policies, models, routes, database behavior, authorization, and validation.

---

END OF DOCUMENT
