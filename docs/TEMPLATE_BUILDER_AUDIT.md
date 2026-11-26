# Template Builder audit

Status: Open for a combined fix. Scope: the Lenovo application’s SaaS Master Template, Clinic Template Builder, Managed Verification import handoff, and downstream verification requests.

## Executive summary

The builder has the core controls needed for a versioned product. SaaS owns the master, each clinic owns independent copies, and publication and activation are separate decisions. New requests attach a version and a snapshot. Existing requests keep their original form unless an authorized user explicitly refreshes an editable request.

The highest-priority gaps are around **what a confirmation actually applies to** and **how a clinic gets its first usable form**. Navigation and keyboard improvements belong in the same delivery because users encounter them in the same workflow.

## SaaS Master Template

1. Open **SaaS → Master Data → Master Template**.
2. Start a fresh draft, copy the current master or an eligible version, or import a reviewed spreadsheet. A working-draft designation determines the default target for question entry.
3. Arrange sections and subsections, configure questions and mappings, and preview the selected version. Question configuration includes form visibility, answer type, conditional follow-ups, procedure tags, optional notes, and audit requirements.
4. Publish with one of three release modes. **Publish only** preserves the active master. **Release to clinics** activates the released master for future clinic initialization. **Internal only** publishes with clinic visibility hidden and preserves the active master. Existing clinic copies are independent.
5. Unused draft deletion, archive and restore are distinct lifecycle operations. Published, used, or otherwise protected versions must be retained for history.

Code: [Master Template page](../app/Filament/Saas/Resources/VerificationFormQuestions/Pages/ListVerificationFormQuestions.php) · [Version service](../app/Support/VerificationTemplateVersionService.php)

## Clinic Template Builder

1. Select a clinic and open **Verification Settings → Template Library**. Create a fresh draft, copy the active form or an eligible clinic version, or import a spreadsheet.
2. Open the exact version through **Edit Draft** or **View**. Use **Questions**, **Reorder**, and **Form Preview**. Draft editing supports individual questions, section uploads, conditional follow-ups, and reviewed removal of questions or sections.
3. **Publish** creates a published version without changing the active forms. **Publish & Activate** also selects it for new requests. **Active Forms** supports independent Full and Short selection and checks that the prior active choices still match the confirmation.
4. Archive eligible inactive templates through the library. Existing requests keep their historical version and answers.

Code: [Clinic settings and library](../app/Filament/Clinic/Pages/VerificationSettings.php) · [Clinic builder](../app/Filament/Clinic/Resources/VerificationQuestions/Pages/ListVerificationQuestions.php) · [Inline actions](../app/Filament/Clinic/Resources/VerificationQuestions/Pages/Concerns/InteractsWithInlineTemplateBuilder.php)

## Links and workflow findings

| Finding | Current behavior | Acceptance check |
| --- | --- | --- |
| Clinic navigation | The Clinic Template resource is registered with its sidebar link disabled. Its bare URL redirects to Template Library because the builder requires a version. | Provide a clear sidebar entry to the library. The library can open a specific version. |
| SaaS import | The post-import button opens the Master Template Builder with the created draft ID. | Preserve this handoff, including after a retry. |
| Clinic import | The post-import button opens Template Library instead of the new draft. | Open the exact imported draft with the selected clinic preserved. |
| Managed Verification import | The builder button opens General Settings. Import permission and Clinic-panel access are separate checks. | Open an authorized destination for the selected clinic and imported version. When the user's role cannot reach that destination, show an appropriate next step. |
| Active Forms | Staff can see and change active Full and Short selections but must use the library to find the corresponding template. | Provide a direct view link using the active version ID and selected form type. |
| First-time setup | The draft dialog defaults to copying an active clinic form, which may be absent. It offers fresh or existing clinic versions. Request creation can initialize a released master if no clinic form is active. | Make the first usable form visible in setup. Test clinics with no active form, only one active form, and independent Full and Short forms. |
| Draft creation handoff | Creating from the Clinic library leaves the user in the library with a success message. | Offer a direct action to open the newly created draft. |
| Appointment intake | The appointment sender chooses Full Form. Activating Short Form changes other Short Form requests, but does not change this path. | Decide whether appointment intake should offer a choice or clearly default to Full. Resolve the selected form before attaching the snapshot. |

Code: [Clinic importer](../app/Filament/Clinic/Pages/ImportVerificationTemplate.php) · [Managed Verification importer](../app/Filament/Admin/Pages/ImportVerificationTemplate.php) · [SaaS importer](../app/Filament/Saas/Pages/ImportVerificationTemplate.php) · [Appointment sender](../app/Support/AppointmentVerificationSender.php)

## New clinic onboarding and master replication

Resolution update: SaaS Short v6 was released locally alongside Full v7. Organization onboarding, DSO onboarding, and direct clinic creation now require both released copies for Verification-enabled clinics. They roll back clinic creation if either form is unavailable. The findings below describe the audited baseline. Existing-clinic repair remains open.

**Onboarding does not create and activate both clinic forms.** The Organization and DSO wizards create the clinic and save its default template key. That key identifies the template family, not a Full Form or Short Form version. Website registration uses the same staff onboarding wizards.

| Step | What happens today |
| --- | --- |
| SaaS releases the master | Full and Short have independent active flags. Automatic copying considers a published, active master available to clinics. Internal-only and inactive masters are excluded. |
| Staff activate the clinic | The onboarding transaction saves clinic configuration without resolving, copying, or verifying the two master versions. A clinic can therefore activate without either form ready. |
| First request with no clinic form active | The resolver selects the released master for the requested form. The copy is published and active in the clinic for that form only, even when its source supports both. Sections and questions get new records and source links. A clinic lock protects the copy operation. |
| Request for the other form | If one clinic form is already active, the resolver stops with a setup error for the missing form. For example, an appointment can initialize Full Form, then a later Short Form request can fail even when SaaS has an active Short master. |
| Later SaaS release | Existing clinic copies keep their own versions. Because copying is deferred, a new clinic can inherit whichever master is active at first use, even if the master changed after onboarding. |

The Clinic question resource also has a provisioning path when it resolves a working version with no explicit form. That path can pick an active master by version number without checking which form the user intended. This is another reason to centralize initial setup.

### Onboarding acceptance checks

