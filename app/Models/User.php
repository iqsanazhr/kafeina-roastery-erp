<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property Carbon|null $pin_locked_until
 * @property Role|null $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'branch_id',
        'name',
        'email',
        'password',
        'pin_code',
        'pin_failed_attempts',
        'pin_locked_until',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pin_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'pin_failed_attempts' => 'integer',
            'pin_locked_until' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'user_id');
    }

    public function inspectedQcs(): HasMany
    {
        return $this->hasMany(QualityControl::class, 'inspector_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'roaster_id');
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(MaterialRequest::class, 'requested_by_user_id');
    }

    public function dispatchedDeliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class, 'dispatched_by_user_id');
    }

    public function posShifts(): HasMany
    {
        return $this->hasMany(PosShift::class, 'cashier_user_id');
    }

    public function posOrders(): HasMany
    {
        return $this->hasMany(PosOrder::class, 'cashier_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCashiers(Builder $query): Builder
    {
        return $query->whereHas('role', function (Builder $q) {
            $q->whereIn('name', ['cashier', 'branch_cashier', 'CASHIER']);
        });
    }

    public function isSuperAdmin(): bool
    {
        $roleName = strtolower(trim((string) ($this->role?->name ?? '')));

        return in_array($roleName, ['super_admin', 'super-admin', 'admin', 'owner'], true);
    }

    public function hasRole(string|array $roles): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roles = is_array($roles) ? $roles : explode(',', $roles);
        $userRole = strtolower(trim((string) ($this->role?->name ?? '')));

        foreach ($roles as $role) {
            if ($userRole === strtolower(trim($role))) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role?->hasPermission($permission) ?? false;
    }

    /**
     * Check if user has permission to access a specific ERP web module.
     */
    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roleName = strtolower(trim((string) ($this->role?->name ?? '')));

        return match ($module) {
            'dashboard' => in_array($roleName, ['super_admin', 'supervisor', 'store_manager', 'procurement', 'procurement_officer'], true),
            'pos' => in_array($roleName, ['super_admin', 'cashier', 'branch_cashier', 'barista', 'supervisor', 'store_manager'], true),
            'sales' => in_array($roleName, ['super_admin', 'cashier', 'branch_cashier', 'barista', 'supervisor', 'store_manager', 'procurement', 'procurement_officer', 'owner', 'admin'], true),
            'vouchers' => in_array($roleName, ['super_admin', 'supervisor', 'store_manager', 'owner', 'admin'], true),
            'procurement' => in_array($roleName, ['super_admin', 'procurement', 'procurement_officer'], true),
            'qc-lab' => in_array($roleName, ['super_admin', 'qc_inspector', 'procurement', 'procurement_officer'], true),
            'roastery' => in_array($roleName, ['super_admin', 'roaster', 'head_roaster'], true),
            'yield-loss' => in_array($roleName, ['super_admin', 'roaster', 'head_roaster'], true),
            'inventory' => in_array($roleName, ['super_admin', 'warehouse', 'warehouse_staff', 'supervisor', 'store_manager', 'roaster', 'head_roaster'], true),
            'logistics' => in_array($roleName, ['super_admin', 'warehouse', 'warehouse_staff', 'supervisor', 'store_manager'], true),
            'finance' => in_array($roleName, ['super_admin', 'owner', 'admin'], true),
            'settings' => $this->isSuperAdmin(),
            default => false,
        };
    }

    /**
     * Check if user is allowed to switch branches globally or view "Semua Cabang".
     */
    public function canSwitchBranch(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $roleName = strtolower(trim((string) ($this->role?->name ?? '')));

        return in_array($roleName, ['super_admin', 'super-admin', 'owner', 'admin', 'procurement', 'procurement_officer'], true);
    }

    /**
     * Get the slug/key for active branch scoping.
     */
    public function getAssignedBranchSlug(): string
    {
        $code = strtoupper(trim((string) ($this->branch?->code ?? '')));

        return match (true) {
            str_contains($code, 'SENO') || str_contains($code, 'FLAG') => 'senopati',
            str_contains($code, 'BRG') => 'braga',
            str_contains($code, 'MLO') || str_contains($code, 'MALIOBORO') => 'malioboro',
            str_contains($code, 'HQ') || str_contains($code, 'ROAST') || str_contains($code, 'CENTRAL') => 'central',
            default => 'all',
        };
    }
}
