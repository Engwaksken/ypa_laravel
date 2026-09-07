<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPayout extends Model
{
    protected $table = 'contract_payouts';

    public const UPDATED_AT = null;

    protected $fillable = [
        'contract_id',
        'payout_type',
        'payout_date',
        'amount_cash',
        'amount_quantity',
        'quantity_unit',
        'status',
        'notes',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'payout_date' => 'date',
        'amount_cash' => 'decimal:2',
        'amount_quantity' => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
