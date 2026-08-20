# Testing Database Safety Report

Version: 1.0
Status: Implemented
Owner: Product Engineering
Date: 2026-08-06

---

## Problem

Local development users were disappearing after test runs.

## Root Cause

Feature tests use Laravel `RefreshDatabase` through `tests/Pest.php`.

`phpunit.xml` correctly defines the test database as SQLite in-memory, but `bootstrap/cache/config.php` can contain stale local configuration pointing to MySQL database `prodentalemr`.

When tests run with cached local MySQL configuration, `RefreshDatabase` can refresh the development database and remove local working data, including login users.

## Fix

- Added `tests/bootstrap.php` as the PHPUnit bootstrap file.
- Updated `phpunit.xml` to use `tests/bootstrap.php`.
- Added a pre-test cached-config guard that blocks tests when cached config is not isolated SQLite `:memory:`.
- Added a runtime guard in `tests/TestCase.php` that blocks tests when Laravel boots with a non-isolated test database.
- Cleared stale cached configuration with `php artisan optimize:clear`.

## Files Modified

- `phpunit.xml`
- `tests/TestCase.php`

## Files Created

- `tests/bootstrap.php`
- `database/seeders/LocalAdminUserSeeder.php`
- `docs/testing-database-safety-report.md`

## Local Admin Recovery

If local login data is missing, restore the development admin user with:

`php artisan db:seed --class=LocalAdminUserSeeder`

This seeder is idempotent and targets only `admin@mgs.com`.

## Safe Validation

- `php artisan optimize:clear`: passed.
- `php artisan test tests\Feature\AppShellFoundationTest.php`: passed using SQLite in-memory.

## Policy

Do not run broad `php artisan test` unless the active testing database is confirmed isolated.

Do not run destructive database reset commands.

---

END OF DOCUMENT
