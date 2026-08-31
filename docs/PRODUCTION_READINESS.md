# Production Readiness

Run `php artisan prodental:production-check` before every production deployment. Any `FAIL` blocks release.

## Required Deployment Gates

- Clean, reviewed Git commit containing every required tracked file and migration.
- `composer audit --locked` and `npm audit --omit=dev` report no known vulnerabilities.
- Build frontend assets with `npm ci && npm run build`. For Git-only/shared-hosting deployments, commit `public/build` with the release so the Vite manifest and hashed assets reach the server.
- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, protected `APP_KEY`.
- Public registration closed unless the complete onboarding release is approved.
- Production database, encrypted sessions, secure cookies, shared cache where required.
- Production mail provider tested without a URL scheme in the SMTP host.
- Queue worker supervised continuously and failed jobs monitored.
- Laravel scheduler invoked every minute and monitored for missed runs.
- Private document storage backed up, encrypted, access-controlled, and restore-tested.
- Database backup and point-in-time recovery procedure tested.
- Error monitoring, uptime checks, queue health, scheduler health, and notification delivery health enabled.
- Migration backup and rollback plan approved before release.

## Security And Compliance Operations

Code controls support tenant isolation, role permissions, support-mode auditing, private document access, protected credentials, and immutable verification history. Production operation additionally requires approved BAAs, access reviews, incident response, breach procedures, retention schedules, workforce training, MFA strategy, and evidence of backup restoration tests.

## Release Sequence

1. Freeze the release branch and inventory untracked files.
2. Run focused tests for changed modules.
3. Run Composer and npm security audits.
4. Run the complete automated suite and frontend production build.
5. Run the production check against production-equivalent configuration.
6. Back up the database and private files.
7. Deploy code and compiled frontend assets, run migrations once, restart workers, and clear/rebuild caches.
8. Smoke-test login, tenant scope, verification, appointment, PDF, email, queue, scheduler, and logout.

## Controlled Database Update

The server terminal remains the preferred migration method. Shared-hosting deployments may use the protected SaaS update center described below.

Preview the exact pending migration list without changing the application:

```bash
php artisan prodental:database-update --dry-run
```

After creating and verifying the production database backup, execute the controlled update:

```bash
php artisan prodental:database-update --force --backup-confirmed
```

The command checks production gates, prevents concurrent execution, enables maintenance mode, runs only pending migrations, verifies that none remain, rebuilds Laravel caches, restarts queue workers, and restores availability. If an update fails, maintenance mode remains enabled for investigation and recovery.

Never edit an executed migration or overwrite production with a local SQL export for routine releases. Add a new migration for every database change and preserve the production `APP_KEY` so encrypted records remain readable.

### Shared-hosting update center

When shell access is unavailable, an active SaaS Administrator can open **Settings → System Updates**. The page:

- lists pending migration names without exposing server paths or credentials;
- checks the production environment before allowing an update;
- requires the administrator's current password and confirmation of a verified backup;
- enables maintenance mode while preserving a signed, short-lived bypass for the initiating administrator;
- applies one migration per request to reduce shared-hosting timeout risk;
- rebuilds caches, restarts queue workers, verifies completion, and restores public access;
- stores a small server-local update history under private application storage.

If a step fails, maintenance mode remains active and the page offers an authenticated recovery action. Investigate and correct the failed migration before attempting another update.
