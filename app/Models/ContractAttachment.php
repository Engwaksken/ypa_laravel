<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAttachment extends Model
{
    protected $table = 'contract_attachments';

    protected $fillable = [
        'member_id',
        'group_id',
        'contract_id',
        'document_type',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'payment_transaction_id',
        'amount',
        'is_visible_to_member',
        'requires_review',
        'reviewed_at',
        'reviewed_by',
        'uploaded_by',
    ];

    protected $casts = [
        'contract_id' => 'integer',
        'member_id' => 'integer',
        'group_id' => 'integer',
        'branch_id' => 'integer',
        'uploaded_by' => 'integer',
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
