<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberMobilization extends Model
{
    protected $table = 'member_mobilization';

    protected $fillable = [
        'member_id',
        'mobilizer_id',
        'mobilized_date',
        'channel',
        'notes',
    ];

    protected $casts = [
        'mobilized_date' => 'date',
        'member_id' => 'integer',
        'mobilizer_id' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function mobilizer(): BelongsTo
    {
        return $this->belongsTo(Mobilizer::class, 'mobilizer_id');
    }
}
