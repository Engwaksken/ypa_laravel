<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractApproval extends Model
{
    protected $table = 'contract_approvals';

    public const UPDATED_AT = null;

    protected $fillable = [
        'contract_id',
        'step',
        'role',
        'user_id',
        'action',
        'signature_data',
        'signature_method',
        'notes',
        'status',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'step' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