- Before completing Verification onboarding, resolve and show the exact released Full and Short source versions. Make missing or internal-only masters actionable in SaaS.
- Provision both clinic forms through one transaction or expose an explicit incomplete setup state. Test separate master versions, one master that supports both, and an unavailable form. Ensure failure or retry cannot create duplicates, leave an unintended partial setup, or replace an existing customized form.
- Verify Organization and DSO onboarding, including website registration handoff. Test any direct clinic-creation path against the same readiness rules.
- Confirm the new clinic’s **Active Forms** shows both intended selections. Create one synthetic Full request and one Short request, verify their clinic version IDs and snapshots, and check that a later SaaS release leaves both requests and the clinic copies intact.

Code: [Organization onboarding](../app/Filament/Saas/Pages/TenantOnboarding.php) · [DSO onboarding](../app/Filament/Saas/Pages/DsoOnboarding.php) · [Clinic template resolution and copying](../app/Support/VerificationTemplateVersionService.php) · [Clinic working version lookup](../app/Filament/Clinic/Resources/VerificationQuestions/VerificationQuestionResource.php)

## Deep audit of new and existing client paths

### Local readiness

The local MySQL database has **one released active Full master and no released active Short master**. Of nine clinics whose model reports active Verification services, four have both active clinic forms and five have neither. None have only one active form at the time of the check. Existing clinics with both forms can continue with their own copies. A clinic with neither can initialize Full under the current rules, but Short setup needs an eligible released master or an independently published clinic Short Form. No duplicate master section keys were found in the versioned master rows checked.

### Route and entry-point coverage

| Entry point | Audit result |
| --- | --- |
| `/saas/organization-onboarding` and `/saas/dso-onboarding` | Both create the clinic and store the template family. Neither checks that its required forms are ready or provisions both copies. Website applicants enter these wizards through staff review. |
| `/saas/clinics/create` | Adding a clinic to an existing organization uses the resource create page and normalizes clinic settings. It has no master replication hook. Editing an existing clinic’s settings also provides no form repair workflow. |
| Legacy public registration | The controller creates a clinic without provisioning forms. The feature is disabled by default. Include it in regression coverage if this entry point is enabled. |
| `/saas/master-template` and three panel import pages | The SaaS route owns master release. Clinic and Managed Verification import produce clinic drafts without activating them. The post-import link issues are documented above. |
| `/clinic/verification-settings` | Template Library and Active Forms allow manual recovery with local drafts and eligible published versions. They do not tell SaaS which existing client sites lack a form or guide a clinic to a released master. |
| `/clinic/verification-questions` and its create, edit, and reorder pages | The main builder requires a version. The shared working-version helper used by question actions and ordering looks for clinic drafts and falls back to active-template initialization. Separate explicit-version lookups from default discovery. |
| Manual Verification request entry | Clinic and Managed Verification create pages save the selected form profile before attaching the snapshot. Both enable database transactions, so a failed template selection should roll back creation. |
| Verification spreadsheet intake | The importer supplies profile data to the shared request action. Include Full and Short rows and a missing-form row in the combined regression checks. |
| Appointment intake | The direct send path attaches the template before creating the profile and then hard-codes Full. It can therefore be the first action to initialize a new clinic’s Full slot. |

### Further integrity findings

- **Resolved: Template lookups during navigation and review.** SaaS and Clinic Builder lookups select existing drafts or published versions without seeding definitions or copying templates. Opening a legacy request, checking refresh availability, and auditing its answers do not provision templates or attach a snapshot. See the verification entry below.
- **An invalid explicit draft ID can fall through to another version.** The Clinic working-version helper filters the draft lookup by an explicit ID but still falls back to the active/provisioned version when no draft matches. For direct question routes, reject a missing, foreign, or non-editable explicit draft instead of continuing with a different version. The main builder already rejects an unmatched explicit version.
- **Resolved: First-use behavior depended on request ordering.** Full and Short requests now each require an existing active version. An unconfigured request fails validation without entering the provisioning path, so neither form can be activated as a side effect of the first request.
- **The current-client repair must preserve live templates and history.** Fill only missing slots, report the source and target version per form, and leave customized active forms, in-progress drafts, and historical snapshots intact. Do not make a new SaaS release retroactively change established clinics. A clinic that deliberately uses one form needs a distinct status from a clinic that is missing a required form.

Code: [Direct clinic creation](../app/Filament/Saas/Resources/Clinics/Pages/CreateClinic.php) · [Legacy registration](../app/Http/Controllers/Auth/RegisteredUserController.php) · [Clinic request creation](../app/Filament/Clinic/Resources/VerificationRequests/Pages/CreateVerificationRequest.php) · [Spreadsheet intake](../app/Support/VerificationRequestImportService.php) · [Legacy audit version lookup](../app/Services/Verification/VerificationAuditService.php)

## Actions and UI findings

Step 5 resolution: Clinic sidebar and active form links now open the correct library or version. Post-import links use the imported draft, subject to destination permissions. SaaS and Clinic Builder confirmations bind to the reviewed draft and its content. Clinic reorder shows the complete section for the selected form, and its dialogs support focus trapping, Escape, and focus return.

| Priority | Action or control | Finding and acceptance check |
| --- | --- | --- |
| High | Clinic Publish and Publish & Activate | Review captures the draft ID and active selections, but no draft-content revision. A second editor can change the draft while the dialog is open. Require a fresh review after a change and verify that a stale attempt leaves publication and activation unchanged. |
| High | SaaS publication | Final publication resolves the selected draft and validates its current content. Verify that the published draft and revision are the ones displayed when the user opened the release dialog. Apply the same confirmation rule as Clinic. |
| High | Clinic removal | The review stores question IDs and section keys, but confirmation resolves the current displayed version. Bind confirmation to the reviewed draft and clear it on context changes. Test two drafts with matching section keys and a section with dependent questions. |
| Medium | Continue Existing Draft | Resuming an existing draft resets the view to Questions and loses pending Add Question or Reorder intent. Carry the intent through both create and resume paths. |
| Medium | Reorder | The screen filters displayed rows, while moves use the full section order. A move can cross a hidden row, and arrows at the true edges are enabled. Use an unfiltered reorder view or make the movement explicit and disable invalid moves. |
| Medium | Keyboard and screen readers | Clinic dialogs lack consistent focus trapping and Escape behavior. The section dialog lacks an accessible name, search relies on placeholder text, and the view switcher does not expose its selected state. Verify keyboard entry, focus return, and validation feedback. |

