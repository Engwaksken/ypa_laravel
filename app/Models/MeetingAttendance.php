<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingAttendance extends Model
{
    protected $table = 'meeting_attendance';

    // The legacy table has only created_at (no updated_at column).
    public $timestamps = false;

    protected $fillable = [
        'meeting_id',
        'member_id',
        'status',
        'check_in_time',
        'notes',
        'attended_at',
    ];

    protected $casts = [
        'meeting_id' => 'integer',
        'member_id' => 'integer',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * The schema has no `attended` boolean — attendance is a status enum.
     * Present/Late count as attended.
     */
    public function getAttendedAttribute(): bool
    {
        return in_array($this->status, ['Present', 'Late'], true);
    }
}