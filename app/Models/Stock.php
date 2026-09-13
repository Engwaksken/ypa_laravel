<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $table = 'stock';

    /**
     * The legacy stock table has no updated_at column.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'branch_id',
        'total_stock',
        'quantity',
        'unit_type',
        'cost_price',
        'expiry_date',
        'selling_price',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
