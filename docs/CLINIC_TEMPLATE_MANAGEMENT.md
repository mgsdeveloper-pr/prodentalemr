# Clinic Template Management

## Purpose

Clinic Template Management controls how a SaaS-owned Master Template becomes an independent clinic working template. The clinic copy can be selected, drafted, edited where permitted, previewed, published, and archived without changing the SaaS Master Template.

## Master Template Relationship

The Master Template is the platform blueprint owned by SaaS/Admin. A clinic template is copied from a published master or from an existing clinic template version. After copying, the clinic template has its own records, questions, sections, and version lifecycle.

## Organization And Clinic Ownership

The organization remains the tenant boundary. Clinic template versions are currently scoped to `organization_id` and `clinic_id` through `VerificationTemplateVersion`, `VerificationTemplateSection`, and `VerificationFormQuestion`. This preserves the existing Solo, Multi-location, and DSO hierarchy without introducing duplicate template models.

## Copy Semantics

Copying is an independent data copy, not a live reference. If the Master Template later changes, the clinic template does not automatically change. If a clinic template is customized, the Master Template remains unchanged.

## Template Lineage

Lineage is preserved through the existing `parent_version_id`, `source_version_id`, `source_section_id`, and `source_question_id` fields. These fields allow administrators to trace where a clinic template version came from without adding another lineage table.

## Client Template Versions

Clinic template versions reuse the existing statuses:

- `draft`
- `published`
- `archived`

Draft versions are editable. Published versions are immutable. Archived versions are removed from the active working list but remain available historically.

## Draft And Published Lifecycle

Published clinic templates cannot be edited directly. A user creates a draft from the active clinic template, from a specific clinic version, or as a fresh draft. Publishing a draft makes it the active clinic template and deactivates the previous active published version.

## Immutability

Published versions are protected by UI rules and backend actions. New changes require a draft copy. Verification requests that already reference an older template version are not automatically changed.

## Customization Permissions

Customization is controlled by the existing clinic setting `allow_verification_manager_template_edits` and existing user permission checks. SaaS admins and users with clinic template permissions can manage templates. Verification managers can manage clinic templates only when the selected clinic allows manager template edits.

## Full / Short Form

Clinic templates preserve the existing Full / Short / Full + Short form type settings from `VerificationTemplateVersion` and `VerificationFormQuestion`.

## Preview

Clinic template preview should render from the clinic template version, not from the SaaS Master Template after a copy exists. Preview is read-only.

## Authorization

Authorization uses the existing `User::canManageClinicTemplateSections()` and `User::canManageClinicVerificationSettings()` checks, backed by roles, clinic access, service/module access, and the selected clinic scope.

## Tenant Isolation

Clinic template actions require the selected clinic. Queries are scoped by `clinic_id`, `organization_id`, and the clinic panel scope, preventing cross-clinic access from route or Livewire state manipulation.

## Audit

The current implementation has SaaS support audit patterns and model-level audit models, but clinic template mutation auditing should be expanded in a follow-up pass using the existing audit tables. No new audit table is required.

## Future Verification Request Integration

New verification requests should use the active clinic template version. Existing requests should keep their saved template snapshot until an explicit future refresh action is chosen.

## Future Template Snapshot

The existing `VerificationTemplateVersionService` already supports request snapshots. This phase does not change request snapshot behavior.

## Future Refresh Template

Refresh Template is deferred. Future behavior should show Refresh only when a newer clinic template exists and the request is still editable.

## Deferred Functionality

- Automatic merge from newer Master Template into customized clinic templates
- Verification Request integration changes
- Refresh Template workflow changes
- Custom Report Builder changes
- PDF rendering redesign
- New template lineage table
- New ClinicTemplate or OrganizationTemplate model
