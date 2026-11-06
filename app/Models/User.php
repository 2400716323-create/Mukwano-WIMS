<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMINISTRATOR = 'administrator';

    public const ROLE_WAREHOUSE_MANAGER = 'warehouse_manager';

    public const ROLE_STOREKEEPER = 'storekeeper';

    public const ROLE_PROCUREMENT_OFFICER = 'procurement_officer';

    public const ROLE_DISPATCH_OFFICER = 'dispatch_officer';

    public const ROLE_AUDITOR = 'auditor';

    public const ROLES = [
        self::ROLE_ADMINISTRATOR,
        self::ROLE_WAREHOUSE_MANAGER,
        self::ROLE_STOREKEEPER,
        self::ROLE_PROCUREMENT_OFFICER,
        self::ROLE_DISPATCH_OFFICER,
        self::ROLE_AUDITOR,
    ];

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

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
        ];
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function createdPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function approvedPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'approved_by');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'received_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'performed_by');
    }

    public static function normalizeRole(?string $role): ?string
    {
        return match ($role) {
            'admin' => self::ROLE_ADMINISTRATOR,
            'supervisor' => self::ROLE_WAREHOUSE_MANAGER,
            'user' => self::ROLE_STOREKEEPER,
            default => $role,
        };
    }

    public function isAdministrator(): bool
    {
        return in_array($this->role, [self::ROLE_ADMINISTRATOR, 'admin'], true);
    }

    public function isWarehouseManager(): bool
    {
        return in_array($this->role, [self::ROLE_WAREHOUSE_MANAGER, 'supervisor'], true);
    }

    public function isStorekeeper(): bool
    {
        return in_array($this->role, [self::ROLE_STOREKEEPER, 'user'], true);
    }

    public function isProcurementOfficer(): bool
    {
        return $this->role === self::ROLE_PROCUREMENT_OFFICER;
    }

    public function isDispatchOfficer(): bool
    {
        return $this->isAdministrator() || $this->role === self::ROLE_DISPATCH_OFFICER || $this->isWarehouseManager();
    }

    public function isAuditor(): bool
    {
        return $this->role === self::ROLE_AUDITOR;
    }

    /**
     * Legacy & convenience supervisor check (administrator or warehouse manager).
     */
    public function isSupervisor(): bool
    {
        return $this->isAdministrator() || $this->isWarehouseManager();
    }

    /**
     * Determine whether the user matches one or more roles.
     *
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        $normalizedCurrent = self::normalizeRole($this->role);

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($normalizedCurrent === self::normalizeRole($role) || $this->role === $role) {
                    return true;
                }
            }

            return false;
        }

        return $normalizedCurrent === self::normalizeRole($roles) || $this->role === $roles;
    }

    /**
     * Check if user role has a specific named permission in config/roles.php.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdministrator()) {
            return true;
        }

        $normalizedRole = self::normalizeRole($this->role);
        $permissions = config("roles.roles.{$normalizedRole}.permissions", []);

        return in_array($permission, $permissions, true);
    }

    /**
     * Get human-readable role label.
     */
    public function getRoleLabelAttribute(): string
    {
        $normalizedRole = self::normalizeRole($this->role);

        return config("roles.roles.{$normalizedRole}.label", ucfirst(str_replace('_', ' ', (string) $this->role)));
    }
}
