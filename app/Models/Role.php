<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }

    public function scopeNamed(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

    public function scopeSuperAdmin(Builder $query): Builder
    {
        return $query->where('name', 'super_admin');
    }

    public function scopeCashier(Builder $query): Builder
    {
        return $query->whereIn('name', ['cashier', 'branch_cashier']);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->name === 'super_admin') {
            return true;
        }

        $perms = $this->permissions ?? [];
        if (in_array('*', $perms, true) || in_array($permission, $perms, true)) {
            return true;
        }

        foreach ($perms as $perm) {
            if (str_ends_with($perm, '.*')) {
                $prefix = substr($perm, 0, -2);
                if (str_starts_with($permission, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
