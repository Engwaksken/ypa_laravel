<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupCoordinator extends Model
{
    protected $table = 'group_coordinators';

    protected $fillable = [
        'group_id',
        'member_id',
        'join_date',
        'added_by',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'coordinator_id' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }
}