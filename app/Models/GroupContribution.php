<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupContribution extends Model
{
    protected $table = 'group_contributions';

    protected $fillable = [
        'group_id',
        'member_id',
        'contribution_type',
        'amount',
        'contribution_date',
        'payment_method',
        'reference_number',
        'description',
        'collected_by',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'member_id' => 'integer',
        'amount' => 'decimal:2',
        'contribution_date' => 'date',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}