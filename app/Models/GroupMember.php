<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMember extends Model
{
    protected $table = 'group_members';

    protected $fillable = [
        'group_id',
        'member_id',
        'role',
        'join_date',
        'status',
        'contribution_amount',
        'notes',
        'added_by',
        'added_at',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'member_id' => 'integer',
        'joined_at' => 'date',
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