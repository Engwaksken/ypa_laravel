<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractHarvest extends Model
{
    protected $table = 'contract_harvests';

    protected $fillable = [
        'contract_id',
        'project_id',
        'created_by',
        'harvest_date',
        'quantity',
        'amount',
        'balance_amount',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'project_id' => 'integer',
        'created_by' => 'integer',
        'harvest_date' => 'date',
        'quantity' => 'decimal:2',
        'amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
