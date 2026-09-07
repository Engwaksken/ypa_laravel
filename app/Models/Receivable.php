<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receivable extends Model
{
    protected $table = 'receivables';

    protected $fillable = [
        'reference_no',
        'received_date',
        'member_id',
        'group_id',
        'branch_id',
        'group_member_id',
        'payer_name',
        'payer_phone',
        'receiver_email',
        'payer_type',
        'group_name',
        'category',
        'receivable_type',
        'other_type',
        'amount',
        'amount_payable',
        'discount',
        'net_amount_payable',
        'income_type',
        'description',
        'chart_account_id',
        'debit_account_id',
        'debit_account_code',
        'credit_account_id',
        'credit_account_code',
        'journal_id',
        'invoice_pdf_url',
        'receipt_pdf_url',
        'created_by',
        'doc_national_id_path',
        'doc_cheque_copy_path',
        'doc_transaction_screenshot_path',
        'doc_other_file_path',
        'doc_other_description',
        'doc_bank_deposit_path',
        'email_sent_at',
        'email_status',
        'email_error',
    ];

    protected $casts = [
        'received_date' => 'date',
        'last_payment_date' => 'date',
        'member_id' => 'integer',
        'group_id' => 'integer',
        'branch_id' => 'integer',
        'group_member_id' => 'integer',
        'chart_account_id' => 'integer',
        'debit_account_id' => 'integer',
        'credit_account_id' => 'integer',
        'journal_id' => 'integer',
        'created_by' => 'integer',
        'payment_transaction_id' => 'integer',
        'amount' => 'decimal:2',
        'amount_payable' => 'decimal:2',
        'discount' => 'decimal:2',
        'net_amount_payable' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'email_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class, 'receivable_id');
    }
}
