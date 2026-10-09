<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['sale_date' => 'date', 'total_amount' => 'decimal:2', 'balance_amount' => 'decimal:2', 'discount' => 'decimal:2'];

    public function items(): HasMany { return $this->hasMany(SaleItem::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }

    public function getCustomerNameAttribute($value): string { return $value ?: ($this->customer?->name ?? 'Walk-in Customer'); }
    public function getPartialAmountAttribute($value): string { return number_format((float) ($value ?? $this->attributes['paid_amount'] ?? ((float) $this->total_amount - (float) $this->balance_amount)), 2, '.', ''); }
}
