<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberBankDetail extends Model
{
    protected $table = 'member_bank_details';

    protected $fillable = [
        'member_id',
        'bank_account',
        'account_name',
        'bank_name',
        'bank_branch',
    ];

    protected $casts = [
        'member_id' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
