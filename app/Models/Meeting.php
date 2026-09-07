<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    protected $table = 'meetings';

    protected $fillable = [
        'meeting_type',
        'meeting_title',
        'meeting_date',
        'meeting_time',
        'location',
        'agenda',
        'minutes',
        'attendance_count',
        'status',
        'chaired_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'created_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(MeetingAttendance::class, 'meeting_id');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(MeetingInvite::class, 'meeting_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Completed' => 'success',
            'Ongoing' => 'info',
            'Scheduled' => 'warning',
            'Cancelled' => 'danger',
            default => 'secondary',
        };
    }
}