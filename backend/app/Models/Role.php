<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    /**
     * Admin users assigned to this role (aliased as users for controller compatibility).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_roles', 'role_id', 'admin_id');
    }

    /**
     * Admin users assigned to this role.
     */
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(Admin::class, 'admin_roles', 'role_id', 'admin_id');
    }

    /**
     * Check if role has a specific permission by slug.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->is_system && $this->slug === 'super-admin') {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('slug', $permissionSlug);
        }

        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }
}