Also verify **Save, Discard, and Stay** with dirty question edits. A validation failure should keep the editor open and prevent the pending action. Confirm that previews and preview PDFs leave drafts, request answers, and active forms unchanged.

## Database and integrity

| Storage | Purpose and safeguards |
| --- | --- |
| `verification_template_versions` | Master or clinic scope, organization and clinic ownership, version number, parent/source lineage, lifecycle, visibility, working-draft state, and per-form active flags. The service changes active flags inside transactions and locks the affected rows. |
| `verification_template_sections` | Versioned hierarchy, ordering, allowed empty sections, and source section links. A composite unique index covers clinic, version, template key, and section key. |
| `verification_form_questions` | Versioned prompts, form applicability, answer definitions, source question links, mappings, procedure metadata, and conditional relationships. Copies have independent records. |
| `verification_template_imports` | Import receipt with a unique token, payload hash, actor, clinic, created version, form type, and question count. This supports retry protection and import history. |
| `billing_work_items` | Verification request stores the attached template version, snapshot JSON, and snapshot timestamp. |
| `verification_form_answers` | Stores request answers by request and question ID. The database enforces one row per pair. The answer action checks the question belongs to the request’s template version. |
| `verification_form_submissions` | Stores versioned submissions, including the prior template and answer state captured before an explicit refresh. |

### Database risks and checks

- The active-per-form invariant is enforced by the service rather than a unique schema constraint. Keep activation on the guarded service path and test that concurrent publication or activation cannot leave two active versions for one form.
- The section index contains nullable `clinic_id` for master rows. In MySQL, a unique index allows multiple rows with a null key part. Confirm duplicate master section keys are prevented across create, import, and copy paths. If stronger enforcement is needed, audit existing keys before adding a constraint.
- Displayed version numbers are allocated against compatible form types. Full and Short can share a displayed number, so links, logs, and confirmations should use the version ID and show form type.
- The workbench reads versioned question and section rows as well as snapshot data. Historical rendering therefore also depends on retaining the linked rows. Verify old requests and PDFs after archival, and treat requests missing a snapshot as a separate legacy integrity check.

Code: [Version schema](../database/migrations/2026_07_15_000001_create_verification_template_versions_table.php) · [Versioned section index](../database/migrations/2026_08_07_000003_update_verification_section_unique_index_for_versions.php) · [Release and import safeguards](../database/migrations/2026_09_28_000002_add_template_release_safeguards.php)

## Authorization and history

- Verify platform authoring, clinic editing, and publication as distinct permissions. Clinic editing is scoped to the selected clinic. Verification managers also need clinic access and the clinic’s edit setting. Publication requires the dedicated grant on the operational service path.
- The registered version policy’s `publish` method checks creation rights and draft status, while the service enforces the dedicated publishing grant. Align those rules so a future policy-only entry point cannot drift from the service.
- Review lifecycle audit coverage. Import receipts and request refresh activity are explicit. Verify whether draft edits, publishing, activation, and archival provide the actor, clinic, version, and outcome needed for support and compliance. Keep patient answers out of template lifecycle event metadata.
- Refresh is an explicit action for eligible editable requests. Review compares answer definitions and identities, rejects ambiguous or incompatible mappings, and records the prior state before carrying compatible answers forward. Completed requests retain the original version.

Code: [Version policy](../app/Policies/VerificationTemplateVersionPolicy.php) · [Answer save action](../app/Actions/Verification/SaveVerificationAnswerAction.php) · [Protected refresh](../app/Actions/Verification/RefreshVerificationTemplateAction.php)

## Combined fix acceptance

1. Exercise both SaaS and Clinic from fresh, copied, and imported drafts, including retry and post-import links.
2. Test viewers, editors, publishers, assigned Verification managers, and a user without access to the selected clinic. Verify that URLs and Livewire actions reject a different clinic’s version.
3. Test parallel edit, publish, activate, and remove attempts. A stale review must leave the reviewed draft and active forms unchanged.
4. Verify separate Full and Short activation through manual and appointment entry, using synthetic patient and insurance records. Check the version and snapshot attached to each request.
5. Confirm historical answers, conditional questions, preview/PDF output, and permitted refresh after a later release. Verify that completed requests and archived versions remain readable.
6. Verify desktop and narrow layouts, keyboard navigation, and each link and confirmation state. Check that validation errors leave the user where they can correct them.

### Request intake decision

Direct appointment actions use **Full Form**. The appointment editor explains this beside the verification toggle, and the appointment list and detail actions identify Full Form before staff submit. Staff can select Short Form through manual request entry when needed.

Step 4 verified manual Full and Short creation in Clinic and Managed Verification, and actual XLSX Full and Short imports through both channels. The appointment sender now saves the explicit Full profile before snapshot selection. XLSX imports also accept omitted patient identifier and subscriber name headers, using existing fallback values.

Spreadsheet intake accepts Full Form and Short Form, including the `full_form` and `short_form` keys, regardless of case and surrounding whitespace. A blank or missing value defaults to Full Form. Other values fail that row with an instruction to correct the form type. Valid rows continue importing.

Four focused tests passed in isolated SQLite. The appointment tests verified visible Full Form guidance and the active Full snapshot. Mixed XLSX tests in Clinic and Managed Verification rejected an invalid row while saving the correct snapshots for valid Full, valid Short, and blank rows. XLSX parsing now preserves blank cell positions.

## Verification evidence

A read-only check of local MySQL found all seven tables listed above. It counted 28 template versions, 278 section rows, 1,913 question rows, and one import receipt. Five published versions were active for each form type, with zero duplicate active groups by scope, clinic, and template key. All 17 verification request rows had both a version ID and a snapshot. These counts describe the local database at the time of the check.

Three feature suites passed in isolated SQLite with **88 tests and 512 assertions**. They covered import and retries, mappings and preview/PDF output, draft validity, independent Full and Short activation, stale activation reviews, request snapshots, archival history, and answer validation. The new findings still need targeted regression tests and browser reproduction under the relevant roles. No templates were edited, published, or activated in local MySQL.

### Final Lenovo end-to-end check

With the running app on port 8003, the browser loaded SaaS Master Template and Organization Onboarding. Both links in Clinic Active Forms reached the Builder and opened the appropriate Full or Short preview.

