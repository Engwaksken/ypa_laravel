<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model
{
    protected $table = 'contract_items';

    protected $fillable = [
        'contract_id',
        'project_id',
        'quantity',
        'unit_price',
        'total_price',
        'purchase_fee',
        'purchase_price',
        'purchase_discount',
        'monthly_return',
        'monthly_payout_amount',
        'quarterly_payout',
        'total_hives',
        'remaining_hives',
        'goats_purchased',
        'goats_offers',
        'total_goats',
        'remaining_goats',
        'acreages_purchased',
        'acreages_offers',
        'total_acreages',
        'remaining_acreages',
        'harvest_amount',
        'harvested_amount',
        'balance_amount',
        'harvest_quantity',
        'harvested_quantity',
        'balance_quantity',
        'projected_quantity',
        'projected_harvest_amount',
        'projected_harvest_balance',
        'duration_months',
        'item_order',
        'is_matured',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'project_id' => 'integer',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'purchase_fee' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'purchase_discount' => 'decimal:2',
        'monthly_return' => 'decimal:2',
        'monthly_payout_amount' => 'decimal:2',
        'quarterly_payout' => 'decimal:2',
        'total_hives' => 'decimal:2',
        'remaining_hives' => 'decimal:2',
        'goats_purchased' => 'decimal:2',
        'goats_offers' => 'decimal:2',
        'total_goats' => 'decimal:2',
        'remaining_goats' => 'decimal:2',
        'acreages_purchased' => 'decimal:2',
        'acreages_offers' => 'decimal:2',
        'total_acreages' => 'decimal:2',
        'remaining_acreages' => 'decimal:2',
        'harvest_amount' => 'decimal:2',
        'harvested_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'harvest_quantity' => 'decimal:2',
        'harvested_quantity' => 'decimal:2',
        'balance_quantity' => 'decimal:2',
        'projected_quantity' => 'decimal:2',
        'projected_harvest_amount' => 'decimal:2',
        'projected_harvest_balance' => 'decimal:2',
        'duration_months' => 'integer',
        'item_order' => 'integer',
        'is_matured' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function harvests(): HasMany
    {
        return $this->hasMany(ContractItemHarvest::class, 'contract_item_id');
    }
}
