<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransactionType extends Model
{
    protected $table = 'payment_transaction_types';

    public $timestamps = false;

    protected $fillable = [
        'type_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'integer',
    ];
}
