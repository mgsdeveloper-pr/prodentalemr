# Verification Request Template Snapshot

## Purpose

Verification requests use the clinic template version that is active when the request is created. Future template changes do not alter existing requests unless a separate explicit refresh workflow is used.

## Domain Boundary

Master Template -> Clinic Template Copy -> Active Clinic Template Version -> Verification Request -> Template Snapshot -> Dynamic Verification Form -> Answers -> Findings -> QA -> Report -> Delivery.

## Verification Request Model

`App\Models\VerificationRequest` is the domain-facing alias over the existing `billing_work_items` table. `BillingWorkItem` remains the storage model for backward compatibility.

## Template Selection

Request creation uses `VerificationTemplateVersionService::latestPublishedVersionForWorkItem()`.

If the request has a clinic, the service resolves or creates the clinic's published template copy. If there is no clinic context, it falls back to the platform master template.

## Template Attachment

`CreateVerificationRequestAction` attaches the template snapshot immediately after creating the request. The Filament create page also attaches the snapshot after creation because Filament performs the actual record insert.

The request stores:

- `verification_template_version_id`
- `verification_template_snapshot`
- `verification_template_snapshot_at`

## Snapshot Strategy

The project uses both:

- an immutable reference to `verification_template_versions`
- a JSON snapshot of version, sections, and active questions

This protects historical request rendering even if later template versions are published.

## Dynamic Form Rendering

The verification form resolves questions through the request's attached `verification_template_version_id`. It does not load the current active clinic template when rendering an existing request.

Sections, sub-sections, question order, input type, options, form visibility, and note metadata come from the attached template version/snapshot.

## Question Rendering

Supported question input types are defined by `VerificationFormQuestion::INPUT_TYPE_OPTIONS`. The form maps those controlled input types to existing Blade/Livewire controls. Database values are not executed as arbitrary components.

## Answer Persistence

Answers use the existing `verification_form_answers` table and `VerificationFormAnswer` model.

`SaveVerificationAnswerAction` is the write boundary. It validates that the question belongs to the request's attached template version before saving.

## Typed Answers

The action performs basic server-side validation for:

- date
- number
- currency
- percent
- yes/no
- select
- multi-select

It keeps the existing answer storage shape while preventing invalid options from being saved.

## Full / Short

Full and Short visibility is resolved from the attached template version/questions. Existing requests remain bound to their historical template context.

## Quick Reference

Quick Reference should derive from request data plus saved answer state. It must not maintain a second independent answer copy.

## Progress

Progress is derived from the visible question and answer state in the verification form. Future required/optional rules should continue to validate against the attached request template.

## Authorization And Tenant Isolation

Users must only access and modify verification requests they are authorized to use through existing panel access, policies, and tenant scoping.

Answer saves validate the question against the request template version to prevent cross-template or cross-tenant question injection.

## PHI Protection

Answer payloads may contain PHI. They should not be written into URLs, browser storage, or audit metadata. Existing activity logs should record meaningful lifecycle metadata only.

## Audit

The request already records lifecycle activity. Template attachment and form submission events should log metadata without raw answer payloads.

## Future Refresh Template

Refresh Template remains a separate workflow. It should explicitly move an editable request from its current template version to the latest active template and rebuild the snapshot.

## Future Request To Clinic

Request to Clinic / Waiting on Clinic should continue using the same request and answer boundary. Clinic responses must not edit template definitions.

## Future QA And Reports

Answers are separate from findings. Reports should continue to consume normalized findings/report profiles rather than treating the answer table as a report definition.
