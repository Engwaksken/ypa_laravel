<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [
        'project_category_id',
        'project_type_id',
        'branch_id',
        'project_name',
        'project_code',
        'description',
        'start_date',
        'end_date',
        'registration_fee',
        'administrative_fee',
        'status',
        'created_by',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'project_category_id' => 'integer',
        'project_type_id' => 'integer',
        'created_by' => 'integer',
        'registration_fee' => 'decimal:2',
        'administrative_fee' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => 'string',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProjectCategory::class, 'project_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'project_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'project_id');
    }
}
