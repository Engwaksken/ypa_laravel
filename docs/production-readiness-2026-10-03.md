# Production preparation — 3 October 2026

This release hardens the existing Laravel application. The subsequent POS/accounting implementation and current database blocker are tracked in [the follow-up report](pos-accounting-followup-2026-10-03.md). The frontend and backend work was performed directly in `D:\projects\ypa_project\laravel`, preserving the earlier audit changes.

## Changes

- Frontend: project/category request races, edit-target reset, modal loading state, duplicate saves/deletions/imports, and back-navigation recovery fixed. Bootstrap 5.3.3 CSS/JS is bundled locally with its upstream license. Icons remain optional CDN resources.
- Inventory/orders: new stock reservation evidence, UUID-based order numbers, stable lock ordering with deadlock retries, locked state transitions, and exactly-once cancellation release for tracked orders. Untracked historical orders are deliberately not restocked automatically.
- Checkout: inactive/expired/unpriced inventory, excessive cart quantities, duplicate products, and unrelated email-only customer association are rejected or prevented.
- Access control: branch-specific orders, stock, customers, suppliers, and expenses are scoped and authorized for listing and mutation. Unassigned report accounts fail closed.
- Expense integrity: journal writes participate in database transactions. Posting failures roll back the expense rather than silently succeeding. Already-posted expense edits/deletes require an accounting adjustment instead of creating duplicate or orphaned journal entries. Unsupported journal schemas return an explicit error.
- Deployment: trusted proxies are read through cached configuration; trusted hostnames are restricted to APP_URL and explicit aliases. Secure session cookie defaults, finite SMTP timeout, response security headers, private-page no-store, and rotating example log settings added.
- Runtime checks: `php artisan ypa:production-check` is a read-only check of effective configuration, database connectivity, required tables/columns, and pending migrations. It never displays the application key or database/mail credentials.
- Dependency security: `league/commonmark` upgraded from 2.10.0 to 2.10.3 to address the two advisories returned by Composer audit. No Laravel major upgrade was made.

## Required deployment sequence

1. Back up the production database and uploaded files, and verify restoration on staging. Use a staging copy of the real MySQL schema for testing; the SQLite tests do not establish MySQL concurrency behavior.
2. Configure the web server document root to this project's `public` directory. Deny dotfiles and PHP execution in upload directories. Configure HTTPS at the ingress.
3. Configure production values privately: APP_ENV=production, APP_DEBUG=false, the real HTTPS APP_URL, the existing stable APP_KEY, database access, SMTP credentials, and persistent sessions/cache/queues. Never regenerate APP_KEY on an established deployment: encrypted data and sessions depend on it.
4. Set TRUSTED_PROXIES to actual ingress IPs/CIDRs only. Set TRUSTED_HOSTS to any additional exact domains; the APP_URL domain is included automatically. Proxy configuration now survives config caching. Do not use wildcard proxy trust.
5. Install the locked dependencies with `composer install --no-dev --prefer-dist --optimize-autoloader`. The production server requires the PHP/extensions required by composer.lock. Do not run `composer update` on the server.
6. Put the existing deployment into maintenance mode and review `php artisan migrate:status` against the real database. Existing migrations contain assumptions about legacy tables and migration records; inspect them before running all outstanding migrations.
7. Apply the new additive reservation migration before enabling checkout/status/stock mutations with this code:

   ```sh
   php artisan migrate --path=database/migrations/2026_10_03_000001_create_order_stock_reservations_table.php --force
   ```

   This migration retains reservation evidence on rollback. It does not backfill old orders or change their stock balances. Do not run migrate:fresh or destructive down migrations against production.

8. Ensure `storage` and `bootstrap/cache` are writable by the runtime user. Create the public storage link where required by existing uploads. Preserve uploads across releases. Restart queue workers with `php artisan queue:restart` if the deployment uses workers.
9. Build configuration/routes/views on the server using `php artisan optimize`, then run `php artisan ypa:production-check`. Every failure must be investigated before enabling writes. The optional `--skip-database` switch checks configuration only and is not deployment clearance.
10. Test a disposable staging account: OTP delivery and resend, role restrictions, cross-branch denial, forms/modal validation, uploads, checkout, cancellation, stock updates, reports/CSV, logout, and mobile/keyboard interactions. Verify database backups, log retention, `/up` monitoring, and concurrent order/stock requests. Restore service only when the required workflows pass.

## Release gates still requiring attention

- The POS and ledger statement implementation must be validated against the real database; see the follow-up report for current behavior and migrations.
- Historical order inventory and completion postings require reconciliation. Legacy checkout and completion scripts contain inconsistent stock deductions. The new reservation ledger does not guess their balances.
- The production expense ledger schema must be checked. Only the supported journal_entries/journal_entry_lines shape can be posted by this implementation; missing accounting tables allow unposted expenses, and unsupported posting tables cause a clear rejection.
- Actual MySQL schema, SMTP delivery, storage permissions, end-to-end browser behavior, backups/restore, and deployment are unverified in this local run.

## Verification commands

```sh
composer validate --no-check-publish
composer audit --locked
composer test
composer run test:js
php artisan view:cache
php artisan route:cache
php artisan route:clear
php artisan view:clear
```

Developer tests use synthetic records and SQLite memory databases. JavaScript checks isolate handlers with a lightweight DOM fixture. Configuration checks use fake credentials and make no external mail/database writes.

References: [Laravel 12 request host/proxy configuration](https://laravel.com/framework/docs/12.x/requests), [Laravel error/debug configuration](https://laravel.com/framework/docs/12.x/errors), [CommonMark raw-HTML advisory](https://github.com/advisories/GHSA-97jj-33gv-5xf9), [CommonMark table scan advisory](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8).

## Final integrated verification

- PHPUnit: 249 tests passed, 928 assertions (PHP 8.2.12; SQLite memory fixtures).
- JavaScript modal/workflow checks: 15 passed.
- Package discovery, Blade compilation, and route caching succeeded; generated route/view caches were cleared afterward.
- Composer manifest validation passed. CommonMark was updated from 2.10.0 to 2.10.3; Composer reported no security vulnerability advisories after the update.
- Reinstalled the existing locked Flysystem package to remove stale duplicate vendor classes; optimized autoload generation completed without ambiguity warnings.
- Git whitespace checks passed. Existing local work was preserved; no deployment or production database migration was performed.

The order_stock_reservations migration is required before deploying the changed checkout code. Browser visual testing and actual production services remain unverified. Bootstrap modal assets are served locally; icon fonts still depend on their external CDN.
