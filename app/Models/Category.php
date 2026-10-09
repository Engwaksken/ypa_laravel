<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model for the legacy `categories` table (schema must not be changed):
 *   id           int PK auto_increment
 *   category_name varchar(150) NOT NULL
 *   description   text NULL
 *   status        enum('active','inactive') default 'active'
 *   created_at    timestamp NOT NULL
 *   updated_at    timestamp NOT NULL default current_timestamp ON UPDATE current_timestamp
 *
 * Attribute mappings (code-facing <-> column):
 *   name      <-> category_name
 *   is_active <-> status ('active' = true, 'inactive' = false)
 */
class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * Code-facing keys included in toArray()/toJson().
     */
    protected $appends = ['name', 'is_active'];

    /**
     * Code-facing `name` maps to the legacy `category_name` column.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $attributes['category_name'] ?? null,
            set: fn ($value) => ['category_name' => $value],
        );
    }

    /**
     * Code-facing `is_active` maps to the legacy `status` enum ('active' / 'inactive').
     * Strings such as 'false', '0', 'off', 'no', '' and null are parsed as false;
     * 'true', '1', 'on', 'yes' as true. Unknown values resolve to false.
     */
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => ($attributes['status'] ?? null) === 'active',
            set: fn ($value) => [
                'status' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'active' : 'inactive',
            ],
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
