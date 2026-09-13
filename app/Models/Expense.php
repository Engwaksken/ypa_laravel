<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        'branch_id',
        'title',
        'amount',
        'expense_date',
        'category',
        'payment_method',
        'description',
        'debit_account_code',
        'credit_account_code',
        'journal_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'journal_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}