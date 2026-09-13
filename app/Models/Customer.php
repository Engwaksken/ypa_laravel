<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $table = 'customers';

    /**
     * The legacy customers table has no updated_at column.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'customer_type',
        'branch_id',
        'name',
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
}
