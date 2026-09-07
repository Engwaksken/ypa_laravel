<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class MeetingInvite extends Model
{
    protected $table = 'meeting_invites';

    protected $fillable = [
        'meeting_id',
        'user_id',
        'invite_status',
        'invited_at',
        'reminder_sent_at',
    ];

    protected $casts = [
        'meeting_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The schema stores invites against the member's user account
     * (meeting_invites.user_id), so resolve the member through the user.
     */
    public function member(): HasOneThrough
    {
        return $this->hasOneThrough(
            Member::class,
            User::class,
            'id',       // users.id
            'user_id',  // members.user_id
            'user_id',  // meeting_invites.user_id
            'id'        // users.id
        );
    }

    /**
     * An invite row exists = the member was invited. Only a Declined invite
     * is treated as not-invited for the UI checkbox.
     */
    public function getInvitedAttribute(): bool
    {
        return $this->invite_status !== 'Declined';
    }
}