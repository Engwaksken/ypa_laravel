<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivablePayment extends Model
{
    protected $table = 'receivable_payments';

    protected $fillable = [
        'receivable_id',
        'payment_date',
        'amount_paid',
        'payment_method',
        'payment_reference',
        'receipt_number',
        'invoice_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'receivable_id' => 'integer',
        'created_by' => 'integer',
        'payment_date' => 'date',
        'amount_paid' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class, 'receivable_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