The write flow ran through the Lenovo Livewire components against local MySQL in a rollback-only transaction using synthetic records. Organization onboarding created independent clinic copies from released Full v7 and Short v6 and activated both. The new clinic owner copied Short into a draft, previewed it, then published and activated it. The Full selection stayed active. Two manual requests for the synthetic patient used the saved insurance policy. Each request saved the expected form type, exact active clinic version ID, and a snapshot with questions. The transaction was rolled back and the synthetic clinic and account were confirmed absent.

### Browser sign-off

The initial approval check blocked durable writes. After the user authorized retaining synthetic records in local MySQL, all three browser submissions succeeded.

| Browser path | Clinic ID | Initial Full version ID | Initial Short version ID |
| --- | ---: | ---: | ---: |
| Organization onboarding | 15 | 57 | 58 |
| Direct clinic creation | 16 | 59 | 60 |
| DSO onboarding | 17 | 61 | 62 |

MySQL confirmed all six copies came from released SaaS Full v7 and Short v6. Both onboarding wizards accepted Austin after the City picker fix. The Organization and DSO browser drafts were resumed for activation.

In Clinic 15, the browser opened Short from Active Forms, created a draft from it, previewed it, and published and activated version ID 63. Full version ID 57 remained active. A synthetic provider, patient, and insurance policy were prepared as fixtures in local MySQL. Both requests were created through the browser and opened their Verification Form pages.

| Request | Form | Version and snapshot ID | Insurance policy ID |
| --- | --- | ---: | ---: |
| BWI-202611-0001 | Full | 57 | 5 |
| BWI-202611-0002 | Short | 63 | 5 |

The synthetic clinics, accounts, templates, and requests remain in local MySQL as authorized.

### Remaining audit items

- The browser exposed an empty dependent City picker after selecting Texas. Both onboarding wizards now use a native City selector. Austin was selectable in both browser flows, and Organization onboarding filled ZIP 78701.
- The rollback-only MySQL follow-up passed for Organization onboarding, DSO onboarding, and direct clinic creation. All three received active copies from Full v7 and Short v6. The Organization test clinic owner published and activated a Short draft without changing Full, then created one Full and one Short request with the expected policy and snapshot. The synthetic clinics and writes from the rollback test were rolled back.
- Keep the longer-term review items open, including the version policy and service permission mismatch, database-level active-form uniqueness, and complete lifecycle audit coverage.

## Read-only Builder and request-review lookups (2026-11-24)

- SaaS Builder opens with an empty state when no master exists. Clinic Builder lookups return no working version when a clinic lacks a draft or an active published copy, even when a released master exists.
- Request review reads the stored template reference or resolves the active version for the request’s exact form. When no matching version is available, it reports missing setup and disables refresh. Opening a review still records the existing console-access activity.
- Template copying remains in setup, such as clinic creation, and in explicit Builder actions. Request creation and saves can attach snapshots from existing versions. A missing template causes validation, and manual request creation rolls back on failure.
- Four SQLite regression cases passed (22 assertions). They covered the empty SaaS Builder, the Clinic Builder lookup before and after explicit Full and Short setup, and opening and auditing legacy requests with and without an active form. The configured form retained its structured layout during review. Neither review assigned a version or snapshot, and refresh was unavailable when the form was missing.
- The broader SQLite feature runs passed 216 tests across the Builder, request review, manual and spreadsheet intake, appointments, and Organization, DSO, and direct clinic creation.

## TB-01 — Clinic Settings draft confirmation (2026-11-24)

Clinic Settings uses a shared review dialog for publishing and deleting drafts. The server stores the clinic, draft ID, action, and content revision. Confirmation rechecks clinic scope and permissions, then uses the Builder's transactional revision guard. Changing a question or section after review invalidates the confirmation. Publication from Settings still requires a separate Active Forms action to make that version active.

**Resolved in code and verified in isolated SQLite.** The Clinic Settings and Builder suites passed 67 tests with 416 assertions. They covered successful publication and deletion, stale revision rejection for both actions, confirmation without review, cancellation, cross-clinic draft rejection, and publishing permission revoked after review. Publishing leaves activation to Active Forms. A live-browser check of the new dialog and MySQL concurrency testing were outside this verification pass.

### TB-01 local MySQL sign-off

In the running Lenovo app, the review dialog identified synthetic draft CT-RYAPQ054. A second browser tab saved a draft edit after review. The first confirmation left the draft unpublished. A fresh review published it as inactive and left Full v1 and Short v2 selected. The synthetic published template remains in the QA clinic.

A rollback-only local MySQL check exercised deletion through the same revision-guarded service. The stale revision was rejected, the newly reviewed copy was deleted inside the transaction, and both active selections matched the baseline. Browser deletion and simultaneous lock contention were not exercised.

## TB-02 — Template lifecycle authorization

Record policy checks now distinguish master and clinic versions. Clinic management requires the actor's own clinic, SaaS administrator access, or an assigned Verification manager with clinic-level editing enabled. Publishing and activation use the dedicated publishing grant for the record scope. Draft deletion, metadata updates, archival, and working-draft changes check the record-level management rule against the locked version.

The final focused SQLite run passed 8 tests with 61 assertions. It covered SaaS edit and publishing grants, Clinic same-tenant and cross-clinic decisions, assigned Verification access and clinic delegation, stale and cancelled confirmations, permission revocation, and retention of a draft referenced by a request.

### Simultaneous MySQL lock verification

Two independent local MySQL connections exercised live contention on the synthetic QA clinic. One held the clinic row lock while the other attempted Full Form activation through the lifecycle service. The waiting operation reached MySQL lock timeout 1205 after 2.5 seconds and both active slots matched their baseline. The activation succeeded on retry after the lock was released, and rolling back the retry preserved both original active form selections. The browser reopened at the login screen. Browser deletion and the live role matrix still require authenticated QA sessions.

### Browser deletion and TB-02 role checks

The approved browser deletion removed synthetic draft CT-YNJPQNB2 from the QA clinic. The row disappeared from the Template Library and was absent in MySQL, including soft-deleted rows. The active Full v1 and Short v2 selections were still displayed after deletion.

Live browser results covered three roles. The SaaS administrator opened Master Template. A synthetic Clinic manager opened its own Template Library and received HTTP 404 for the other QA clinic's Full template. A synthetic Verification manager opened the Verification workspace and received HTTP 403 on the SaaS and Clinic Builder URLs. The browser fixtures had no active Verification service enrollment, so delegated editing and publishing remain covered by the automated role matrix. Both temporary browser accounts were deactivated.

## TB-03 — One active version per form and scope

