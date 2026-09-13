<?php

namespace App\Models;

use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * The users table has NO updated_at column. Disabling it prevents
     * every save from failing with "Unknown column 'updated_at'".
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'branch_id',
        'profile_pic',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected $casts = [
        'code_expires' => 'datetime',
        'branch_id' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->normalizedRole(), ['supper_admin', 'admin', 'director'], true);
    }

    public function normalizedRole(): string
    {
        return app(PermissionService::class)->normalizeRole($this->role);
    }

    public function hasPermission(string|array $permissions): bool
    {
        return app(PermissionService::class)->can($permissions, $this);
    }
}
