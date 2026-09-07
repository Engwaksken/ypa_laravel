<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTemplate extends Model
{
    protected $table = 'contract_templates';

    protected $fillable = [
        'template_name',
        'project_type_id',
        'project_category_id',
        'template_key',
        'cover_page',
        'template_body',
        'template_sections',
        'version',
        'is_active',
        'created_by',
        'contract_footer',
        'contract_signature',
        'active_key',
    ];

    protected $casts = [
        'template_name' => 'string',
        'project_type_id' => 'integer',
        'project_category_id' => 'integer',
        'version' => 'integer',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'cover_page' => 'string',
        'template_body' => 'string',
        'contract_footer' => 'string',
        'contract_signature' => 'string',
        'template_sections' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
