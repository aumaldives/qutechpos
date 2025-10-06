<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApiKey extends Model
{
    use HasFactory;

    protected $table = 'api_keys';

    protected $fillable = [
        'business_id',
        'user_id', 
        'name',
        'key_prefix',
        'key_hash',
        'last_4',
        'abilities',
        'access_level',
        'is_internal',
        'created_by_superadmin_id',
        'rate_limit_per_minute',
        'last_used_at',
        'expires_at',
        'is_active'
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_internal' => 'boolean',
        'rate_limit_per_minute' => 'integer'
    ];

    protected $hidden = [
        'key_hash'
    ];

    /**
     * Generate a new API key for a business
     *
     * @param int $business_id
     * @param int|null $user_id
     * @param string $name
     * @param array|null $abilities
     * @param int $rate_limit_per_minute
     * @param Carbon|null $expires_at
     * @return array ['api_key' => string, 'model' => ApiKey]
     */
    public static function generateKey(
        int $business_id,
        ?int $user_id,
        string $name,
        ?array $abilities = null,
        int $rate_limit_per_minute = 60,
        ?Carbon $expires_at = null
    ): array {
        // Generate unique prefix (ib_ + 4 random characters)
        do {
            $prefix = 'ib_' . Str::random(4);
        } while (self::where('key_prefix', $prefix)->exists());

        // Generate secure random key (64 characters total)
        $key_suffix = Str::random(60); // 60 chars + 4 prefix chars = 64 total
        $full_key = $prefix . $key_suffix;
        
        // Hash the full key for storage
        $key_hash = hash('sha256', $full_key);
        
        // Get last 4 characters for display
        $last_4 = substr($full_key, -4);

        $api_key = self::create([
            'business_id' => $business_id,
            'user_id' => $user_id,
            'name' => $name,
            'key_prefix' => $prefix,
            'key_hash' => $key_hash,
            'last_4' => $last_4,
            'abilities' => $abilities ?? ['read', 'write'],
            'rate_limit_per_minute' => $rate_limit_per_minute,
            'expires_at' => $expires_at,
            'is_active' => true
        ]);

        return [
            'api_key' => $full_key,
            'model' => $api_key
        ];
    }

    /**
     * Find API key by token and verify it's valid
     *
     * @param string $token
     * @return self|null
     */
    public static function findValidKey(string $token): ?self
    {
        $key_hash = hash('sha256', $token);
        
        return self::where('key_hash', $key_hash)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Check if API key has specific ability/permission
     *
     * @param string $ability
     * @return bool
     */
    public function hasAbility(string $ability): bool
    {
        if (!$this->abilities) {
            return false;
        }

        return in_array($ability, $this->abilities) || in_array('*', $this->abilities);
    }

    /**
     * Check if API key has specific access level
     *
     * @param string $level
     * @return bool
     */
    public function hasAccessLevel(string $level): bool
    {
        $levels = [
            'business' => 1,
            'system' => 2,
            'superadmin' => 3
        ];

        $currentLevel = $levels[$this->access_level ?? 'business'] ?? 1;
        $requiredLevel = $levels[$level] ?? 1;

        return $currentLevel >= $requiredLevel;
    }

    /**
     * Check if API key can access system-level endpoints
     *
     * @return bool
     */
    public function canAccessSystemEndpoints(): bool
    {
        return $this->hasAccessLevel('system');
    }

    /**
     * Check if API key can access superadmin endpoints
     *
     * @return bool
     */
    public function canAccessSuperadminEndpoints(): bool
    {
        return $this->hasAccessLevel('superadmin');
    }

    /**
     * Update last used timestamp
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if key is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Revoke/deactivate the API key
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get display name for the key (prefix + last 4)
     *
     * @return string
     */
    public function getDisplayKeyAttribute(): string
    {
        return $this->key_prefix . '...' . $this->last_4;
    }

    /**
     * Relationships
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
