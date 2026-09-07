<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTermination extends Model
{
    protected $table = 'contract_terminations';

    public const UPDATED_AT = null;

    protected $fillable = [
        'contract_id',
        'termination_date',
        'reason',
        'amount_paid',
        'contract_amount',
        'project_projection',
        'deduction_base',
        'deduction_base_source',
        'deduction_rate',
        'deduction_amount',
        'refund_amount',
        'notes',
        'created_by',
        'deduction_harvested',
        'deduction_harvested_at',
        'harvest_id',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'created_by' => 'integer',
        'termination_date' => 'date',
        'contract_amount' => 'decimal:2',
        'project_projection' => 'decimal:2',
        'deduction_base' => 'decimal:2',
        'deduction_rate' => 'decimal:4',
        'deduction_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'deduction_harvested' => 'integer',
        'deduction_harvested_at' => 'datetime',
        'harvest_id' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
