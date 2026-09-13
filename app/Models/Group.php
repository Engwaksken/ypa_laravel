<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $table = 'groups';

    /**
     * Fillable fields for mass assignment. id / created_by / updated_by are
     * set explicitly by the controllers; everything else is fillable so the
     * explicit payloads built by GroupService keep working.
     */
    protected $fillable = [
        'branch_id',
        'group_code',
        'telephone',
        'email',
        'rep_name',
        'rep_photo',
        'group_name',
        'group_category',
        'tin_number',
        'source',
        'source_station',
        'category_other',
        'country',
        'uganda_subregion',
        'uganda_district',
        'village',
        'formation_date',
        'mobilizer_id',
        'description',
        'account_type',
        'bank_account',
        'bank_account_name',
        'bank_name',
        'bank_account_number',
        'coordinator_id',
        'logo',
        'status',
        'total_members',
        'created_by',
        'updated_by',
        'payment_transaction_id',
        'non_member_coordinators',
        'membership_payment_notes',
    ];

    protected $casts = [
        'formation_date' => 'date',
        'mobilizer_id' => 'integer',
        'branch_id' => 'integer',
        'coordinator_id' => 'integer',
        'non_member_coordinators' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function mobilizer(): BelongsTo
    {
        return $this->belongsTo(Mobilizer::class, 'mobilizer_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }

    public function coordinators(): HasMany
    {
        return $this->hasMany(GroupCoordinator::class, 'group_id');
    }

    public function nonMemberCoordinators(): HasMany
    {
        return $this->hasMany(GroupNonMemberCoordinator::class, 'group_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(GroupDocument::class, 'group_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GroupContribution::class, 'group_id');
    }

    public function nextOfKin(): HasMany
    {
        return $this->hasMany(GroupNextOfKin::class, 'group_id');
    }

    public function getGroupCategoryLabelAttribute(): string
    {
        return $this->group_category ?? '-';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'Active' => 'success',
            'Inactive' => 'secondary',
            'Suspended' => 'warning',
            'Dissolved' => 'danger',
            default => 'secondary',
        };
    }
}
