# By Design Dentals: Hybrid onboarding

One organization, one clinic and Main Office, with Dr. Toral Patel as a separate provider.
The supplied NPI and license belong to the provider, not the clinic. The legal owner is not yet supplied.

## Deployment

- Back up the target database and confirm the target environment.
- Optionally set `BY_DESIGN_DENTALS_TAX_ID` securely on the target server. Never put its value in Git. If absent, provisioning succeeds with a blank tax ID and a pending note; enter the tax ID securely in clinic settings before billing or payer submissions.
- Refresh cached configuration if changing it, then run migrations through the normal protected update process. A supplied but invalid tax ID is rejected.
- The migration requires the existing `clinic_admin` role. It stops on conflicting client/email records rather than taking over another account.
- Reruns preserve existing records and passwords. Rollback deliberately does not delete client or clinical data.
- Production provisioning is skipped in automated test migrations; the explicit seeder is covered by isolated tests.

## Access and shared verification

- Administrator: `developer@medityaglobalservices.com`, display name `By Design Dentals Admin`.
- A random, undisclosed password is generated; use the approved password-reset flow to establish access. No invitation or reset email is sent by this migration.
- Dr. Toral Patel's required linked user is disabled with a reserved `.invalid` email and no roles. Replace it only when actual provider login details are approved.
- Clinic verification and managed verification are enabled. Existing schema represents managed access as `active`; it does not have a separate fully-active Hybrid status. The shared model is recorded in service notes.
- An active verification-service enrollment with clinic workspace access enables both Managed Service and Self-Managed choices. Existing default targets (normal: three days, urgent: 24 hours) are provisional, not agreed contractual SLAs.
- Assign authorized internal verifiers to this clinic explicitly. Provisioning does not grant all staff access automatically.
- Use one assignee per request, shared status/history and deliberate handoffs between teams.
- Clinic PDF default: Custom Landscape. No existing renderer or form template is changed.
- No subscription, invoice, payment, external API request or patient-data import is created.

## Remaining setup

Confirm the legal owner and administrator's actual name; establish the administrator password; assign internal verifiers; agree subscription/usage pricing; select and validate the clinic's verification template before real requests.

## Recovering an update stopped by the earlier tax-ID prerequisite

Restore application access using the System Updates recovery action and the SaaS Admin password. Deploy the corrected seeder, confirm the database backup, and rerun the protected update. The failed migration remains pending and will retry; do not manually mark it completed. The existing local client's tax ID and credentials are preserved.
