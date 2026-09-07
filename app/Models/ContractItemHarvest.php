<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItemHarvest extends Model
{
    protected $table = 'contract_item_harvests';

    protected $fillable = [
        'contract_item_id',
        'harvest_id',
        'contract_id',
        'project_kind',
        'harvest_type',
        'harvest_mode',
        'harvest_date',
        'start_date',
        'end_date',
        'periods_due',
        'months_elapsed',
        'years_elapsed',
        'withdrawable_amount',
        'withdrawable_quantity',
        'projected_harvest_amount',
        'projected_harvest_quantity',
        'purchase_fee_reference',
        'amount_payable',
        'gross_entitlement',
        'period_profit',
        'unwithdrawn_entitlement',
        'already_withdrawn_amount',
        'already_withdrawn_quantity',
        'amount_harvested',
        'quantity_harvested',
        'balance_before_amount',
        'balance_after_amount',
        'balance_before_quantity',
        'balance_after_quantity',
        'maintenance_fee',
        'penalties',
        'processing_fee',
        'other_charges',
        'total_fees',
        'net_amount',
        'number_of_goats_harvested',
        'bee_sub_type',
        'goat_contract_mode',
        'cdc_harvest_option',
        'notes',
        'journal_id',
        'created_by',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'contract_item_id' => 'integer',
        'harvest_id' => 'integer',
        'project_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'harvest_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'periods_due' => 'integer',
        'months_elapsed' => 'integer',
        'years_elapsed' => 'integer',
        'withdrawable_amount' => 'decimal:2',
        'withdrawable_quantity' => 'decimal:2',
        'projected_harvest_amount' => 'decimal:2',
        'projected_harvest_quantity' => 'decimal:2',
        'amount_payable' => 'decimal:2',
        'gross_entitlement' => 'decimal:2',
        'period_profit' => 'decimal:2',
        'unwithdrawn_entitlement' => 'decimal:2',
        'already_withdrawn_amount' => 'decimal:2',
        'already_withdrawn_quantity' => 'decimal:2',
        'amount_harvested' => 'decimal:2',
        'quantity_harvested' => 'decimal:2',
        'balance_before_amount' => 'decimal:2',
        'balance_after_amount' => 'decimal:2',
        'balance_before_quantity' => 'decimal:2',
        'balance_after_quantity' => 'decimal:2',
        'maintenance_fee' => 'decimal:2',
        'penalties' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'number_of_goats_harvested' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function harvest(): BelongsTo
    {
        return $this->belongsTo(Harvest::class, 'harvest_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class, 'contract_item_id');
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
