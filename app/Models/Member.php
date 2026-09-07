<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    protected $table = 'members';

    /**
     * Fillable fields for mass assignment. The audit fields (user_id /
     * created_by / updated_by) are set explicitly by the controllers via
     * validated() payloads and MemberService::buildMemberPayload() — they
     * never come from request input.
     */
    protected $fillable = [
        'branch_id',
        'user_id',
        'group_id',
        'mobilizer_id',
        'membership_id',
        'first_name',
        'last_name',
        'other_name',
        'member_photo',
        'date_of_birth',
        'sex',
        'nin',
        'tin_number',
        'nationality',
        'address',
        'country',
        'region',
        'district_residence',
        'district',
        'employment_status',
        'employment_other',
        'marital_status',
        'children_count',
        'source',
        'source_station',
        'source_other',
        'email',
        'telephone1',
        'telephone2',
        'mother_name',
        'mother_phone',
        'father_name',
        'father_phone',
        'account_type',
        'bank_account',
        'bank_account_name',
        'bank_name',
        'bank_branch',
        'membership_status',
        'gender',
        'created_by',
        'updated_by',
        'membership_payment_transaction_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'children_count' => 'integer',
        'branch_id' => 'integer',
        'mobilizer_id' => 'integer',
        'user_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'membership_payment_transaction_id' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function nextOfKin(): HasMany
    {
        return $this->hasMany(MemberNextOfKin::class, 'member_id');
    }

    public function bankDetail(): HasOne
    {
        return $this->hasOne(MemberBankDetail::class, 'member_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(MemberDependent::class, 'member_id');
    }

    public function mobilizations(): HasMany
    {
        return $this->hasMany(MemberMobilization::class, 'member_id');
    }

    public function mobilizer(): BelongsTo
    {
        return $this->belongsTo(Mobilizer::class, 'mobilizer_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(
            ($this->first_name ?? '') . ' ' .
            ($this->last_name ?? '') .
            (!empty($this->other_name) ? ' ' . $this->other_name : '')
        );
    }
}
