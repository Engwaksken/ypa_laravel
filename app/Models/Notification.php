<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    /**
     * The legacy notifications table has no updated_at column.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'title',
        'message',
        'type',
        'is_read',
        'user_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeForUser($query, ?int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->whereNull('user_id');

            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        });
    }
}
