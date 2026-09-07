<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    protected $fillable = [
        'method_name',
        'sort_order',
        'chart_account_id',
        'description',
        'icon',
        'status',
    ];

    protected $casts = [
        'chart_account_id' => 'integer',
        'status' => 'string',
        'sort_order' => 'integer',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'payment_method_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(ContractPayout::class, 'payment_method_id');
    }
}
