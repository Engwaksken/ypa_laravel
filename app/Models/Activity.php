<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $table = 'activities';

    protected $fillable = [
        'activity_code',
        'activity_type_id',
        'activity_name',
        'description',
        'start_date',
        'end_date',
        'location',
        'budget',
        'is_promotion',
        'promo_category',
        'promo_item_name',
        'promo_other_text',
        'activity_date',
        'start_time',
        'end_time',
        'venue',
        'capacity',
        'participation_fee',
        'status',
        'organizer',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'activity_type_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ActivityParticipant::class, 'activity_id');
    }

    public function participantItems(): HasMany
    {
        return $this->hasMany(ActivityParticipantItem::class, 'activity_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Completed' => 'success',
            'Ongoing' => 'info',
            'Planned' => 'warning',
            'Cancelled' => 'danger',
            default => 'secondary',
        };
    }
}
