<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberNextOfKin extends Model
{
    protected $table = 'member_next_of_kin';

    protected $fillable = [
        'member_id',
        'first_name',
        'last_name',
        'nin',
        'date_of_birth',
        'dob',
        'relationship',
        'address',
        'phone',
        'telephone1',
        'telephone2',
        'email',
        'photo',
        'nok_photo',
        'photo_path',
        'image_url',
        'country',
        'region',
        'district_residence',
        'district',
        'subcounty',
        'parish',
        'village',
        'occupation',
        'gender',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'dob' => 'date',
        'member_id' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(
            ($this->first_name ?? '') . ' ' .
            ($this->last_name ?? '')
        );
    }
}
