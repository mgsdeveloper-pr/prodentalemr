# ProDental Technical Debt Register

## Active Items

| Item | Area | Status | Notes |
| --- | --- | --- | --- |
| Release baseline | Delivery | Open | Prior approved phases remain spread across modified and untracked files. Inventory and commit the complete release before deployment. |
| Production infrastructure evidence | Operations | Open | Queue/scheduler supervision, backup restore, private storage, error monitoring, and mail delivery require production-environment evidence. |
| MFA and access recertification | Security | Planned | Define the production MFA policy and periodic privileged-access review process. |
| Document malware scanning | Security | Planned | Add content scanning before external uploads are accepted in a public production deployment. |
| Public onboarding | Product | Deferred | Registration stays disabled until plans, agreements, verification, activation, and approval are complete. |
| Organization Operations activity feed | Workspace | Deferred | Uses existing audit records only; unified activity stream is future work. |
| Template Builder | Verification | Open | Address the link, UI, action, workflow, and integrity findings in one change. See the [full SaaS and Clinic audit](TEMPLATE_BUILDER_AUDIT.md). |
| Onboarding and existing clinic template readiness | Verification | Open | Check both onboarding wizards, direct clinic creation, first-use provisioning, and repair for existing clinics. The local audit found five active Verification clinics with neither form configured. See the [deep audit](TEMPLATE_BUILDER_AUDIT.md#deep-audit-of-new-and-existing-client-paths). |

### Template Builder navigation audit

1. **Clinic sidebar.** Add a Clinic Template entry that opens Template Library, where users can choose a version.
2. **Clinic template import.** After an import creates a draft, make **Open Template Builder** open that exact version in the selected clinic. The pre-import breadcrumb can continue to open the library.
3. **Managed Verification template import.** The builder button currently opens General Settings. Link to the imported draft with the selected clinic and version, and respect access to the destination workspace.
4. **Clinic Active Forms.** Add a view link for each active Full or Short Form. Pass the published version and form type so the preview opens in the right context.

Verify the four paths together with permitted and restricted clinic and Verification roles. Check that switching clinics cannot open the previous clinic’s draft. Retest the existing SaaS Master Template import link and the Template Library’s version links.

### Template Builder UI findings

- **Reorder can move a question past a hidden row.** The table applies search, status, and ownership filters, while the move operation uses the full section order. Show the full section while reordering or make the move behavior clear. Disable the arrows at the actual first and last positions.
- **Clinic dialogs lack consistent keyboard handling.** The section dialog has no accessible name and its close button is labeled only with a multiplication sign. The custom dialogs have no focus trap. Give each dialog a name, manage focus on open and close, and support Escape consistently.
- **Question search relies on placeholder text.** Give the search field a persistent accessible label and expose the selected Questions, Reorder, or Form Preview state to assistive technology.

Recheck these findings in the browser during the combined fix, including filtered reorder, keyboard-only dialog use, and validation errors. The findings above come from the Blade and Livewire code.

### Template Builder action findings

- **Publication review can become stale.** Clinic review records the draft ID and current active-form selections. Final publication validates the draft again and activation checks for changed active selections, but neither action verifies that the questions and sections still match what the reviewer saw. Bind confirmation to the reviewed draft revision or content and require a fresh review after a concurrent edit. Check the SaaS publish flow for the same case.
- **Removal confirmation uses the currently displayed version.** The clinic builder stores the reviewed question IDs and section keys, but confirmation takes the version ID from the view at confirmation time. Bind the review to its original draft and invalidate it on context changes. Test two drafts that share section keys.
- **Continuing an existing draft drops the requested action.** When the draft dialog was opened to add a question or reorder, creating a replacement draft resumes that action. **Continue Existing Draft** instead switches to the Questions view. Preserve the original intent for both paths.

For the combined fix, test these actions alongside **Save, Discard, and Stay** for unsaved edits, dependent-question removal, and **Publish** versus **Publish & Activate**. Verify that a rejected or stale confirmation leaves the draft and active form unchanged.

### Template Builder workflow findings

The clinic flow runs from Template Library through draft creation or import, editing, preview, publication, activation, and the snapshot saved on a new verification request.

- **First-time setup does not guide the clinic to a usable starting point.** The new-draft dialog defaults to copying an active clinic template, even when the selected form has none. The library offers a fresh draft or an existing clinic version, with no explicit route to copy a released SaaS master. Request creation can initialize a clinic copy from a released master when no clinic form is active. Reconcile the builder and request-entry experience so staff can see what will be used before the first request.
- **Creating a draft from Template Library leaves staff in the library.** The success message confirms creation without linking to that version. Open the new draft or offer a direct action to open it, consistent with the post-import handoff.
- **Appointment verification always uses Full Form.** The appointment sender creates the request before its profile and then explicitly stores `full_form`. An active Short Form therefore has no effect on this path. Confirm whether the appointment flow should expose a form choice or use a documented default. Resolve the form before saving the request snapshot.

Check the complete flow for an unconfigured clinic, a clinic with only one active form, and a clinic with independent Full and Short versions. Cover fresh, copied, and imported drafts, publication without activation, activation for new manual and appointment requests, and historical requests retaining their original snapshots.

## Deferred Boundaries

- Full PMS and clinical integration
- Claims integration
- Dedicated analytics engine
- AI workspace intelligence

---

END OF DOCUMENT
