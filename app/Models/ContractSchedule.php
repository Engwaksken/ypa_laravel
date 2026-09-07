<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSchedule extends Model
{
    protected $table = 'contract_schedules';

    public const UPDATED_AT = null;

    protected $fillable = [
        'contract_id',
        'installment_no',
        'due_date',
        'amount',
        'status',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'installment_no' => 'integer',
        'due_date' => 'date',
        'amount' => 'decimal:2',
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