The read-only local MySQL preflight found 13 active Full slots and 13 active Short slots with zero duplicates. Two combined templates occupy both slots and one occupies the Full slot only. The migration preserves the existing flags and enforces uniqueness independently for Full and Short by scope, clinic, and template key. The null clinic on master templates participates in uniqueness. Inactive versions can coexist.

Only the TB-03 migration was applied to local MySQL. All active flags and the template-version ID, snapshot, and snapshot timestamp fields on all 19 existing work items matched before and after migration. Publishing now writes the new version as inactive before activating it within the existing transaction.

MySQL rejected all four direct duplicate probes across master and clinic Full and Short. With two connections, a held clinic lock made a concurrent activation wait and time out. After release, the retry activated Full, preserved Short and all request snapshots, and rolled back to the original selections. Four isolated SQLite regression cases passed (12 assertions).

The broader isolated SQLite run passed 131 tests (889 assertions) across request templates, Clinic Builder, onboarding, and SaaS lifecycle workflows.

### TB-03 follow-up review

A read-only MySQL check confirmed both active-slot indexes are installed and unique. It found zero aggregate flag mismatches, zero active draft or retired versions, zero invalid master or clinic scope combinations, and zero flags on an unsupported form type. No blocker was found for normal activation, onboarding, or Clinic Settings flows.

The Clinic UI uses soft deletion. If a future maintenance workflow hard-deletes clinics, review template retention first. The template foreign key sets the clinic ID to null on hard deletion, and active clinic-scope versions from multiple hard-deleted clinics could conflict in the resulting null-clinic slot. A live hard-delete or migration rollback was not exercised.

## TB-04 — Template lifecycle audit events

SaaS and Clinic lifecycle actions now write to the existing audit log. The events are publish, release, activate, archive, restore, and delete. Each event records the actor, template version, scope, and structured before/after state. Activation and release also record the previous and new version IDs for the affected form slots. Release records when an eligible master becomes active for clinic setup. Reconfirming an already active version creates no duplicate activation or release event.

Audit events are written in the same database transaction as the lifecycle change. The payload excludes question content, change notes, request snapshots, and patient data. Initial clinic setup and template-copy operations are outside these explicit lifecycle events.

A rollback-only MySQL check verified all six events, their actors, status transitions, version IDs, and active-slot changes. The rollback removed all six test events and restored the original active master selection.

The broader isolated SQLite run passed 183 tests (1,236 assertions), including actor and payload checks for each event, Clinic activation and archival, and audit rollback. TB-04 is resolved for explicit lifecycle actions.

## TB-05 — SaaS Master Template summary

The SaaS summary shows the active Full and Short slots separately using the per-form activation flags. Each slot displays its version, name, and clinic visibility. A shared version appears in both slots, and a missing slot reads “No active version.” Previous Published counts only published rows inactive in both slots. The summary reuses the existing version history query.

Verified with 65 SQLite feature tests (523 assertions), including rendered-summary checks for separate active versions, a shared active version, and a missing Short slot. TB-05 is resolved.

## TB-06 — SaaS Master Template history performance

The history now returns 20 versions per page in descending version-number and ID order. The summary still calculates counts and active Full and Short versions across the entire history. The Builder detail screen skips the history queries. The last-page deletion flow returns to the nearest populated page.

History rows fetch request and lineage existence checks with the page query, including soft-deleted references. Draft controls use those results for display. Lifecycle actions still check the current locked records before writing.

In a rollback-only local MySQL benchmark with 33 versions, including 25 synthetic drafts, history preparation dropped from 241 queries to 5 with the summary and first 20 rows. The measured data-preparation time was 134.63 ms before and 12.73 ms after. SQL time was 56.70 ms before and 5.58 ms after. These timings are one local comparison.

Verified with 111 SQLite feature tests (785 assertions). The regression checks cover bounded query count, second-page visibility, global summary totals, protected drafts, archive and restore, working-draft selection, and confirmed deletion from the final page. TB-06 is resolved.

## TB-07 — Clinic creation and template provisioning

Clinic creation now invokes the template provisioner from one model creation event. Organization onboarding, DSO onboarding, and direct SaaS clinic creation choose the required setup policy when saving a verification-enabled clinic. The save and template copy share a transaction. A missing released form still rolls back creation, and direct creation still reports the issue on the template form field.

Reviewed website registrations activate through the same Organization or DSO wizard. The legacy public registration controller and the By Design and Demo client seeders keep the best-effort policy. They copy the released forms available when a clinic is first created. A clinic with verification disabled retains the same best-effort behavior. Existing Demo clinic updates do not provision templates. Read paths remain read-only.

Verified with 74 isolated SQLite tests across the main and focused runs. The tests assert one provisioning call for all three SaaS creation flows and legacy registration, transactional rollback when Short is missing in a required flow, successful website draft activation, successful legacy registration with zero, one, or two released forms, retry safety, and best-effort behavior with verification disabled. No local MySQL records were changed. TB-07 is resolved.

## TB-08 — Safe Clinic Settings errors

Clinic Settings shows actionable validation feedback for expected failures. Unexpected errors during draft creation, editing, review, confirmation, archive, active-form activation, or PDF saving use a generic retry and support message. Laravel receives the original exception through its server-side reporting pipeline. Access-denied and missing-record responses retain their HTTP behavior. Active Forms keeps its inline validation errors.

PDF settings use one transaction for the preset and clinic updates so an unexpected failure can be reported without leaving a partial save.

Verified with 72 SQLite feature tests (467 assertions). The final focused run passed five checks (30 assertions), including exact safe notifications, reporting the original unexpected exception, keeping validation out of server error reports, PDF rollback, and preserving 403 and 404 behavior. TB-08 is resolved.

## Final Lenovo sign-off — TB-01 through TB-08

