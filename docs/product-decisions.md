# ProDental Product Decisions

This file records product-boundary and workflow decisions that should remain stable across UI, backend, routes, testing, and deployment.

## 2026-08-25 - Insurance Directory

- The database model remains `InsuranceCarrier` and its table remains `insurance_carriers`. Database naming is internal and should not be renamed for UI terminology.
- The user-facing module is **Insurance Directory** and its canonical panel route is lowercase `/insurance`.
- Previous `/insurance-carriers` URLs remain as permanent redirects so bookmarks and deployed links do not break.
- SaaS owns the central insurance payer directory. Clinics inherit central records and may use isolated clinic overrides where permitted.
- Patient insurance policies are transactional patient records. Their payer names must not be promoted automatically into the central directory.
- Empty central-directory data is valid. The UI must explain the empty state and provide an intentional **Add Insurance** action.
- SaaS administrators may bulk import the central directory from CSV or XLSX. Imports must be previewable, match existing carriers by payer ID first and insurance name second, and update matches instead of creating duplicates.
- Insurance imports never alter patient policies or clinic overrides. The supported columns are insurance name, payer ID, payer phone, claims address, website, notes, and active status.
- Verification questions belong to the Master Template workflow, not the Insurance Directory.
- The Insurance Directory uses the standard application header and primary sidebar only. It must not add a second settings menu, duplicate title, or Verification Workspace label.

## Shared Application Shell

- SaaS, Verification, Clinic, Organization, and DSO panels use the same AppShell behavior while retaining panel-specific navigation.
- On desktop, the sidebar and its inner navigation must use the same expanded or collapsed width.
- Refreshing a page must keep both the sidebar and header visible. The saved sidebar state may change width, but must not translate the sidebar off-screen.
- Page actions belong in the standard page header. Page content should not duplicate the page title or global navigation.

## Verification Records

- Published templates create stable request snapshots.
- Completed verification forms and generated reports remain tied to their saved snapshot for audit history.
- Later template changes do not mutate completed requests automatically.
