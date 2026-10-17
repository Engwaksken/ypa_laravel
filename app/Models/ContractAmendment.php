<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAmendment extends Model
{
    protected $table = 'contract_amendments';

    public const UPDATED_AT = null;

    protected $fillable = [
        'contract_id',
        'user_id',
        'contract_status',
        'workflow_status',
        'reason',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'user_id' => 'integer',
        'changes' => 'array',
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
