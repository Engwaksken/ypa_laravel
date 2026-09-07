<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mobilizer extends Model
{
    protected $table = 'mobilizers';

    protected $fillable = [
        'first_name',
        'last_name',
        'department',
        'position',
        'contact_number',
        'email',
        'branch_region',
        'supervisor',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'created_by' => 'integer',
    ];

    public function mobilizations(): HasMany
    {
        return $this->hasMany(MemberMobilization::class, 'mobilizer_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}
