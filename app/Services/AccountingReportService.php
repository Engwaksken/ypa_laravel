<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingReportService
{
    public function statement(string $tab, string $from, string $to, int $branchId, bool $fullExport = false): array
    {
        $unavailable = fn ($message) => ['available' => false, 'message' => $message, 'columns' => [], 'rows' => collect(), 'totals' => []];
        if (!Schema::hasTable('ledger_entries') || !Schema::hasTable('chart_of_accounts')
            || !Schema::hasColumns('ledger_entries', ['journal_id', 'account_id', 'debit', 'credit', 'created_at'])
            || !Schema::hasColumns('chart_of_accounts', ['id', 'account_code', 'account_name', 'account_type'])) {
            return $unavailable('Ledger statements require the legacy ledger and chart of accounts. No accounting balances have been inferred from operational totals.');
        }
        $base = DB::table('ledger_entries as l')->join('chart_of_accounts as a', 'a.id', '=', 'l.account_id');
        $date = 'l.created_at';
        $journalBranch = false;
        if (Schema::hasTable('journal_entries')) {
            $base->join('journal_entries as j', 'j.id', '=', 'l.journal_id');
            foreach (['transaction_date', 'entry_date', 'journal_date'] as $column) {
                if (Schema::hasColumn('journal_entries', $column)) { $date = 'j.' . $column; break; }
            }
            $journalBranch = Schema::hasColumn('journal_entries', 'branch_id');
            if (Schema::hasColumn('journal_entries', 'status')) $base->whereRaw("LOWER(COALESCE(j.status, '')) = 'posted'");
            if (Schema::hasColumn('journal_entries', 'deleted_at')) $base->whereNull('j.deleted_at');
        }
        if (Schema::hasColumn('ledger_entries', 'deleted_at')) $base->whereNull('l.deleted_at');
        $ledgerBranch = Schema::hasColumn('ledger_entries', 'branch_id');
        $branchColumn = $ledgerBranch && $journalBranch ? DB::raw('COALESCE(NULLIF(l.branch_id, 0), j.branch_id)') : ($ledgerBranch ? 'l.branch_id' : ($journalBranch ? 'j.branch_id' : null));
        if ($branchId > 0 && !$branchColumn) {
            return $unavailable('This ledger cannot attribute entries to branches. Branch statements remain unavailable until historical journals are reconciled.');
        }
        if ($branchId > 0) $base->where($branchColumn, $branchId);
        $historyQuery = (clone $base)->whereDate($date, '<=', $to);
        $periodQuery = (clone $historyQuery)->whereDate($date, '>=', $from);
        $aggregate = fn ($query) => $query->select('l.account_id', 'a.account_code', 'a.account_name', 'a.account_type')
            ->selectRaw('SUM(l.debit) as debit, SUM(l.credit) as credit')->groupBy('l.account_id', 'a.account_code', 'a.account_name', 'a.account_type')->get();
        $lines = function ($query) use ($date, $fullExport) {
            $query->select('l.*', 'a.account_code', 'a.account_name', 'a.account_type', DB::raw($date . ' as entry_date'))->orderByDesc($date)->orderByDesc('l.id');
            if (!$fullExport) $query->limit(500);
            return $query->get();
        };
        $history = in_array($tab, ['trial', 'balance']) ? $aggregate(clone $historyQuery) : collect();
        $period = $tab === 'ledger-income' ? $aggregate(clone $periodQuery) : ($tab === 'ledger' ? $lines(clone $periodQuery) : collect());
        $message = $branchId > 0 ? 'Historical entries without branch attribution are excluded; reconcile them before certifying this statement.' : 'Ledger entries only. Unposted operational records and POS receipts are excluded until ledger reconciliation.';
        $message .= ' When journal statuses exist, only posted entries are included; deleted entries are excluded. Ledger and cash movement screens show the latest 500 rows; totals and CSV exports cover the full selected period.';
        $commonColumns = [
            ['key' => 'account_code', 'label' => 'Account code', 'type' => 'text'],
            ['key' => 'account_name', 'label' => 'Account', 'type' => 'text'],
            ['key' => 'account_type', 'label' => 'Type', 'type' => 'text'],
        ];
        $amountColumn = fn ($key, $label) => ['key' => $key, 'label' => $label, 'type' => 'money'];
        $totals = [];
        if ($tab === 'ledger') {
            $rows = $period->map(fn ($line) => (array) $line);
            $columns = array_merge([['key' => 'entry_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'journal_id', 'label' => 'Journal', 'type' => 'number']], $commonColumns, [$amountColumn('debit', 'Debit'), $amountColumn('credit', 'Credit')]);
            $totals = ['Total debits' => (float) (clone $periodQuery)->sum('l.debit'), 'Total credits' => (float) (clone $periodQuery)->sum('l.credit')];
        } elseif ($tab === 'trial') {
            $rows = $this->accountTotals($history)->map(function ($row) {
                $net = $row['total_debit'] - $row['total_credit'];
                $row['total_debit'] = max(0, $net); $row['total_credit'] = max(0, -$net);
                return $row;
            })->values();
            $columns = array_merge($commonColumns, [$amountColumn('total_debit', 'Debit balance'), $amountColumn('total_credit', 'Credit balance')]);
            $totals = ['Debit balances' => (float) $rows->sum('total_debit'), 'Credit balances' => (float) $rows->sum('total_credit')];
            $totals['Difference'] = $totals['Debit balances'] - $totals['Credit balances'];
        } elseif ($tab === 'balance') {
            $accounts = $this->accountTotals($history);
            $rows = $accounts->filter(fn ($row) => in_array(strtolower($row['account_type']), ['asset', 'liability', 'equity']))->map(function ($row) {
                $row['balance'] = strtolower($row['account_type']) === 'asset' ? $row['total_debit'] - $row['total_credit'] : $row['total_credit'] - $row['total_debit'];
                return $row;
            })->values();
            $earnings = $accounts->sum(fn ($row) => in_array(strtolower($row['account_type']), ['income', 'expense']) ? $row['total_credit'] - $row['total_debit'] : 0);
            $rows->push(['account_code' => '', 'account_name' => 'Accumulated unclosed net income', 'account_type' => 'Equity', 'balance' => $earnings]);
            $columns = array_merge($commonColumns, [$amountColumn('balance', 'Balance')]);
            foreach (['Asset' => 'Assets', 'Liability' => 'Liabilities', 'Equity' => 'Equity'] as $type => $label) {
                $totals[$label] = (float) $rows->filter(fn ($row) => strtolower($row['account_type']) === strtolower($type))->sum('balance');
            }
            $totals['Difference'] = $totals['Assets'] - $totals['Liabilities'] - $totals['Equity'];
        } elseif ($tab === 'ledger-income') {
            $rows = $this->accountTotals($period)->filter(fn ($row) => in_array(strtolower($row['account_type']), ['income', 'expense']))->map(function ($row) {
                $row['amount'] = strtolower($row['account_type']) === 'income' ? $row['total_credit'] - $row['total_debit'] : $row['total_debit'] - $row['total_credit'];
                return $row;
            })->values();
            $columns = array_merge($commonColumns, [$amountColumn('amount', 'Amount')]);
            $totals = ['Income' => (float) $rows->filter(fn ($r) => strtolower($r['account_type']) === 'income')->sum('amount'), 'Expenses' => (float) $rows->filter(fn ($r) => strtolower($r['account_type']) === 'expense')->sum('amount')];
            $totals['Net income'] = $totals['Income'] - $totals['Expenses'];
        } else {
            if (!Schema::hasTable('payment_methods') || !Schema::hasColumn('payment_methods', 'chart_account_id')) {
                return $unavailable('Cash flow requires explicit payment-method links to cash/bank Asset accounts.');
            }
            $cashIds = DB::table('payment_methods')->whereNotNull('chart_account_id')->pluck('chart_account_id')->unique();
            $cashHistory = (clone $historyQuery)->whereIn('l.account_id', $cashIds)->whereRaw("LOWER(a.account_type) = 'asset'");
            if (!(clone $cashHistory)->exists()) return $unavailable('No ledger movements for explicitly linked cash/bank accounts. Cash flow has not been inferred from all account types.');
            $cashPeriodQuery = (clone $cashHistory)->whereDate($date, '>=', $from);
            $cashPeriod = $lines(clone $cashPeriodQuery);
            $rows = $cashPeriod->map(fn ($line) => array_merge((array) $line, ['inflow' => (float) $line->debit, 'outflow' => (float) $line->credit]))->values();
            $columns = array_merge([['key' => 'entry_date', 'label' => 'Date', 'type' => 'date'], ['key' => 'journal_id', 'label' => 'Journal', 'type' => 'number']], $commonColumns, [$amountColumn('inflow', 'Cash in'), $amountColumn('outflow', 'Cash out')]);
            $opening = (float) (clone $cashHistory)->whereDate($date, '<', $from)->sum(DB::raw('l.debit - l.credit'));
            $totals = ['Opening cash' => $opening, 'Inflows' => (float) (clone $cashPeriodQuery)->sum('l.debit'), 'Outflows' => (float) (clone $cashPeriodQuery)->sum('l.credit')];
            $totals['Closing cash'] = $opening + $totals['Inflows'] - $totals['Outflows'];
            $message .= ' Cash/bank account coverage follows payment-method links; operating/investing/financing classifications are unavailable without explicit mappings. Transfers between linked cash accounts appear on both sides.';
        }
        return ['available' => true, 'message' => $message, 'columns' => $columns, 'rows' => $rows, 'totals' => $totals];
    }

    private function accountTotals(Collection $lines): Collection
    {
        return $lines->groupBy('account_id')->map(fn ($entries) => [
            'account_code' => $entries->first()->account_code, 'account_name' => $entries->first()->account_name,
            'account_type' => $entries->first()->account_type, 'total_debit' => (float) $entries->sum('debit'), 'total_credit' => (float) $entries->sum('credit'),
        ])->sortBy('account_code');
    }
}
