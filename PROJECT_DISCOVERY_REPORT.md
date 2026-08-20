# Project Discovery Report

## Admin Portal Phase 4 - Clinic / Organization Template Management

### Discovery Findings

The project already contains the core architecture needed for Clinic Template Management. The existing `VerificationTemplateVersion`, `VerificationTemplateSection`, and `VerificationFormQuestion` models represent master and clinic template versions without requiring new template models.

### Master Template Reused

The SaaS Master Template remains the platform blueprint. It is represented by `VerificationTemplateVersion` with `scope = master`, plus related sections and questions.

### Copy Mechanism

`VerificationTemplateVersionService::createDraftFromSource()` already supports copying a source template into an independent draft version. It copies sections and questions into new rows, preserving source references.

### Lineage

Existing lineage fields are reused:

- `parent_version_id`
- `source_version_id`
- `source_section_id`
- `source_question_id`

No duplicate lineage system is required.

### Client Template

Clinic templates are represented by `VerificationTemplateVersion` with `scope = clinic`, `organization_id`, and `clinic_id`.

### Versioning

Clinic template versions reuse `draft`, `published`, and `archived` statuses. Published versions are kept immutable by workflow and actions.

### Customization

Customization is controlled through existing permission methods and the clinic setting `allow_verification_manager_template_edits`.

### Authorization

The project uses the shared `User` model, Spatie roles/permissions, clinic scope, and helper methods such as `canManageClinicTemplateSections()`.

### Audit

Audit infrastructure exists, but clinic template mutation audit coverage should be expanded using existing audit models rather than creating a new audit table.

### Full Workspace

The clinic settings UI already uses a wide workspace-style layout. Further polish should continue using existing PDS/PWDL workspace components instead of creating a new layout system.

### Tests

Existing clinic smoke tests already cover:

- clinic draft creation
- archive protection
- active published template selection
- portal credential mapping

Additional focused tests should be added for explicit master-to-clinic lineage, immutable publish behavior, and unauthorized cross-clinic access.

### Database State

No schema gap was found for Phase 4. No migration is required.

### Deferred Work

- request template snapshot integration
- refresh template workflow
- automatic master-to-client merge
- PDF redesign
- QA redesign
- billing
- AI

## Phase 5 - Verification Request + Template Snapshot + Dynamic Form

### Discovery Findings

The project already contains the main request, template, snapshot, dynamic question, answer, and submission structures. The correct approach is to reuse them, not create duplicate models or routes.

### Existing Verification Request Reuse

`App\Models\VerificationRequest` is a domain-facing alias over `BillingWorkItem`. The storage table remains `billing_work_items` for backward compatibility.

### Template Architecture Reused

`VerificationTemplateVersion`, `VerificationTemplateSection`, and `VerificationFormQuestion` represent master and clinic template versions. Existing request columns store `verification_template_version_id`, `verification_template_snapshot`, and `verification_template_snapshot_at`.

### Snapshot Strategy

The project uses an immutable template version reference plus explicit snapshot JSON. This is sufficient to keep historical requests tied to the template version that was active when the request was created.

### Answer Architecture

`VerificationFormAnswer` and `verification_form_answers` already exist with one answer row per request/question. `VerificationFormSubmission` stores form submission snapshots for the timeline.

### Dynamic Rendering

The verification form already resolves managed questions by `verification_template_version_id` when the request has one. This avoids loading the newest active clinic template for older requests.

### Quick Reference

Quick Reference already exists in the verification workspace. It should continue to derive from the same request/profile/answer state used by the form.

### Authorization

The project already uses panel access, policies, and Spatie roles. Phase 5 adds a server-side answer persistence action so question IDs from outside the request's attached template version cannot be saved.

### Tenant Isolation

Template resolution is scoped through the request's clinic/organization context. Answer saving validates the question against the request template version to prevent cross-template injection.

### Audit

Existing activity logging records lifecycle events. Full PHI answer payloads should not be added to audit metadata.

### Full Workspace

The existing Verification Workspace and form views are reused. No second workbench or duplicate form route was created.

### Tests

Focused Phase 5 tests were added for request template attachment, historical template protection, answer ownership validation, and select option validation.

### Database State

No schema gap was found for this pass. No migration is required.

### Deferred Work

- broader browser validation of every input type
- formal required/optional completion rules
- deeper Quick Reference answer binding review
- future Refresh Template phase
- future Request to Clinic phase
- future QA/report integration polish
