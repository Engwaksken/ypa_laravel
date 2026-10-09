<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ContractItem;
use App\Models\Expense;
use App\Models\Harvest;
use App\Models\PaymentTransaction;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends ReportController
{
    public const TABS = ['overview', 'income', 'projects', 'branches', 'ledger', 'trial', 'balance', 'cashflow', 'ledger-income'];

    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('auth'),
            new \Illuminate\Routing\Controllers\Middleware('user.status'),
            new \Illuminate\Routing\Controllers\Middleware('permission:financial_reports'),
            (new \Illuminate\Routing\Controllers\Middleware('permission:export_financial_reports'))->only(['export']),
        ];
    }

    public function index(Request $request)
    {
        [$from, $to] = $this->dateRange($request);
        $branchId = $this->selectedBranch($request);
        $branches = $this->branches();
        $tab = in_array($request->query('tab', 'overview'), self::TABS, true)
            ? $request->query('tab', 'overview')
            : 'overview';

        $data = $this->compute($from, $to, $branchId, $tab);

        return view('reports.financial.index', compact(
            'from', 'to', 'branchId', 'branches', 'tab', 'data'
        ));
    }

    public function export(Request $request)
    {
        if (!app(\App\Services\PermissionService::class)->canAny(['export_financial_reports', 'reports_export'])) {
            abort(403, 'You do not have permission to export the financial report.');
        }

        [$from, $to] = $this->dateRange($request);
        $branchId = $this->selectedBranch($request);
        $tab = in_array($request->query('tab', 'overview'), self::TABS, true)
            ? $request->query('tab', 'overview')
            : 'overview';

        $data = $this->compute($from, $to, $branchId, $tab, true);

        $filename = 'financial_report_' . $tab . '_' . $from . '_' . $to . '.csv';

        if (isset($data['statement'])) {
            $statement = $data['statement'];
            abort_unless($statement['available'], 422, $statement['message']);
            $headers = array_column($statement['columns'], 'label');
            $keys = array_column($statement['columns'], 'key');
            return $this->streamCsv($filename, $headers, $statement['rows'], fn ($row) => array_map(fn ($key) => $row[$key] ?? '', $keys));
        }

        if ($tab === 'overview') {
            $headers = ['Metric', 'Amount'];
            $rows = collect([
                ['Total Revenue (Collected)', $this->moneyExport($data['revenue'])],
                ['Total Expenses (Manual)', $this->moneyExport($data['expenses'])],
                ['Harvest Payouts', $this->moneyExport($data['harvests'])],
                ['Project Revenue', $this->moneyExport($data['projects'])],
                ['Net Position', $this->moneyExport($data['net'])],
            ]);
        } elseif ($tab === 'income') {
            $headers = ['Date', 'Order #', 'Customer', 'Method', 'Status', 'Amount'];
            $rows = $data['transactions']->map(fn ($t) => [
                $t['date'],
                $t['order_number'],
                $t['customer'],
                $t['method'],
                $t['status'],
                $this->moneyExport($t['amount']),
            ]);
        } elseif ($tab === 'projects') {
            $headers = ['Project', 'Branch', 'Revenue', 'Cost', 'Profit', 'Margin %'];
            $rows = $data['projectsDetail']->map(fn ($p) => [
                $p['name'],
                $p['branch'],
                $this->moneyExport($p['revenue']),
                $this->moneyExport($p['cost']),
                $this->moneyExport($p['profit']),
                number_format($p['margin'], 1),
            ]);
        } else {
            $headers = ['Branch', 'Revenue', 'Expenses', 'Harvest Payouts', 'Net'];
            $rows = $data['branchesDetail']->map(fn ($b) => [
                $b['name'],
                $this->moneyExport($b['revenue']),
                $this->moneyExport($b['expenses']),
                $this->moneyExport($b['harvests']),
                $this->moneyExport($b['net']),
            ]);
        }

        return $this->streamCsv($filename, $headers, $rows, fn ($r) => $r);
    }

    protected function compute(string $from, string $to, int $branchId, string $tab, bool $fullExport = false): array
    {
        if (in_array($tab, ['ledger', 'trial', 'balance', 'cashflow', 'ledger-income'], true)) {
            return ['statement' => app(\App\Services\AccountingReportService::class)->statement($tab, $from, $to, $branchId, $fullExport)];
        }
        $approved = PaymentTransaction::query()->with('paymentMethod')
            ->where('status', 'APPROVED')
            ->whereBetween('transaction_date', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))->get();
        $collected = fn ($transaction) => (float) ($transaction->total_amount_paid ?? $transaction->amount ?? 0);
        $revenue = (float) $approved->sum($collected);
        $expenses = Expense::query()->whereBetween('expense_date', [$from, $to])
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))->get();
        $paidHarvests = Harvest::query()
            ->whereRaw("(LOWER(COALESCE(status, '')) = 'paid' OR LOWER(COALESCE(approval_stage, '')) = 'paid')")
            ->when($branchId > 0, fn ($query) => $query->where('branch_id', $branchId))->get()
            ->filter(fn (Harvest $harvest) => $this->paidDateInRange($harvest, $from, $to));
        $payout = fn ($harvest) => (float) ($harvest->net_amount ?? $harvest->amount_harvested ?? $harvest->equivalent_ugx ?? 0);
        $expensesTotal = (float) $expenses->sum('amount');
        $harvestTotal = (float) $paidHarvests->sum($payout);
        $projectsTotal = (float) $approved->whereNotNull('project_id')->sum($collected);
        $result = [
            'revenue' => $revenue, 'expenses' => $expensesTotal, 'harvests' => $harvestTotal,
            'projects' => $projectsTotal,
            // Project collections are already included in approved revenue.
            'net' => $revenue - $expensesTotal - $harvestTotal,
        ];
        if ($tab === 'income') {
            $rows = $approved->map(fn ($transaction) => [
                'date' => $transaction->transaction_date?->format('Y-m-d H:i') ?? '',
                'order_number' => $transaction->receipt_number ?: $transaction->transaction_number ?: 'N/A',
                'customer' => $transaction->payer_type ?: 'Unspecified',
                'method' => $transaction->paymentMethod->method_name ?? '-',
                'status' => $transaction->status,
                'amount' => $collected($transaction),
            ]);
            return array_merge($result, ['transactions' => $rows]);
        }
        if ($tab === 'projects') {
            $collections = $approved->whereNotNull('project_id')->groupBy('project_id')->map(fn ($items) => (float) $items->sum($collected));
            $items = ContractItem::query()->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
                ->when($branchId > 0, fn ($query) => $query->whereHas('contract', fn ($contract) => $contract->where('branch_id', $branchId)))->get();
            $costs = $items->groupBy('project_id')->map(fn ($items) => (float) $items->sum(fn ($item) => (float) ($item->purchase_price ?? 0) * (float) ($item->quantity ?? 0)));
            $projectIds = $collections->keys()->merge($costs->keys())->unique();
            $detail = Project::query()->with('branch')->whereIn('id', $projectIds)->get()->map(function (Project $project) use ($collections, $costs) {
                $revenue = (float) ($collections[$project->id] ?? 0);
                $cost = (float) ($costs[$project->id] ?? 0);
                $profit = $revenue - $cost;
                return ['name' => $project->project_name, 'branch' => $project->branch->name ?? '-',
                    'revenue' => $revenue, 'cost' => $cost, 'profit' => $profit,
                    'margin' => $cost > 0 ? $profit / $cost * 100 : 0];
            });
            return array_merge($result, ['projectsDetail' => $detail]);
        }
        if ($tab === 'branches') {
            $branches = $this->branches()->when($branchId > 0, fn ($rows) => $rows->where('id', $branchId));
            $detail = $branches->map(function ($branch) use ($approved, $expenses, $paidHarvests, $collected, $payout) {
                $revenue = (float) $approved->where('branch_id', $branch->id)->sum($collected);
                $expense = (float) $expenses->where('branch_id', $branch->id)->sum('amount');
                $harvest = (float) $paidHarvests->where('branch_id', $branch->id)->sum($payout);
                return ['name' => $branch->name, 'revenue' => $revenue, 'expenses' => $expense,
                    'harvests' => $harvest, 'net' => $revenue - $expense - $harvest];
            });
            return array_merge($result, ['branchesDetail' => $detail]);
        }
        return $result;
    }

    protected function paidDateInRange(\App\Models\Harvest $h, string $from, string $to): bool
    {
        $date = $h->paid_at ?? $h->approved_at ?? $h->harvest_date ?? $h->created_at;
        $date = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : substr((string) $date, 0, 10);

        return $date !== '' && $date >= $from && $date <= $to;
    }
}
