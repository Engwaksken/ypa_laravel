<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LegacyLedgerService
{
    public function postExpense(Expense $expense): void
    {
        $this->ensure(Schema::hasColumns('ledger_entries', ['journal_id', 'account_id', 'debit', 'credit', 'created_at'])
            && Schema::hasColumns('chart_of_accounts', ['id', 'account_code', 'account_name', 'account_type'])
            && Schema::hasColumn('journal_entries', 'branch_id'), 'Legacy ledger posting requires the supported schema and journal branch migration.');
        $debit = $this->account($expense->debit_account_code ?: '5000');
        $credit = $this->account($expense->credit_account_code ?: '1000');
        $this->ensure($debit->id !== $credit->id, 'Expense debit and credit accounts must differ.');
        $columns = Schema::getColumnListing('journal_entries');
        $this->ensure(in_array('transaction_date', $columns) || in_array('entry_date', $columns) || in_array('journal_date', $columns), 'The journal has no supported transaction date column.');
        $date = $expense->expense_date->toDateString();
        $reference = 'EXP-' . $expense->id;
        $header = array_intersect_key([
            'reference_no' => $reference, 'reference' => $reference, 'description' => 'Expense: ' . $expense->title,
            'transaction_date' => $date, 'entry_date' => $date, 'journal_date' => $date,
            'amount' => $expense->amount, 'branch_id' => $expense->branch_id,
            'created_by' => $expense->created_by ?: auth()->id(), 'created_at' => now(),
            'source_type' => 'expense', 'source_id' => $expense->id, 'status' => 'posted',
        ], array_flip($columns));
        $journalId = DB::table('journal_entries')->insertGetId($header);
        $lineColumns = array_flip(Schema::getColumnListing('ledger_entries'));
        DB::table('ledger_entries')->insert([
            array_intersect_key(['journal_id' => $journalId, 'account_id' => $debit->id, 'debit' => $expense->amount, 'credit' => '0.00', 'branch_id' => $expense->branch_id, 'created_at' => now()], $lineColumns),
            array_intersect_key(['journal_id' => $journalId, 'account_id' => $credit->id, 'debit' => '0.00', 'credit' => $expense->amount, 'branch_id' => $expense->branch_id, 'created_at' => now()], $lineColumns),
        ]);
        $expense->update(['journal_id' => $journalId]);
    }

    private function account(string $code): object
    {
        $query = DB::table('chart_of_accounts')->where('account_code', $code);
        if (Schema::hasColumn('chart_of_accounts', 'is_active')) $query->where('is_active', 1);
        $account = $query->first();
        $this->ensure((bool) $account, 'The selected chart account is missing or inactive.');
        return $account;
    }

    private function ensure(bool $condition, string $message): void
    {
        if (!$condition) throw ValidationException::withMessages(['debit_account_code' => $message]);
    }
}
