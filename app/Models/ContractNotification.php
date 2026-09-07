<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractNotification extends Model
{
    protected $table = 'contract_notifications';

    public const UPDATED_AT = null;

    protected $fillable = [
        'contract_id',
        'from_user_id',
        'to_user_id',
        'to_role',
        'step',
        'subject',
        'message',
        'email_sent',
        'email_sent_at',
        'read_at',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'from_user_id' => 'integer',
        'to_user_id' => 'integer',
        'step' => 'integer',
        'email_sent' => 'boolean',
        'email_sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
