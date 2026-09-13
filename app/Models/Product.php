<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';

    /**
     * The legacy products table has no updated_at column.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'sku',
        'branch_id',
        'category_id',
        'description',
        'image_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class, 'product_id');
    }
}
