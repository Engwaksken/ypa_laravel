<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupNextOfKin extends Model
{
    protected $table = 'group_next_of_kin';

    protected $fillable = [
        'group_id',
        'first_name',
        'last_name',
        'relationship',
        'phone',
        'email',
        'nin',
        'date_of_birth',
        'address',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'member_id' => 'integer',
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