<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Harvest extends Model
{
    protected $table = 'harvests';

    protected $fillable = [
        'branch_id',
        'contract_id',
        'member_id',
        'group_id',
        'owner_type',
        'harvest_type',
        'harvest_date',
        'projected_harvest_amount',
        'months_elapsed',
        'years_elapsed',
        'periods_due',
        'seasonal_harvest_amount',
        'seasonal_harvest_quantity',
        'amount_harvested',
        'quantity_harvested',
        'number_of_goats_harvested',
        'balance_amount',
        'balance_quantity',
        'balance_goats',
        'additional_goats_purchased',
        'total_goats',
        'maintenance_fee',
        'accumulated_profits',
        'harvestable_profit',
        'current_herd_size',
        'equivalent_ugx',
        'season',
        'payment_reference',
        'notes',
        'created_by',
        'payment_method',
        'harvest_form_path',
        'journal_id',
        'debit_account_code',
        'credit_account_code',
        'approval_date',
        'signature_path',
        'price_per_unit',
        'harvest_mode',
        'bee_sub_type',
        'goat_contract_mode',
        'cdc_harvest_option',
        'net_amount',
        'total_fees',
        'deduction_description',
        'project_kind',
        'project_name_snap',
        'harvest_data_json',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'contract_id' => 'integer',
        'member_id' => 'integer',
        'group_id' => 'integer',
        'months_elapsed' => 'integer',
        'periods_due' => 'integer',
        'number_of_goats_harvested' => 'integer',
        'balance_goats' => 'integer',
        'additional_goats_purchased' => 'integer',
        'total_goats' => 'integer',
        'current_herd_size' => 'integer',
        'approved_by' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'paid_by' => 'integer',
        'reviewed_by' => 'integer',
        'rejected_by' => 'integer',
        'journal_id' => 'integer',
        'harvest_date' => 'date',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'approval_date' => 'datetime',
        'years_elapsed' => 'decimal:2',
        'projected_harvest_amount' => 'decimal:2',
        'seasonal_harvest_amount' => 'decimal:2',
        'seasonal_harvest_quantity' => 'decimal:2',
        'amount_harvested' => 'decimal:2',
        'quantity_harvested' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'balance_quantity' => 'decimal:2',
        'maintenance_fee' => 'decimal:2',
        'accumulated_profits' => 'decimal:2',
        'harvestable_profit' => 'decimal:2',
        'equivalent_ugx' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'total_fees' => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function itemHarvests(): HasMany
    {
        return $this->hasMany(ContractItemHarvest::class, 'harvest_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
