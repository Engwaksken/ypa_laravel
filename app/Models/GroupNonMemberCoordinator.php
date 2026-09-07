<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupNonMemberCoordinator extends Model
{
    protected $table = 'group_non_member_coordinators';

    protected $fillable = [
        'group_id',
        'name',
        'phone',
        'email',
        'role',
    ];

    protected $casts = [
        'group_id' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}