<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'suppliers';

    /**
     * The legacy suppliers table has no updated_at column.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'supplier_type',
        'branch_id',
        'name',
        'contact_name',
        'phone',
        'email',
        'address',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'supplier_id');
    }
}
