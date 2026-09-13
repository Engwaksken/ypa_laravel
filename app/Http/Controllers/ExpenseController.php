<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Harvest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public const HARVEST_CATEGORIES = [
        'Goat Harvest Payout',
        'Honey Harvest Payout',
        'Venom Harvest Payout',
        'Maize Harvest Payout',
        'CDC Withdrawal',
        'Cash Withdrawal',
        'Harvest Payout',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('user.status'),
            new Middleware('permission:expenses_view'),
            (new Middleware('permission:expenses_manage'))->only(['store', 'update']),
            (new Middleware('permission:delete_expenses'))->only(['destroy']),
            (new Middleware('permission:expenses_import'))->only(['import']),
            (new Middleware('throttle:30,1'))->only(['store', 'update', 'destroy', 'import']),
        ];
    }

    public function index(Request $request): View
    {
        $permission = app(\App\Services\PermissionService::class);
        $user = auth()->user();

        $canCreate = $permission->canAny(['create_expenses', 'manage_expenses']);
        $canEdit = $permission->canAny(['edit_expenses', 'manage_expenses']);
        $canDelete = $permission->can('delete_expenses');
        $canExport = $permission->canAny(['export_expenses', 'manage_expenses']);
        $canImport = $permission->can('expenses_import');
        $canAllBranches = $permission->canAny(['all_branches', 'view_all_branches']);

        $branches = Branch::query()->orderBy('name')->get(['id', 'name']);

        $requestedBranch = (int) $request->query('branch', 0);
        $selectedBranch = $canAllBranches
            ? max(0, $requestedBranch)
            : (int) ($user->branch_id ?? 1);

        $branchName = 'All Branches';
        if ($selectedBranch === 0) {
            $branchName = 'All Branches';
        } else {
            $branchName = $branches->firstWhere('id', $selectedBranch)->name ?? 'Unknown Branch';
        }
        $formBranchId = $selectedBranch > 0 ? $selectedBranch : (int) ($user->branch_id ?? 1);

        $search = trim((string) $request->query('search', ''));
        $categoryFilter = trim((string) $request->query('category', ''));
        $methodFilter = trim((string) $request->query('payment_method', ''));
        $sourceFilter = trim((string) $request->query('source', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $perPage = (int) $request->query('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $includeManual = ($sourceFilter === '' || $sourceFilter === 'manual');
        $includeHarvest = ($sourceFilter === '' || $sourceFilter === 'harvest');

        $rows = [];

        if ($includeManual && Schema::hasTable('expenses')) {
            $rows = array_merge($rows, $this->manualRows($request));
        }

        if ($includeHarvest && Schema::hasTable('harvests')) {
            $rows = array_merge($rows, $this->harvestRows());
        }

        // Apply PHP-side filters shared by both sources (branch is only
        // applied above when it can cheaply be pushed to the queries; the
        // remaining filters mirror the legacy expenses.php union).
        $rows = array_filter($rows, function (array $row) use ($selectedBranch, $search, $categoryFilter, $methodFilter, $dateFrom, $dateTo) {
            if ($selectedBranch > 0 && (int) ($row['branch_id'] ?? 0) !== $selectedBranch) {
                return false;
            }

            if ($categoryFilter !== '' && $row['category'] !== $categoryFilter) {
                return false;
            }

            if ($methodFilter !== '' && $row['payment_method'] !== $methodFilter) {
                return false;
            }

            if ($dateFrom !== '' && $row['record_date'] < $dateFrom) {
                return false;
            }

            if ($dateTo !== '' && $row['record_date'] > $dateTo) {
                return false;
            }

            if ($search !== '' && !$this->rowMatchesSearch($row, $search)) {
                return false;
            }

            return true;
        });

        $rows = array_values($rows);

        // Sort: record_date DESC, created_at DESC, source_id DESC.
        usort($rows, function (array $a, array $b) {
            return [$b['record_date'], $b['created_at'], $b['source_id']]
                <=> [$a['record_date'], $a['created_at'], $a['source_id']];
        });

        $page = max(1, (int) $request->query('page', 1));
        $total = count($rows);
        $offset = ($page - 1) * $perPage;
        $paginator = new LengthAwarePaginator(
            array_slice($rows, $offset, $perPage),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => collect($request->query())->forget('page')->all(),
            ]
        );

        $stats = $this->stats($selectedBranch);

        return view('expenses.index', compact(
            'paginator',
            'rows',
            'total',
            'search',
            'categoryFilter',
            'methodFilter',
            'sourceFilter',
            'dateFrom',
            'dateTo',
            'perPage',
            'selectedBranch',
            'branchName',
            'formBranchId',
            'branches',
            'stats',
            'canCreate',
            'canEdit',
            'canDelete',
            'canExport',
            'canImport',
            'canAllBranches'
        ));
    }

    public function store(StoreExpenseRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['category'] = $request->resolvedCategory();
        $data['debit_account_code'] = $data['debit_account_code'] ?? '5000';
        $data['credit_account_code'] = $data['credit_account_code'] ?? '1000';
        $data['created_by'] = auth()->id();

        $expense = Expense::create($data);
        $this->postJournal($expense);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense '{$expense->title}' added successfully.",
                'expense_id' => $expense->id,
            ], 201);
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', "Expense '{$expense->title}' added successfully.");
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['category'] = $request->resolvedCategory();
        $data['debit_account_code'] = $data['debit_account_code'] ?? '5000';
        $data['credit_account_code'] = $data['credit_account_code'] ?? '1000';
        $data['updated_by'] = auth()->id();

        $expense->update($data);
        $this->postJournal($expense);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense '{$expense->title}' updated successfully.",
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', "Expense '{$expense->title}' updated successfully.");
    }

    public function destroy(Request $request, Expense $expense): JsonResponse|RedirectResponse
    {
        $title = $expense->title;
        $expense->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense '{$title}' deleted successfully.",
            ]);
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', "Expense '{$title}' deleted successfully.");
    }

    /**
     * CSV export mirroring expenses.php expense_export_rows(). Follows the
     * index page filters; a streamed CSV is returned with a sanitised header.
     */
    public function export(Request $request)
    {
        if (!app(\App\Services\PermissionService::class)->canAny(['export_expenses', 'manage_expenses'])) {
            abort(403, 'You do not have permission to export expenses.');
        }

        $rows = $this->filteredRows($request);
        $columns = [
            'Branch' => 'Branch',
            'Date' => 'Date',
            'Title' => 'Title',
            'Description' => 'Description',
            'Source' => 'Source',
            'Category' => 'Category',
            'Owner' => 'Owner / Contract',
            'Payment Method' => 'Payment Method',
            'Payment Ref' => 'Payment Ref',
            'Status' => 'Status',
            'Gross Amount' => 'Gross Amount',
            'Total Fees' => 'Total Fees',
            'Maintenance Fee' => 'Maintenance Fee',
            'Net Amount' => 'Net Amount',
            'Balance Amount' => 'Balance Amount',
            'Posting Status' => 'Posting Status',
            'Debit Account' => 'Debit Account',
            'Credit Account' => 'Credit Account',
            'Journal Ref' => 'Journal Ref',
        ];

        $filename = 'expenses_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($out, array_values($columns));

            foreach ($rows as $row) {
                $export = $this->exportRow($row);
                fputcsv($out, $export);
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * CSV template download for the bulk upload modal (mirrors
     * expenses_template.php column set).
     */
    public function template(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'expenses_import_template_' . now()->format('Y-m-d') . '.csv';

        $callback = function () {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $comment = [
                '# YPA expense import template',
                '# - title: Expense title or description (required)',
                '# - amount: Expense amount in UGX (required, numbers only, no commas or currency symbols)',
                '# - expense_date: Date of expense (required, format: YYYY-MM-DD)',
                '# - category: Expense category (optional)',
                '# - payment_method: Cash, Mobile Money, Bank, Cheque or Other (optional, default Cash)',
                '# - description: Extra notes (optional)',
                '# - branch_name: Branch name (optional; the branch selected on the form is used by default)',
                '# - Rows starting with # are skipped.',
                '',
            ];

            foreach ($comment as $line) {
                fputcsv($out, [$line]);
            }

            fputcsv($out, ['title', 'amount', 'expense_date', 'category', 'payment_method', 'description', 'branch_name']);
            fputcsv($out, ['Office stationery', '25000', '2026-09-01', 'Supplies', 'Cash', 'Pens, paper and folders', 'Kampala']);

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * AJAX bulk upload mirroring process_bulk_expenses_upload.php.
     */
    public function import(Request $request): JsonResponse
    {
        if (!$request->hasFile('csv_file')) {
            return response()->json(['success' => false, 'message' => 'No file uploaded.'], 422);
        }

        $file = $request->file('csv_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'csv') {
            return response()->json(['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.'], 422);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'File size exceeds 5MB limit.'], 422);
        }

        $defaultBranchId = (int) $request->input('branch_id', auth()->user()->branch_id ?? 1);
        if (!Branch::find($defaultBranchId)) {
            return response()->json(['success' => false, 'message' => 'Invalid branch selected.'], 422);
        }

        $branchMap = [];
        foreach (Branch::query()->pluck('id', 'name') as $name => $id) {
            $branchMap[strtolower(trim((string) $name))] = (int) $id;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        try {
            $handle = fopen($file->getRealPath(), 'r');
            if (!$handle) {
                return response()->json(['success' => false, 'message' => 'Unable to read CSV file.'], 422);
            }

            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                return response()->json(['success' => false, 'message' => 'CSV file is empty or invalid.'], 422);
            }

            $headers = array_map(fn ($header) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $header))), $headers);

            $required = ['title', 'amount', 'expense_date'];
            $missing = array_values(array_diff($required, $headers));
            if ($missing) {
                fclose($handle);
                return response()->json(['success' => false, 'message' => 'Missing required columns: ' . implode(', ', $missing)], 422);
            }

            $idx = array_flip($headers);
            $hasBranchName = isset($idx['branch_name']);
            $hasBranchId = isset($idx['branch_id']);
            $rowNumber = 1;

            while (($data = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (empty(array_filter($data))) {
                    continue;
                }
                if (isset($data[0]) && str_starts_with(trim((string) $data[0]), '#')) {
                    continue;
                }

                try {
                    $branchId = $defaultBranchId;

                    if ($hasBranchName && !empty(trim((string) ($data[$idx['branch_name']] ?? '')))) {
                        $branchName = strtolower(trim((string) $data[$idx['branch_name']]));
                        if (isset($branchMap[$branchName])) {
                            $branchId = $branchMap[$branchName];
                        } else {
                            $errors[] = "Row $rowNumber: Invalid branch name '{$data[$idx['branch_name']]}' - using default branch";
                        }
                    } elseif ($hasBranchId && !empty(trim((string) ($data[$idx['branch_id']] ?? '')))) {
                        $branchId = (int) trim((string) $data[$idx['branch_id']]);
                        if (!Branch::find($branchId)) {
                            $errors[] = "Row $rowNumber: Invalid branch ID '{$data[$idx['branch_id']]}' - using default branch";
                            $branchId = $defaultBranchId;
                        }
                    }

                    $title = trim((string) ($data[$idx['title']] ?? ''));
                    $amountRaw = trim((string) ($data[$idx['amount']] ?? ''));
                    $expenseDate = trim((string) ($data[$idx['expense_date']] ?? ''));
                    $category = isset($idx['category']) ? trim((string) ($data[$idx['category']] ?? '')) : '';
                    $paymentMethod = isset($idx['payment_method']) ? trim((string) ($data[$idx['payment_method']] ?? '')) : 'Cash';
                    $description = isset($idx['description']) ? trim((string) ($data[$idx['description']] ?? '')) : '';

                    if ($title === '') {
                        $errors[] = "Row $rowNumber: Title is required";
                        $skipped++;
                        continue;
                    }

                    $amount = (float) preg_replace('/[^0-9.]/', '', $amountRaw);
                    if ($amountRaw === '' || $amount <= 0) {
                        $errors[] = "Row $rowNumber: Valid amount is required (numbers only, no currency symbols)";
                        $skipped++;
                        continue;
                    }

                    if ($expenseDate === '' || !$this->validDate($expenseDate)) {
                        $errors[] = "Row $rowNumber: Invalid date format (use YYYY-MM-DD, e.g., 2026-09-01)";
                        $skipped++;
                        continue;
                    }

                    $paymentMethod = in_array($paymentMethod, StoreExpenseRequest::PAYMENT_METHODS, true) ? $paymentMethod : 'Cash';

                    Expense::create([
                        'title' => $title,
                        'amount' => $amount,
                        'expense_date' => $expenseDate,
                        'category' => $category ?: null,
                        'payment_method' => $paymentMethod,
                        'description' => $description ?: null,
                        'branch_id' => $branchId,
                        'created_by' => auth()->id(),
                    ]);

                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = "Row $rowNumber: " . $e->getMessage();
                    $skipped++;
                }
            }

            fclose($handle);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if ($imported > 0) {
            $message = $imported . ' expense(s) imported successfully';
            if ($skipped > 0) {
                $message .= ', ' . $skipped . ' row(s) skipped due to errors';
            }
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No expenses were imported. Please check the errors below.',
            'imported' => 0,
            'skipped' => $skipped,
            'errors' => $errors,
        ], 422);
    }

    /* ---------------------------------------------------------------
       Row building
    --------------------------------------------------------------- */

    protected function manualRows(Request $request): array
    {
        $accountNames = $this->accountNameMap();
        $journalRefs = $this->journalRefMap();

        return Expense::query()
            ->with('branch')
            ->get()
            ->map(function (Expense $expense) use ($accountNames, $journalRefs) {
                $amount = (float) $expense->amount;
                $date = $expense->expense_date?->format('Y-m-d') ?? '';

                return [
                    'row_key' => "manual-{$expense->id}",
                    'source_type' => 'manual',
                    'source_id' => $expense->id,
                    'branch_id' => (int) $expense->branch_id,
                    'branch_name' => $expense->branch->name ?? '',
                    'record_date' => $date,
                    'created_at' => $expense->created_at?->format('Y-m-d H:i:s') ?? '',
                    'title' => (string) $expense->title,
                    'category' => (string) $expense->category,
                    'description' => (string) ($expense->description ?? ''),
                    'payment_method' => (string) ($expense->payment_method ?? ''),
                    'gross_amount' => $amount,
                    'total_fees' => 0.0,
                    'maintenance_fee' => 0.0,
                    'net_amount' => $amount,
                    'balance_amount' => 0.0,
                    'owner_name' => '',
                    'owner_type' => '',
                    'contract_number' => '',
                    'harvest_status' => '',
                    'payment_reference' => '',
                    'debit_account_code' => (string) ($expense->debit_account_code ?? ''),
                    'credit_account_code' => (string) ($expense->credit_account_code ?? ''),
                    'debit_account_name' => $accountNames[$expense->debit_account_code ?? ''] ?? '',
                    'credit_account_name' => $accountNames[$expense->credit_account_code ?? ''] ?? '',
                    'journal_id' => $expense->journal_id ? (int) $expense->journal_id : 0,
                    'journal_reference' => $expense->journal_id ? ($journalRefs[$expense->journal_id] ?? '') : '',
                ];
            })
            ->all();
    }

    protected function harvestRows(): array
    {
        $accountNames = $this->accountNameMap();
        $journalRefs = $this->journalRefMap();

        return Harvest::query()
            ->with(['branch', 'member', 'group', 'contract'])
            ->whereRaw("(LOWER(COALESCE(`status`, '')) = 'paid' OR LOWER(COALESCE(`approval_stage`, '')) = 'paid')")
            ->get()
            ->map(function (Harvest $harvest) use ($accountNames, $journalRefs) {
                $category = $this->harvestCategory($harvest);
                $recordDate = $harvest->paid_at
                    ?? $harvest->approved_at
                    ?? $harvest->harvest_date
                    ?? $harvest->created_at;

                $recordDate = $recordDate instanceof \DateTimeInterface
                    ? $recordDate->format('Y-m-d')
                    : substr((string) $recordDate, 0, 10);

                $amount = (float) ($harvest->amount_harvested ?? $harvest->equivalent_ugx ?? $harvest->net_amount ?? 0);

                $ownerName = '';
                if (strtolower((string) $harvest->owner_type) === 'group') {
                    $ownerName = (string) ($harvest->group->group_name ?? '');
                } else {
                    $ownerName = trim(($harvest->member->first_name ?? '') . ' ' . ($harvest->member->last_name ?? ''));
                }

                return [
                    'row_key' => "harvest-{$harvest->id}",
                    'source_type' => 'harvest',
                    'source_id' => $harvest->id,
                    'branch_id' => (int) ($harvest->branch_id ?: 1),
                    'branch_name' => $harvest->branch->name ?? 'Unknown Branch',
                    'record_date' => $recordDate,
                    'created_at' => $harvest->created_at?->format('Y-m-d H:i:s') ?? '',
                    'title' => $category,
                    'category' => $category,
                    'description' => (string) ($harvest->notes ?? ''),
                    'payment_method' => (string) ($harvest->payment_method ?? '-'),
                    'gross_amount' => $amount,
                    'total_fees' => (float) ($harvest->total_fees ?? 0),
                    'maintenance_fee' => (float) ($harvest->maintenance_fee ?? 0),
                    'net_amount' => (float) ($harvest->net_amount ?? $harvest->amount_harvested ?? $harvest->equivalent_ugx ?? 0),
                    'balance_amount' => (float) ($harvest->balance_amount ?? 0),
                    'owner_name' => $ownerName,
                    'owner_type' => (string) ($harvest->owner_type ?? ''),
                    'contract_number' => (string) ($harvest->contract->contract_number ?? ''),
                    'harvest_status' => (string) ($harvest->status ?? ''),
                    'payment_reference' => (string) ($harvest->payment_reference ?? ''),
                    'debit_account_code' => (string) ($harvest->debit_account_code ?? ''),
                    'credit_account_code' => (string) ($harvest->credit_account_code ?? ''),
                    'debit_account_name' => $accountNames[$harvest->debit_account_code ?? ''] ?? '',
                    'credit_account_name' => $accountNames[$harvest->credit_account_code ?? ''] ?? '',
                    'journal_id' => $harvest->journal_id ? (int) $harvest->journal_id : 0,
                    'journal_reference' => $harvest->journal_id ? ($journalRefs[$harvest->journal_id] ?? '') : '',
                ];
            })
            ->all();
    }

    protected function harvestCategory(Harvest $harvest): string
    {
        $haystack = strtolower(implode(' ', array_filter([
            (string) ($harvest->harvest_type ?? ''),
            (string) ($harvest->project_kind ?? ''),
            (string) ($harvest->bee_sub_type ?? ''),
            (string) ($harvest->project_name_snap ?? ''),
        ])));

        if (str_contains($haystack, 'cdc')) {
            return 'CDC Withdrawal';
        }
        if (str_contains($haystack, 'goat')) {
            return 'Goat Harvest Payout';
        }
        if (str_contains($haystack, 'honey')) {
            return 'Honey Harvest Payout';
        }
        if (str_contains($haystack, 'venom')) {
            return 'Venom Harvest Payout';
        }
        if (str_contains($haystack, 'maize')) {
            return 'Maize Harvest Payout';
        }
        if (str_contains(strtolower((string) ($harvest->harvest_mode ?? '')), 'cash')) {
            return 'Cash Withdrawal';
        }

        return 'Harvest Payout';
    }

    protected function rowMatchesSearch(array $row, string $search): bool
    {
        $search = strtolower($search);

        $fields = [
            $row['title'],
            $row['category'],
            $row['description'],
            $row['payment_method'],
            $row['payment_reference'],
            $row['owner_name'],
            $row['contract_number'],
        ];

        if (($row['source_type'] ?? '') === 'harvest') {
            $fields[] = $row['harvest_status'];
            $fields[] = $row['branch_name'];
        }

        foreach ($fields as $field) {
            if ($field !== '' && str_contains(strtolower((string) $field), $search)) {
                return true;
            }
        }

        return false;
    }

    protected function accountNameMap(): array
    {
        if (!Schema::hasTable('chart_of_accounts') || !Schema::hasColumns('chart_of_accounts', ['account_code', 'account_name'])) {
            return [];
        }

        return DB::table('chart_of_accounts')->pluck('account_name', 'account_code')->all();
    }

    protected function journalRefMap(): array
    {
        if (!Schema::hasTable('journal_entries') || !Schema::hasColumns('journal_entries', ['reference_no'])) {
            return [];
        }

        return DB::table('journal_entries')->pluck('reference_no', 'id')->all();
    }

    protected function validDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1
            && (bool) \DateTime::createFromFormat('Y-m-d', $date);
    }

    protected function filteredRows(Request $request): array
    {
        // Reuse the same filter pipeline as index() by temporarily building a
        // synthetic "page" query identical to the index filters.
        $source = trim((string) $request->query('source', ''));
        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $method = trim((string) $request->query('payment_method', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $requestedBranch = (int) $request->query('branch', 0);

        $permission = app(\App\Services\PermissionService::class);
        $canAllBranches = $permission->canAny(['all_branches', 'view_all_branches']);
        $selectedBranch = $canAllBranches
            ? max(0, $requestedBranch)
            : (int) (auth()->user()->branch_id ?? 1);

        $rows = [];
        if (($source === '' || $source === 'manual') && Schema::hasTable('expenses')) {
            $rows = array_merge($rows, $this->manualRows($request));
        }
        if (($source === '' || $source === 'harvest') && Schema::hasTable('harvests')) {
            $rows = array_merge($rows, $this->harvestRows());
        }

        $rows = array_values(array_filter($rows, function (array $row) use ($selectedBranch, $search, $category, $method, $dateFrom, $dateTo) {
            if ($selectedBranch > 0 && (int) ($row['branch_id'] ?? 0) !== $selectedBranch) {
                return false;
            }
            if ($search !== '' && !$this->rowMatchesSearch($row, $search)) {
                return false;
            }
            if ($category !== '' && $row['category'] !== $category) {
                return false;
            }
            if ($method !== '' && $row['payment_method'] !== $method) {
                return false;
            }
            if ($dateFrom !== '' && $row['record_date'] < $dateFrom) {
                return false;
            }
            if ($dateTo !== '' && $row['record_date'] > $dateTo) {
                return false;
            }
            return true;
        }));

        usort($rows, function (array $a, array $b) {
            return [$b['record_date'], $b['created_at'], $b['source_id']]
                <=> [$a['record_date'], $a['created_at'], $a['source_id']];
        });

        return $rows;
    }

    protected function exportRow(array $row): array
    {
        $owner = '-';
        if (($row['source_type'] ?? '') === 'harvest') {
            $ownerName = trim((string) $row['owner_name']);
            $ownerType = trim((string) $row['owner_type']);
            $contract = trim((string) $row['contract_number']);
            $owner = trim($ownerName . ($ownerType !== '' ? ' (' . ucfirst($ownerType) . ')' : '') . ($contract !== '' ? ' - Contract: ' . $contract : ''));
            if ($owner === '') {
                $owner = '-';
            }
        } else {
            $owner = 'Manual operational expense';
        }

        $posted = !empty($row['journal_id']) ? 'Posted' : 'Not Posted';
        $debit = trim((string) $row['debit_account_code'] . ' ' . (string) $row['debit_account_name']);
        $credit = trim((string) $row['credit_account_code'] . ' ' . (string) $row['credit_account_name']);

        return [
            $this->sanitize($row['branch_name'] ?? ''),
            $row['record_date'] ?? '',
            $this->sanitize($row['title'] ?? ''),
            $this->sanitize($row['description'] ?? ''),
            $this->sanitize($this->sourceLabel($row['source_type'] ?? '')),
            $this->sanitize($row['category'] ?? ''),
            $this->sanitize($owner),
            $this->sanitize($row['payment_method'] ?? ''),
            $this->sanitize($row['payment_reference'] ?? ''),
            $this->sanitize($row['harvest_status'] ?? ''),
            $this->moneyExport((float) $row['gross_amount']),
            $this->moneyExport((float) $row['total_fees']),
            $this->moneyExport((float) $row['maintenance_fee']),
            $this->moneyExport((float) $row['net_amount']),
            $this->moneyExport((float) $row['balance_amount']),
            $posted,
            $this->sanitize($debit),
            $this->sanitize($credit),
            $this->sanitize($row['journal_reference'] ?? ''),
        ];
    }

    protected function moneyExport(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }

    protected function sourceLabel(string $source): string
    {
        return match ($source) {
            'manual' => 'Manual Expense',
            'harvest' => 'Harvest Liability',
            default => ucwords(str_replace('_', ' ', $source)),
        };
    }

    protected function sanitize(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }

        return $value;
    }

    protected function stats(int $selectedBranch): array
    {
        $today = Carbon::today();
        $now = Carbon::now();

        $todayCount = 0;
        $todayTotal = 0.0;
        $monthCount = 0;
        $monthTotal = 0.0;
        $yearTotal = 0.0;
        $manualTotal = 0.0;
        $postedCount = 0;
        $harvestTotal = 0.0;
        $pendingHarvest = 0.0;

        if (Schema::hasTable('expenses')) {
            $query = fn ($q) => $selectedBranch > 0 ? $q->where('branch_id', $selectedBranch) : $q;

            $todayCount = Expense::query()->tap($query)->whereDate('expense_date', $today)->count();
            $todayTotal = (float) Expense::query()->tap($query)->whereDate('expense_date', $today)->sum('amount');
            $monthCount = Expense::query()->tap($query)->whereYear('expense_date', $now->year)->whereMonth('expense_date', $now->month)->count();
            $monthTotal = (float) Expense::query()->tap($query)->whereYear('expense_date', $now->year)->whereMonth('expense_date', $now->month)->sum('amount');
            $yearTotal = (float) Expense::query()->tap($query)->whereYear('expense_date', $now->year)->sum('amount');
            $manualTotal = (float) Expense::query()->tap($query)->sum('amount');
            $postedCount = Expense::query()->tap($query)->whereNotNull('journal_id')->where('journal_id', '>', 0)->count();
        }

        if (Schema::hasTable('harvests')) {
            $hq = fn ($q) => $selectedBranch > 0 ? $q->where('branch_id', $selectedBranch) : $q;

            $harvestTotal = (float) Harvest::query()
                ->tap($hq)
                ->whereRaw("(LOWER(COALESCE(`status`, '')) = 'paid' OR LOWER(COALESCE(`approval_stage`, '')) = 'paid')")
                ->get()
                ->sum(fn (Harvest $h) => (float) ($h->net_amount ?? $h->amount_harvested ?? $h->equivalent_ugx ?? 0));

            $pendingHarvest = (float) Harvest::query()
                ->tap($hq)
                ->whereRaw("LOWER(COALESCE(`status`, '')) NOT IN ('paid','rejected') AND LOWER(COALESCE(`approval_stage`, '')) <> 'paid'")
                ->get()
                ->sum(fn (Harvest $h) => (float) ($h->net_amount ?? $h->amount_harvested ?? $h->equivalent_ugx ?? 0));
        }

        return [
            'today_count' => $todayCount,
            'today_total' => $todayTotal,
            'month_count' => $monthCount,
            'month_total' => $monthTotal,
            'year_total' => $yearTotal,
            'manual_total' => $manualTotal,
            'posted_count' => $postedCount,
            'harvest_total' => $harvestTotal,
            'pending_harvest' => $pendingHarvest,
        ];
    }

    /**
     * Mirror pe_post_journal(): when the journaling tables exist, post a
     * journal entry for a manual expense and store the journal_id back.
     */
    protected function postJournal(Expense $expense): void
    {
        if (!Schema::hasTable('journal_entries')) {
            return;
        }

        try {
            $reference = 'EXP-' . now()->format('YmdHis') . '-' . $expense->id;

            $map = [
                'reference_no' => [$reference, 'string'],
                'reference' => [$reference, 'string'],
                'entry_date' => [$expense->expense_date->format('Y-m-d'), 'string'],
                'journal_date' => [$expense->expense_date->format('Y-m-d'), 'string'],
                'transaction_date' => [$expense->expense_date->format('Y-m-d'), 'string'],
                'description' => ['Expense: ' . $expense->title, 'string'],
                'amount' => [(float) $expense->amount, 'float'],
                'branch_id' => [(int) $expense->branch_id, 'int'],
                'created_by' => [(int) ($expense->created_by ?: auth()->id()), 'int'],
                'source_type' => ['expense', 'string'],
                'source_id' => [(int) $expense->id, 'int'],
                'status' => ['posted', 'string'],
            ];

            $columns = Schema::getColumnListing('journal_entries');
            $insert = [];
            foreach ($map as $column => [$value, $type]) {
                if (in_array($column, $columns, true)) {
                    $insert[$column] = $value;
                }
            }

            if ($insert === []) {
                return;
            }

            $journalId = DB::table('journal_entries')->insertGetId($insert);

            if ($journalId > 0) {
                if (Schema::hasTable('journal_entry_lines') && Schema::hasColumns('journal_entry_lines', ['journal_id', 'account_code', 'account_name', 'debit', 'credit'])) {
                    $debitCode = $expense->debit_account_code ?: '5000';
                    $creditCode = $expense->credit_account_code ?: '1000';
                    $debitName = $this->accountNameMap()[$debitCode] ?? 'Expense';
                    $creditName = $this->accountNameMap()[$creditCode] ?? 'Cash/Bank';
                    $amount = (float) $expense->amount;

                    DB::table('journal_entry_lines')->insert([
                        ['journal_id' => $journalId, 'account_code' => $debitCode, 'account_name' => $debitName, 'debit' => $amount, 'credit' => 0],
                        ['journal_id' => $journalId, 'account_code' => $creditCode, 'account_name' => $creditName, 'debit' => 0, 'credit' => $amount],
                    ]);
                }

                $expense->update(['journal_id' => $journalId]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}