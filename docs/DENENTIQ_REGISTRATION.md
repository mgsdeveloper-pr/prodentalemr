# Denentiq registration and plan setup

## What is implemented

The independently hosted website sends registration requests through its PHP relay to the app. The app owns the registration, existing onboarding draft, plans and review status. There is no second customer database on the website.

1. The visitor enters name, work email, clinic, practice structure and offering. This first submitted step immediately creates a draft in the app, even if email delivery fails. Text not yet submitted on this first screen is not saved.
2. The app emails a single-use link valid for 30 minutes. A fresh link can be requested with the same email. Existing records are not overwritten by an unverified visitor.
3. Following the link verifies the email and starts a 12-hour registration session. The token is retained only in the website's server-side session. The email link fragment is removed from browser history before requests are made.
4. Practice fields save automatically after typing pauses. Save progress is also available. A visible error means the latest change has not saved. Previously saved data remains available via a fresh email link on another device.

The website uses the app's existing US country, state, city and time-zone choices. The app currently has a limited city list; applicants whose city is missing should contact the team. Extending geography should be done in the shared app option sources.
5. The applicant chooses a published plan for the offering selected at the start. The app stores a snapshot of the offer and rejects submission if the plan changed or became unavailable. Offering changes currently require staff assistance.
6. Submit for review locks customer editing. Staff see incomplete, unverified, submitted, under-review and activated records under SaaS > Client Management > Website Registrations. Staff can reopen a submitted/under-review record for customer changes. That action does not send email; the customer requests a new link from the website.
7. Review in onboarding opens the existing organization or DSO wizard with saved details. Staff complete remaining setup and approve activation there. Review locks customer editing. The existing wizard's draft is preserved across interactions; website activation and draft completion are saved together to prevent repeated provisioning.

Login remains https://app.denentiq.com. Submitted registrations can now continue to first-period checkout; see [DENENTIQ_CHECKOUT.md](DENENTIQ_CHECKOUT.md). Public requests do not provision accounts or automatically sign users in. The existing staff onboarding process controls account creation and access instructions.

## Create different plans

Open SaaS > Billing > Subscription Plans > Create. The existing plan creation/edit page now contains Website Plan Options.

- Choose Verification Platform or Managed Verification — Platform Included. Existing hybrid plans and registrations retain their saved identifiers and display the managed offering label.
- Set the name, public description, price and currency. Use Request a quote for negotiated pricing; the underlying required price can be zero for a quote-only offer.
- Choose monthly, yearly, one-time, or custom agreement. These are offer terms; automated recurring billing is not part of this change.
- Set clinic/user limits, monthly verifications and included features. Use the public description to explain service scope. Configure managed-service eligibility consistently with the offering.
- Set plan type to Verification Module, routing to Verification, trial days to zero, and Active on.
- Enable Publish on website. Nothing is published automatically and no sample prices are inserted into your database.

For example, create Platform Starter and Platform Team under Verification Platform, and Managed Verification under Managed Verification — Platform Included. Choose actual prices and service commitments before publishing. A pricing card describes the offer; existing app billing must be reviewed separately before charging in a new currency or interval.

## cPanel deployment without SSH

1. Back up the app files and database using the normal hosting tools. Upload the app changes as a normal release. Do not replace `.env` with the example file.
2. Apply pending migrations with the app's existing System Updates screen. This release adds `website_registrations`, permits website drafts without a user account, and adds public-plan columns. The migration is forward-only and does not remove existing customer data. Review all pending migrations listed by that screen before running the release.
3. In the app's private `.env`, set `WEBSITE_REGISTRATION_ENABLED=true`, `WEBSITE_REGISTRATION_SECRET` to a random secret of at least 32 characters, and `WEBSITE_REGISTRATION_RESUME_URL=https://denentiq.com/registration`. Use the actual website domain if different. Refresh the app's cached configuration through the existing deployment/update process.
4. Configure the app's real outgoing mail transport and sender. Log/array mailers are rejected for resume emails. Email delivery uses the app mailer; the separate pricing-enquiry form still uses the website PHP mail handler.
5. Upload the standalone website `dist` contents to the Denentiq domain's document root using File Manager. The website needs PHP 8.1+, sessions, cURL, HTTPS, and permission to make outbound HTTPS requests to the app.
6. Create `config.local.php` one directory ABOVE the website document root using `config.example.php` as a template. Set `app_api_url` to `https://app.denentiq.com` and `registration_bridge_secret` to the exact same private secret as the app. Keep this file outside public access; upload only `dist` publicly. A private writable rate-limit directory is recommended. Do not place either app `.env` or this configuration in the public ZIP.
7. Publish at least one appropriate plan. Check the pricing page and complete a real registration on HTTPS: receive email, resume in a new browser, save partial details, select a plan, submit, locate the draft in the SaaS panel, reopen it and resume again. Verify that unsubmitted and unverified drafts also appear to staff. No live inbox delivery or production hosting has been verified by the local tests.

The website gateway is disabled by default until this setup is complete. Local feature tests use an isolated in-memory database; they do not migrate the development or production database.

## Payment checkout

Stripe and PayPal adapters now connect to a shared first-period checkout layer with verified callbacks, saved payment attempts and reconciliation. Additional providers can be registered through the same interface. See [DENENTIQ_CHECKOUT.md](DENENTIQ_CHECKOUT.md) for setup and limits. Automatic recurring payments are not created.