| Item | Final evidence |
|---|---|
| TB-01 | In the running Clinic Template Library, the SaaS administrator reviewed and published synthetic draft CT-F3WDDAF5 as inactive. Prior browser stale-publish and approved deletion checks still apply. |
| TB-02 | **Open for browser release sign-off.** A Verification-manager-only QA account had an active verification enrollment, clinic assignment, delegated editing enabled, and publishing grants. It opened the Verification workspace with the QA clinic selected. The live backend allowed management and publication on a QA draft, but the Clinic Builder returned 403. The Clinic panel role gate excludes Verification-manager-only users. Confirm a supported, clinic-scoped browser entry point before signing off on delegated editing and publishing. |
| TB-03 | Both live MySQL unique indexes remained installed. Clinic Full and Short selections remained on versions 57 and 63, and the two existing QA requests retained their original snapshot version references and populated snapshots. The previous two-connection contention and full snapshot comparison remain the coverage for those checks. |
| TB-04 | The browser publication wrote a MySQL audit row with the administrator actor, exact version, and draft-to-published transition. The previous rollback-only MySQL test covers all six lifecycle events. |
| TB-05 | The live SaaS summary showed Full v7 and Short v6 separately and counted two previous published versions. |
| TB-06 | The live history rendered. The QA dataset fit on one page. Multi-page actions and bounded query counts are covered by the feature tests and prior MySQL benchmark. |
| TB-07 | The strict creation path copied the correct released Full and Short masters in a local MySQL transaction that was rolled back. Organization, DSO, direct creation, and legacy registration also run in the isolated regression suites. |
| TB-08 | The isolated failure tests cover actionable validation, sanitized unexpected notifications, reporting, and PDF rollback. No unexpected server failure was induced in the running app. |

The temporary QA enrollment and publishing grants were removed, the original clinic delegation setting was restored, and the Verification QA account was disabled again. The inactive synthetic browser-published draft and its audit event remain in the local QA clinic.

Final isolated SQLite regression pass: 195 tests and 1,380 assertions passed. The lifecycle audit logger, TB-03 migration, and this audit document are present locally as untracked Git files and need to be included when committing the release.

## Final remediation and certification — 24 November 2026

This entry supersedes the previous TB-02 browser status. The checkout already held unrelated tracked, staged, and untracked work. The pre-change checkpoint is in `C:/Users/ASUS/AppData/Local/Temp/Denentiq-TB-baseline-8693a5bc-afa7-410f-a688-d2c1fa9e72b7`. It contains the tracked working and staged diffs, the Git inventory, and copies of the three pre-existing untracked Template Builder release files.

### Findings and dispositions

| Finding | Original priority | Root cause and resolution | Evidence and remaining verification | Status |
|---|---|---|---|---|
| TB-02 | Release blocker | The Clinic workspace role gate correctly excluded Verification-only identities. Added a Verification workspace resource at `/verification/clinic-templates` that reuses the existing Clinic Builder. The route and Livewire hydration validate clinic selection, assignment and edit delegation. Publication retains its separate permission. Managed Import links to the exact same-clinic draft. | The synthetic Verification Manager opened clinic 15, saved a question, reviewed and published version 387 as inactive. Full 57 and Short 63 stayed active. An unassigned clinic version returned 404. HTTP and Livewire tests cover assignment and permission revocation, including a denied publication review with editing still allowed. The complete browser role matrix and a delegated browser import are pending. | Partially Resolved |
| C-01 | P1 | Legacy ordering code directly wrote per-question sort orders. The shared writer locks and authorizes the exact draft, validates all row IDs before writing, and commits the order atomically. Legacy create and edit use Filament transactions. Managed and Clinic question arrangement share the guarded clinic implementation. Clinic legacy section creation and draft deletion take the same clinic lock used by publication. | Two independent MariaDB sessions exercised both publication orders. Reorder first made the earlier review stale. Publish first made the draft protected. Both contenders timed out on the lock before retry. SQLite verifies rollback on a second-row failure, preserves field mappings, and rejects archived drafts. Additional MariaDB checks serialized two reorders, rejected stale deletion after a reorder, and blocked reordering while a rolled-back deletion held the lock. | Resolved – Independently Verified |
| C-02 | P2 | One historical clinic request has matching stored and embedded master version IDs. Older persisted assignments predate the clinic-only current selection. The older service used the platform master when no clinic relation was present at attachment, and used a clinic version when the clinic relation existed. The exact writer for this record could not be established from migrations, source history, and row metadata. Current manual, spreadsheet and appointment intake choose the clinic's active version when no template metadata is provided. Historical audit and PDF rendering use the saved association. | The synthetic legacy case resolves the same question, produces a PDF, reviews the mapped clinic replacement, and preserves the complete raw request record. No historical data change was performed. The internal intake service still accepts prefilled template metadata from its caller. Review that internal contract separately before exposing intake to any new caller that supplies arbitrary fields. | Valid Legacy Behavior |
| C-03 | P2 | Opening Clinic Settings used to persist a preset. It now displays effective defaults from existing preset or clinic state. The first authorized save serializes on the clinic row and reuses the same-name default if another session has already saved it. Existing explicit presets are scoped by clinic and kept editable. | Two MariaDB sessions opened Settings with zero presets. The second save waited roughly 2.1 seconds with zero competing rows, then a retry reused the first default. SQLite verifies Clinic PDF preview returns PDF bytes without persisting presets, denial after access is revoked, and rollback on failure. | Resolved – Independently Verified |
| C-04 | P2 | Clinic history loaded all rows and repeated lifecycle, lineage and section queries for every version. It now fetches 20 rows with database-side count and existence queries, applies filters before pagination, and keeps the active slots independent of the page. | MariaDB measurements below. SQLite exercises both pages, form filtering, and the action that removes the last row on page two. Browser layout across breakpoints remains pending. | Resolved – Independently Verified |

### Clinic history benchmark

Each measurement used the same local synthetic clinic and rollback-only draft fixtures. Query counts and wall times cover history preparation, including the page data. Browser rendering and network time are outside the measurement.

| Matching versions | Before queries | After queries | Before elapsed | After elapsed | Rows returned before → after |
|---:|---:|---:|---:|---:|---:|
| 8 | 118 | 7 | 92.82 ms | 16.95 ms | 8 → 8 |
| 28 | 434 | 4 | 275.30 ms | 8.18 ms | 28 → 20 |
| 103 | 1,634 | 4 | 1,086.93 ms | 6.92 ms | 103 → 20 |

### Implementation change register

