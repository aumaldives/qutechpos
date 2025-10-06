<?php

namespace Modules\Superadmin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'custom_permissions' => 'array',
        'is_per_location_pricing' => 'boolean',
    ];

    /**
     * Scope a query to only include active packages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Returns the list of active pakages
     *
     * @return object
     */
    public static function listPackages($exlude_private = false)
    {
        $packages = Package::active()
                        ->orderby('sort_order');

        if ($exlude_private) {
            $packages->notPrivate();
        }

        return $packages->get();
    }

    /**
     * Scope a query to exclude private packages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotPrivate($query)
    {
        return $query->where('is_private', 0);
    }

    /**
     * Calculate price for per-location package
     *
     * @param int $location_count
     * @return float
     */
    public function calculatePrice($location_count = null)
    {
        if (!$this->is_per_location_pricing) {
            return $this->price;
        }

        $locations = $location_count ?? $this->location_count;
        
        // Ensure within min/max bounds
        if ($locations < $this->min_locations) {
            $locations = $this->min_locations;
        }
        
        if ($this->max_locations > 0 && $locations > $this->max_locations) {
            $locations = $this->max_locations;
        }

        return $this->price_per_location * $locations;
    }

    /**
     * Get effective location count for package
     *
     * @param int $requested_locations
     * @return int
     */
    public function getEffectiveLocationCount($requested_locations = null)
    {
        if (!$this->is_per_location_pricing) {
            return $this->location_count;
        }

        return $requested_locations ?? $this->min_locations;
    }
}
