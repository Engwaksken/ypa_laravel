<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['price' => 'decimal:2', 'total' => 'decimal:2', 'cost_price' => 'decimal:2'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function getProductNameAttribute($value): string { return $value ?: ($this->product?->name ?? 'Deleted product'); }
}