- TB-02 added `app/Filament/Admin/Resources/ClinicTemplates/ClinicTemplateResource.php` and `app/Filament/Admin/Resources/ClinicTemplates/Pages/ManageClinicTemplate.php`. It changed `app/Providers/Filament/AdminPanelProvider.php`, `app/Filament/Admin/Pages/ImportVerificationTemplate.php`, `app/Filament/Clinic/Resources/VerificationQuestions/Pages/ListVerificationQuestions.php`, `app/Filament/Clinic/Resources/VerificationQuestions/Pages/Concerns/InteractsWithInlineTemplateBuilder.php`, and `app/Support/ClinicTemplateBuilder.php`. The existing version policy and Clinic panel role gate remain the enforcement points.
- C-01 changed `app/Support/VerificationTemplateVersionService.php`, `app/Filament/Clinic/Pages/Concerns/InteractsWithVerificationQuestionLibraryOrdering.php`, and `app/Filament/Saas/Pages/Concerns/InteractsWithAdminVerificationQuestionLibraryOrdering.php`. Under `app/Filament/Clinic/Resources/VerificationQuestions/Pages/`, it changed `ListVerificationQuestions.php`, `CreateVerificationQuestion.php`, `EditVerificationQuestion.php`, and `Concerns/InteractsWithVerificationQuestionOrdering.php`. Under `app/Filament/Saas/Resources/VerificationFormQuestions/Pages/`, it changed `CreateVerificationFormQuestion.php`, `EditVerificationFormQuestion.php`, and `Concerns/InteractsWithVerificationFormQuestionOrdering.php`.
- C-03 and C-04 changed `app/Filament/Clinic/Pages/VerificationSettings.php` and the history view `resources/views/filament/clinic/pages/verification-settings.blade.php`.
- Regression changes are in `tests/Feature/Clinic/ManagedServicesSmokeTest.php`, `tests/Feature/ClinicSectionBuilderTest.php`, `tests/Feature/ClinicSettingsTemplateEmptyStateTest.php`, and `tests/Feature/VerificationRequestTemplateFormTest.php`.
- No production dependency, configuration, policy, or migration file was added or changed by this remediation batch. There were no source deletions. Existing unrelated changes were preserved. Five files still need inclusion in the release because they are local and untracked. These are both new Admin resource files, `app/Support/VerificationTemplateLifecycleAudit.php`, `database/migrations/2026_11_24_000001_enforce_unique_active_verification_template_forms.php`, and this audit. This register scopes the batch. The dirty working tree is not itself a reviewed release package.

### Test and deployment evidence

- **Unit.** `php -d extension=zip vendor/bin/pest --compact --testsuite=Unit` passed 6 tests and 9 assertions.
- **Feature and downstream.** `php -d extension=zip vendor/bin/pest --compact tests/Feature/ClinicSectionBuilderTest.php tests/Feature/VerificationSettingsSafetyTest.php tests/Feature/Clinic/ManagedServicesSmokeTest.php tests/Feature/Saas/RevenueOperationsSmokeTest.php tests/Feature/VerificationTemplateImportTest.php tests/Feature/VerificationRequestTemplateFormTest.php` passed 205 tests and 1,352 assertions in 71.10 seconds before the final added regression checks. This covers the Clinic and SaaS Builder, request review, imports, PDF and template lifecycle. The six-case certification run passed 48 assertions. The follow-up for PDF authorization and preview passed 2 tests and 9 assertions.
- **Authorization and isolation.** Automated coverage used authenticated role fixtures and real HTTP or Livewire component calls. Browser coverage showed an assigned manager saving and publishing and a foreign version returning 404. The temporary publishing grants, clinic delegation, and login were restored, and the temporary enrollment was marked inactive. The synthetic inactive published template and its audit event remain.
- **MariaDB concurrency.** Applied `php artisan migrate --force --no-interaction` to the new local `denentiq_tb_qa` schema, then ran `race.php` in the certification scratch directory. MariaDB 10.11.16 returned lock timeout 1205 to the second connection in both execution orders. After the first committed, the second action failed with the expected stale-review or protected-version validation, and row ordering and version status matched the winning action.
- **Historical data and PDFs.** A synthetic clinic request with a matching stored and embedded master reference was read, compared to a clinic refresh target, and rendered as PDF. Its original row stayed equal. Existing baseline coverage checks request snapshots and template refresh generally. No existing request was repaired or refreshed.
- **Accessibility.** Browser accessibility inspection found labeled question and publication controls. Keyboard Enter reopened the review. Escape dismissed it and restored focus to Review & Publish. Full screen-reader, narrow layout, drag-and-drop, loading and validation walkthroughs are pending.
- **Environment.** The managed Windows shell produced unreadable-file errors for synthetic uploads and binary downloads. Its PHP process also lacked ZIP by default. Running Pest directly with the installed ZIP extension and escalated Windows file access resolved those test-environment failures. The repository bootstrap continued to require isolated in-memory SQLite. Local XAMPP was used for the browser. The clean schema migrated successfully with current working files. Both unique indexes were present, removed by an isolated migration rollback, and restored on reapplication. The checksum of the request snapshot columns stayed unchanged in that schema. A clean source checkout still needs to be exercised from the exact reviewed release.
- **Release.** Include the five untracked release files and run the clean-checkout acceptance suite on the intended revision. Before deployment confirm `APP_DEBUG=false`, protected server-side log access, and retention for lifecycle audit events. No commit, push, deployment, or migration of the existing application schema occurred.

### Release acceptance

The delegated positive browser flow, cross-clinic denial, essential two-order publication contention, read-only historical preservation, and bounded history preparation have evidence. Full accessibility, the complete browser permission and import matrix, and a clean release checkout still require sign-off. The full product suite also has failures outside the focused Template Builder cases.

**Final certification — Insufficient Evidence to Certify.** Evidence supports the changes above. The remaining acceptance and delivery work prevents unconditional production sign-off.

## Release verification follow-up — 24 November 2026

### TB-02 delegated browser access

- In Lenovo, the existing synthetic Verification Manager uploaded a Full Form CSV, selected custom mappings for two questions, approved the clinic and saved the draft. **Open Template Builder** opened that exact draft, version 388, in the Verification workspace with QA clinic 15 selected.
- The browser exposed a misdirected sidebar link. The Verification resource inherited the Clinic resource's navigation URL. The delegated resource now overrides its navigation URL in `app/Filament/Admin/Resources/ClinicTemplates/ClinicTemplateResource.php`. The browser confirmed the sidebar points to the Verification Builder. The targeted test in `tests/Feature/Clinic/ManagedServicesSmokeTest.php` passed 1 test with 18 assertions, including the rendered link. This correction has no schema, policy or dependency change.
- Revoking Clinic editing blocked the next action from the already-open page and the version's direct URL with HTTP 403. Revoking publishing after the review opened caused confirmation to return 403. Reload still offered editing and omitted publication. Deactivating the enrollment also returned 403. Assignment detachment is covered by the existing authenticated HTTP test. The earlier browser run covers successful delegated publication and cross-clinic 404.
- Verified that the imported draft stayed unpublished and active forms remained Full 57 and Short 63. The account, temporary grants, delegation flag and enrollment were returned to their original inactive state. The synthetic import draft remains in local QA.
- **TB-02 status — Resolved – Independently Verified.** This closes the positive browser import, exact draft destination and live revocation gaps. Separate browser sessions for every administrative role were outside this pass. The prior administrator and Clinic flows and automated role tests provide those checks.

