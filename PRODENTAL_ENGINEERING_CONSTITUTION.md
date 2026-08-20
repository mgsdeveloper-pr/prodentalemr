# ProDental Engineering Constitution

Version: 1.0
Status: Mandatory

---

This document is the governing engineering policy for the ProDental Platform.

Every Engineering Work Order, Phoenix Program task, UI modernization, feature implementation, bug fix, refactor, or documentation update must comply with these rules.

Violation of these rules is considered an implementation failure.

---

## Rule 1: Business Logic Is Sacred

Never modify business logic unless the Engineering Work Order explicitly requires it.

Protected layers include:

- Domain
- Services
- Actions
- Policies
- Authorization
- Validation
- Workflow
- Multi-tenancy
- Public ID Strategy

Presentation work must remain presentation only.

## Rule 2: Database Safety Policy

The development database contains valuable working data.

It must never be destroyed.

Strictly prohibited:

- `php artisan migrate:fresh`
- `php artisan migrate:fresh --seed`
- `php artisan db:wipe`
- `php artisan db:reset`
- Dropping all tables
- Truncating production or development data
- Destructive migrations
- Destructive seeders

Never recommend these commands.

Never execute these commands.

Never include them in validation.

## Rule 3: Forward Only Database Changes

Schema changes must always be incremental.

Allowed:

- `php artisan make:migration`
- `php artisan migrate`

Not allowed:

- Rebuilding database
- Dropping all tables
- Recreating schema

## Rule 4: Seeder Safety

Seeders must be idempotent.

Always use:

- `updateOrCreate()`
- `firstOrCreate()`

Never create duplicate records.

Never overwrite existing development data.

Never reseed everything unless explicitly requested.

## Rule 5: Testing Safety

Before executing `php artisan test`, verify that tests use an isolated testing database.

If the testing database is not isolated, do not run tests. Instead explain why.

Never allow automated tests to wipe the development database.

## Rule 6: PWDL Is The UI Authority

The following documents define the visual language:

- `PWDL.md`
- `UI_DESIGN_SYSTEM.md`
- `DESIGN_TOKENS.md`
- `WORKSPACE_LAYOUT_GUIDE.md`
- `WORKSPACE_COMPONENT_LIBRARY.md`

Do not invent new layouts.

Do not invent new spacing.

Do not invent new components.

Implement PWDL.

## Rule 7: Workspace First

Build workspaces.

Never build dashboards.

Every screen must answer:

- Where am I?
- What should I work on?
- What changed?
- What comes next?

## Rule 8: Component Reuse

Always reuse:

- PDS Components
- Focus Mode
- Work Context Engine
- Workspace Framework
- Services
- Actions
- Policies

Never duplicate architecture.

## Rule 9: Documentation

Every Engineering Work Order must review documentation.

Update where required.

If no update is required, explicitly state:

`No documentation update required.`

Never silently skip documentation.

## Rule 10: Validation

Safe validation only.

Allowed:

- `php artisan migrate`
- `php artisan route:list`
- `php artisan optimize`
- `php artisan optimize:clear`
- `php artisan view:cache`
- `npm run build`

Never include destructive validation.

## Rule 11: Product Boundary

ProDental is an Enterprise Insurance Verification Platform.

Do not implement PMS functionality unless explicitly requested.

Examples outside current scope:

- Appointment Scheduling
- Clinical Notes
- Treatment Plans
- Patient Charts
- Claims Workflow

## Rule 12: When Unsure

Never guess.

Never invent architecture.

Never perform destructive actions.

Stop.

Explain the concern.

Wait for user approval.

## Rule 13: Success Criteria

Every implementation must preserve:

- Business Logic
- Database
- Authorization
- Policies
- Services
- Architecture

Every implementation should improve:

- Presentation
- Usability
- Maintainability
- Documentation
- Code Quality

---

End of Document
