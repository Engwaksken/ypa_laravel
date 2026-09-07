<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParticipant extends Model
{
    protected $table = 'activity_participants';

    protected $fillable = [
        'activity_id',
        'member_id',
        'participant_type',
        'first_name',
        'last_name',
        'gender',
        'phone',
        'email',
        'address',
        'region',
        'district',
        'sub_county',
        'parish',
        'village',
        'organization',
        'role_title',
        'attendance_status',
        'notes',
    ];

    protected $casts = [
        'activity_id' => 'integer',
        'member_id' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}