### Manual accessibility

- At 390 × 844, document width remained within the viewport. The accessibility tree exposed labeled question move buttons, disabled edge actions, and an accessible success heading. Keyboard Enter moved the second question above the first and the displayed order changed. Focus then followed the rendered row position and landed on the neighboring question's move control. Focus tracking across a reorder remains an accessibility improvement.
- The import reported the missing form type and each unconfirmed mapping before enabling approval. The browser showed the upload-in-progress state, exact clinic and form in the approval label, a successful draft notice, and forbidden feedback on revocation. The earlier run verified Escape closes publish review, returns focus to its trigger, and Enter reopens it.
- The Clinic reorder view uses keyboard-operable buttons. A full screen-reader walkthrough and manual SaaS drag-and-drop interaction are still pending. **Accessibility status — Partially Resolved.**

### Test environment and baseline attribution

- The working Lenovo checkout previously returned 276 passing affected-module tests with 1,827 assertions. The last broad product run returned 539 passed and 3 failed, with 3,517 assertions in 103.59 seconds.
- Exported Git revision `7383486` to a separate directory and applied the preserved pre-remediation working patch. Ran `php -d extension=zip -d extension=intl vendor/bin/pest --compact tests/Feature/Clinic/ClinicWorkflowSmokeTest.php tests/Feature/Clinic/SaasAdminClinicEntryTest.php` with in-memory SQLite. The same three failures reproduced before the remediation. The expected patient insurance claims route and patient statement named route were missing. The Clinic workspace chooser returned 302 where the test expected 2xx. Baseline run reported 3 failed, 3 warnings and 19 assertions in 77.37 seconds. No assertions were weakened.

### Isolated release candidate

- Built a separate source export from `7383486` plus a Template Builder file manifest. Included both delegated resource classes, the lifecycle audit logger, both index definitions in the uniqueness migration, and this audit. Used installed locked dependencies and a synthetic test configuration. The live checkout, its staged state, and unrelated changes were preserved. The candidate notes document the scoped hunks in the mixed User and onboarding files. The staged Git snapshot is byte-for-byte identical to the pre-sign-off checkpoint.
- On an empty isolated MariaDB schema, `php artisan migrate --force --no-interaction` installed both unique indexes. `php artisan migrate:rollback --step=1 --force --no-interaction` removed them and reapplying the migration restored them. All three steps passed from the source export. The release export also passed both two-session publication contention orders. Both contenders reached lock timeout 1205, and retries failed with the expected stale or protected-version error.
- The source export is a local verification candidate. The initial scope omitted the exact Clinic import redirect and internal onboarding protections. The revised candidate includes those dependencies and the active-user test factory. The onboarding suite passed 18 tests with 161 assertions. Merge the reviewed patch into the intended release revision, preserving approved branch-specific changes.

The final clean source regression ran 215 tests with 1,374 assertions in 66.12 seconds. All passed. This run used the eight feature files below, isolated in-memory SQLite, and the installed ZIP and Intl PHP extensions.

```powershell
php -d extension=zip -d extension=intl vendor/bin/pest --compact tests/Feature/ClinicSectionBuilderTest.php tests/Feature/ClinicSettingsTemplateEmptyStateTest.php tests/Feature/Clinic/ManagedServicesSmokeTest.php tests/Feature/VerificationTemplateImportTest.php tests/Feature/VerificationRequestTemplateFormTest.php tests/Feature/Saas/ClientManagementTest.php tests/Feature/Clinic/AppointmentVerificationWorkflowTest.php tests/Feature/VerificationRoleAccessMatrixTest.php
```

The 77-path candidate patch was generated against `7383486`. It contains the required Audit, audit logger, active-slot uniqueness migration, and two delegated Builder classes. The reviewed candidate, file manifest, digest manifest and test result are available in the local sign-off artifact. The five files remain untracked in the working checkout until release integration.

### Certification checklist

| Check | Disposition |
|---|---|
| Delegated browser editing, publication and import | Verified, including live revocation and tenant denial |
| Two-connection publication races and draft protection | Verified in the prior pass |
| Historical master snapshot and PDFs | Verified as supported legacy behavior with synthetic data |
| PDF first save and read-only preview | Verified, including two MariaDB connections |
| Clinic history page size, filters and query count | Verified |
| Both active-slot indexes on clean source export | Verified for migration, rollback and reapplication |
| Clean source feature regression | 215 passed with 1,374 assertions |
| Full product suite | Three failures reproduced on the pre-remediation baseline |
| Full manual accessibility | Partial coverage, with focus behavior and manual checks outstanding |
| Intended release integration and deployment approval | Pending |

**Final certification — Insufficient Evidence to Certify.** The delegated release blocker is closed. Complete release integration and accessibility evidence are still required.

### Keyboard reorder focus follow-up

The shared Clinic Builder now tracks reorder rows by question ID and restores focus after a successful move. At the top or bottom, focus goes to the remaining move button on the same question. A move that does nothing emits no focus event.

In the Lenovo browser, Enter moved the synthetic question from last to first and focus followed it to Move down. At a 390-pixel viewport, pressing Enter again moved it back to last and focus followed it to Move up. The original test order and QA access settings were restored. The Clinic Builder suite passed 41 tests with 213 assertions. The earlier reorder focus finding is resolved.

### SaaS edits during publication

Add Section, Confirm Intentionally Empty, and Delete Question now lock and revalidate the exact SaaS master draft before writing. The same guarded deletion is wired to the editor and table. The retained inline Add Question action also locks the draft before inserting. Publication continues to compare the reviewed revision before committing.

Two distinct MariaDB connections in the isolated release schema verified both execution orders for each of the three reported actions. If the edit committed first, publication with the old review was refused. If publication committed first, the edit was refused. In all six cases the competing connection waited on the lock, and the retry left the committed content intact. The SaaS import, revenue operations, and Clinic Builder regression suites passed 134 tests with 857 assertions.
