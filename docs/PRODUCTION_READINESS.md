# Production Readiness

Run `php artisan prodental:production-check` before every production deployment. Any `FAIL` blocks release.

## Required Deployment Gates

- Clean, reviewed Git commit containing every required tracked file and migration.
- `composer audit --locked` and `npm audit --omit=dev` report no known vulnerabilities.
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
7. Deploy code, run migrations once, restart workers, and clear/rebuild caches.
8. Smoke-test login, tenant scope, verification, appointment, PDF, email, queue, scheduler, and logout.
