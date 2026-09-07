<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'branches';

    /**
     * The branches table has no updated_at column.
     */
    public const UPDATED_AT = null;

    protected $fillable = ['name', 'location', 'contact', 'branch_email', 'status'];
}