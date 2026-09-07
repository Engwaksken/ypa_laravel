<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'transaction_number',
        'account_or_mobile',
        'payment_type',
        'payer_type',
        'customer_id',
        'member_id',
        'group_id',
        'contract_id',
        'chart_account_id',
        'project_id',
        'branch_id',
        'transaction_type_id',
        'company_mode_id',
        'transaction_date',
        'amount',
        'penalties',
        'processing_fee',
        'other_charges',
        'total_fees',
        'deduction_description',
        'net_amount',
        'payment_method_id',
        'membership_fee',
        'membership_fee_paid',
        'membership_outstanding',
        'registration_fee',
        'registration_fee_paid',
        'registration_outstanding',
        'admin_fee',
        'admin_fee_paid',
        'admin_fee_outstanding',
        'contract_amount',
        'contract_amount_paid',
        'contract_amount_outstanding',
        'total_amount',
        'total_amount_paid',
        'total_amount_outstanding',
        'reference',
        'receipt_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'member_id' => 'integer',
        'group_id' => 'integer',
        'contract_id' => 'integer',
        'chart_account_id' => 'integer',
        'project_id' => 'integer',
        'branch_id' => 'integer',
        'transaction_type_id' => 'integer',
        'company_mode_id' => 'integer',
        'payment_method_id' => 'integer',
        'reconciled_by' => 'integer',
        'created_by' => 'integer',
        'approved_by' => 'integer',
        'transaction_date' => 'datetime',
        'reconciled_date' => 'datetime',
        'approval_date' => 'datetime',
        'amount' => 'decimal:2',
        'penalties' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'membership_fee' => 'decimal:2',
        'membership_fee_paid' => 'decimal:2',
        'membership_outstanding' => 'decimal:2',
        'registration_fee' => 'decimal:2',
        'registration_fee_paid' => 'decimal:2',
        'registration_outstanding' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'admin_fee_paid' => 'decimal:2',
        'admin_fee_outstanding' => 'decimal:2',
        'contract_amount' => 'decimal:2',
        'contract_amount_paid' => 'decimal:2',
        'contract_amount_outstanding' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_amount_paid' => 'decimal:2',
        'total_amount_outstanding' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

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

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(PaymentTransactionType::class, 'transaction_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
