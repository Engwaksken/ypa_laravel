<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDOException;
use Throwable;

class ProductionCheck extends Command
{
    protected $signature = 'ypa:production-check {--skip-database : Check configuration only; database readiness remains unverified}';
    protected $description = 'Check production configuration and database readiness without modifying data';

    public function handle(): int
    {
        $failures = [];
        $check = function (bool $passed, string $label) use (&$failures): void {
            if ($passed) {
                $this->line('[PASS] '.$label);
            } else {
                $failures[] = $label;
                $this->error('[FAIL] '.$label);
            }
        };

        $check(app()->environment('production'), 'APP_ENV is production');
        $check(config('app.debug') === false, 'Debug output is disabled');
        $url = (string) config('app.url');
        $check(parse_url($url, PHP_URL_SCHEME) === 'https' && (bool) parse_url($url, PHP_URL_HOST), 'APP_URL is an HTTPS URL');
        $key = (string) config('app.key');
        $decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $check(is_string($decoded) && strlen($decoded) === 32, 'A valid application encryption key is configured');
        $check((bool) config('session.secure') && (bool) config('session.http_only'), 'Session cookies are secure and HTTP-only');
        $check(in_array(config('session.same_site'), ['lax', 'strict'], true), 'Session cookies use SameSite protection');
        $check(!in_array(config('session.driver'), ['array', 'null'], true), 'Sessions use persistent storage');
        $check(config('mail.default') === 'smtp', 'OTP email uses the SMTP mailer');
        $check((bool) config('mail.mailers.smtp.host') && (bool) config('mail.from.address'), 'SMTP host and sender are configured');
        $check((int) config('mail.mailers.smtp.timeout') > 0, 'SMTP connections have a finite timeout');
        $check(!in_array('*', config('trustedproxy.proxies', []), true), 'Proxy trust is restricted to explicit IPs/CIDRs');
        foreach ([storage_path('framework'), storage_path('logs'), base_path('bootstrap/cache')] as $directory) {
            $check(is_dir($directory) && is_writable($directory), 'Runtime directory is writable: '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $directory));
        }

        if ($this->option('skip-database')) {
            $this->warn('Database checks skipped. This result does not establish database readiness.');
        } else {
            try {
                DB::connection()->select('SELECT 1');
                $check(true, 'Database connection is available');
                $required = [
                    'users', 'role_permissions', 'branches', 'members', 'groups', 'mobilizers',
                    'activities', 'meetings', 'contracts', 'contract_items', 'harvests',
                    'payment_transactions', 'payment_methods', 'receivables', 'expenses',
                    'projects', 'project_categories', 'products', 'stock', 'customers',
                    'suppliers', 'orders', 'order_items', 'order_stock_reservations',
                    'sales', 'sale_items', 'pos_sale_requests',
                    'chart_of_accounts', 'journal_entries', 'ledger_entries',
                    'settings', 'notifications',
                ];
                if (config('session.driver') === 'database') $required[] = config('session.table');
                if (config('cache.default') === 'database') {
                    $required[] = config('cache.stores.database.table', 'cache');
                    $required[] = config('cache.stores.database.lock_table', 'cache_locks');
                }
                if (config('queue.default') === 'database') $required[] = config('queue.connections.database.table', 'jobs');
                foreach (array_unique($required) as $table) {
                    $check(Schema::hasTable($table), 'Required table exists: '.$table);
                }
                foreach ([
                    'users' => ['email', 'password', 'role', 'status', 'verification_code', 'code_expires', 'remember_token'],
                    'stock' => ['product_id', 'branch_id', 'quantity', 'total_stock', 'selling_price'],
                    'order_stock_reservations' => ['order_id', 'stock_id', 'quantity', 'released_at'],
                    'sales' => ['customer_id', 'user_id', 'branch_id', 'invoice_no', 'total_amount', 'balance_amount', 'discount', 'payment_method', 'payment_status', 'partial_amount', 'sale_date'],
                    'sale_items' => ['sale_id', 'product_id', 'quantity', 'price', 'total'],
                    'pos_sale_requests' => ['request_token', 'user_id', 'branch_id', 'sale_id', 'payload_hash'],
                    'chart_of_accounts' => ['id', 'account_code', 'account_name', 'account_type'],
                    'journal_entries' => ['id'],
                    'ledger_entries' => ['journal_id', 'account_id', 'debit', 'credit', 'created_at'],
                    'payment_methods' => ['method_name', 'chart_account_id', 'status'],
                    'payment_transactions' => ['project_id', 'branch_id', 'transaction_date', 'total_amount_paid', 'payment_method_id', 'deleted_at'],
                    'contract_items' => ['contract_id', 'project_id', 'quantity', 'purchase_price', 'created_at'],
                ] as $table => $columns) {
                    if (!Schema::hasTable($table)) continue;
                    foreach ($columns as $column) {
                        $check(Schema::hasColumn($table, $column), 'Required column exists: '.$table.'.'.$column);
                    }
                }
                if (Schema::hasTable('journal_entries') && Schema::hasTable('ledger_entries')) {
                    $check(Schema::hasColumn('journal_entries', 'branch_id'), 'New journals support branch attribution');
                    $check(Schema::hasColumn('ledger_entries', 'branch_id') || Schema::hasColumn('journal_entries', 'branch_id'), 'Ledger statements can be scoped to branches');
                    $check(collect(['transaction_date', 'entry_date', 'journal_date'])->contains(fn ($column) => Schema::hasColumn('journal_entries', $column)), 'Journals have a supported transaction date');
                }
                $migrator = app('migrator');
                $repository = $migrator->getRepository();
                if ($repository->repositoryExists()) {
                    $files = $migrator->getMigrationFiles(array_merge([database_path('migrations')], $migrator->paths()));
                    $pending = array_diff(array_keys($files), $repository->getRan());
                    $check($pending === [], 'All application migrations are recorded as applied');
                    foreach ($pending as $migration) $this->warn('Pending migration: '.$migration);
                } else {
                    $check(false, 'Migration repository exists');
                }
            } catch (Throwable $exception) {
                // Do not print SQL connection strings or exception details that may contain credentials.
                $check(false, 'Database readiness could not be verified; inspect the server configuration privately');
                $this->error($this->databaseFailureMessage($exception));
            }
        }

        $this->warn('This read-only check does not verify SMTP delivery, backups, MySQL concurrency, or legacy feature parity.');
        $this->line(count($failures).' failed check(s).');
        return $failures === [] ? self::SUCCESS : self::FAILURE;
    }

    private function databaseFailureMessage(Throwable $exception): string
    {
        do {
            if ($exception instanceof PDOException) {
                $driverCode = (int) ($exception->errorInfo[1] ?? $exception->getCode());
                return match ($driverCode) {
                    1045, 1698 => 'Database authentication was rejected. Verify the configured account, password, and permitted connection host privately.',
                    1044 => 'The database account cannot access the configured database. Verify its database grants privately.',
                    1049 => 'The configured database does not exist on this server. Verify the intended database name; do not create a replacement for existing business data.',
                    2002, 2003, 2006 => 'The database server is unavailable. Verify the service, host, port, and network access.',
                    default => 'Database inspection failed. Review the schema and server configuration privately.',
                };
            }
            $exception = $exception->getPrevious();
        } while ($exception !== null);

        return 'Database inspection failed. Review the schema and server configuration privately.';
    }
}
