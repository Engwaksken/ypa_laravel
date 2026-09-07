<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParticipantItem extends Model
{
    protected $table = 'activity_participant_items';

    protected $fillable = [
        'activity_id',
        'participant_id',
        'item_category',
        'item_name',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'participant_id' => 'integer',
        'activity_id' => 'integer',
        'quantity' => 'decimal:2',
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(ActivityParticipant::class, 'participant_id');
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}