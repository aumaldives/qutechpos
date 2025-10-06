<?php

namespace Modules\Woocommerce\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Business;
use App\User;
use Carbon\Carbon;

class WoocommerceSyncConflict extends Model
{
    protected $table = 'woocommerce_sync_conflicts';
    
    protected $fillable = [
        'business_id',
        'entity_type',
        'entity_id',
        'woocommerce_id',
        'conflict_type',
        'field_name',
        'pos_data',
        'woocommerce_data',
        'metadata',
        'resolution_strategy',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'severity',
        'auto_resolvable'
    ];
    
    protected $casts = [
        'pos_data' => 'array',
        'woocommerce_data' => 'array',
        'metadata' => 'array',
        'resolved_at' => 'datetime',
        'auto_resolvable' => 'boolean',
        'entity_id' => 'integer',
        'woocommerce_id' => 'integer',
        'resolved_by' => 'integer'
    ];
    
    const STATUS_OPEN = 'open';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';
    const STATUS_ESCALATED = 'escalated';
    
    const RESOLUTION_POS_WINS = 'pos_wins';
    const RESOLUTION_WC_WINS = 'wc_wins';
    const RESOLUTION_NEWEST_WINS = 'newest_wins';
    const RESOLUTION_MANUAL = 'manual';
    const RESOLUTION_MERGE = 'merge';
    const RESOLUTION_SKIP = 'skip';
    
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    const CONFLICT_DATA_MISMATCH = 'data_mismatch';
    const CONFLICT_DUPLICATE = 'duplicate';
    const CONFLICT_VALIDATION_ERROR = 'validation_error';
    const CONFLICT_MISSING_DEPENDENCY = 'missing_dependency';
    const CONFLICT_VERSION_CONFLICT = 'version_conflict';
    
    /**
     * Business relationship
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
    
    /**
     * Resolved by user relationship
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
    
    /**
     * Scope for open conflicts
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }
    
    /**
     * Scope for resolved conflicts
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }
    
    /**
     * Scope for auto-resolvable conflicts
     */
    public function scopeAutoResolvable($query)
    {
        return $query->where('auto_resolvable', true)
                    ->where('status', self::STATUS_OPEN);
    }
    
    /**
     * Scope for business
     */
    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('business_id', $business_id);
    }
    
    /**
     * Scope for entity
     */
    public function scopeForEntity($query, string $entity_type, int $entity_id)
    {
        return $query->where('entity_type', $entity_type)
                    ->where('entity_id', $entity_id);
    }
    
    /**
     * Scope for severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }
    
    /**
     * Scope for conflict type
     */
    public function scopeByType($query, string $conflict_type)
    {
        return $query->where('conflict_type', $conflict_type);
    }
    
    /**
     * Check if conflict can be auto-resolved
     */
    public function canAutoResolve(): bool
    {
        return $this->auto_resolvable && 
               $this->status === self::STATUS_OPEN &&
               !empty($this->resolution_strategy);
    }
    
    /**
     * Resolve the conflict
     */
    public function resolve(
        string $resolution_strategy,
        string $resolution_notes = null,
        int $resolved_by = null
    ): void {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolution_strategy' => $resolution_strategy,
            'resolution_notes' => $resolution_notes,
            'resolved_by' => $resolved_by,
            'resolved_at' => now()
        ]);
    }
    
    /**
     * Ignore the conflict
     */
    public function ignore(string $reason = null, int $ignored_by = null): void
    {
        $this->update([
            'status' => self::STATUS_IGNORED,
            'resolution_notes' => $reason,
            'resolved_by' => $ignored_by,
            'resolved_at' => now()
        ]);
    }
    
    /**
     * Escalate the conflict
     */
    public function escalate(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_ESCALATED,
            'severity' => self::SEVERITY_HIGH,
            'resolution_notes' => $reason
        ]);
    }
    
    /**
     * Get the conflict age in hours
     */
    public function getAgeInHours(): int
    {
        return $this->created_at->diffInHours(now());
    }
    
    /**
     * Check if conflict is stale (older than specified hours)
     */
    public function isStale(int $hours = 24): bool
    {
        return $this->getAgeInHours() > $hours;
    }
    
    /**
     * Get the recommended resolution based on conflict data
     */
    public function getRecommendedResolution(): string
    {
        // Simple logic for resolution recommendation
        switch ($this->conflict_type) {
            case self::CONFLICT_DATA_MISMATCH:
                return $this->getNewestDataResolution();
                
            case self::CONFLICT_VALIDATION_ERROR:
                return self::RESOLUTION_POS_WINS; // POS data is usually validated
                
            case self::CONFLICT_DUPLICATE:
                return self::RESOLUTION_MERGE;
                
            default:
                return self::RESOLUTION_MANUAL;
        }
    }
    
    /**
     * Determine resolution based on data timestamps
     */
    private function getNewestDataResolution(): string
    {
        $posUpdated = $this->pos_data['updated_at'] ?? null;
        $wcUpdated = $this->woocommerce_data['date_modified'] ?? null;
        
        if (!$posUpdated || !$wcUpdated) {
            return self::RESOLUTION_MANUAL;
        }
        
        $posTime = Carbon::parse($posUpdated);
        $wcTime = Carbon::parse($wcUpdated);
        
        return $posTime->gt($wcTime) ? self::RESOLUTION_POS_WINS : self::RESOLUTION_WC_WINS;
    }
    
    /**
     * Create a new sync conflict
     */
    public static function createConflict(
        int $business_id,
        string $entity_type,
        int $entity_id,
        int $woocommerce_id,
        string $conflict_type,
        array $pos_data,
        array $woocommerce_data,
        array $options = []
    ): self {
        return self::create([
            'business_id' => $business_id,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'woocommerce_id' => $woocommerce_id,
            'conflict_type' => $conflict_type,
            'field_name' => $options['field_name'] ?? null,
            'pos_data' => $pos_data,
            'woocommerce_data' => $woocommerce_data,
            'metadata' => $options['metadata'] ?? [],
            'severity' => $options['severity'] ?? self::SEVERITY_MEDIUM,
            'auto_resolvable' => $options['auto_resolvable'] ?? false,
            'resolution_strategy' => $options['resolution_strategy'] ?? null
        ]);
    }
    
    /**
     * Get conflict statistics for a business
     */
    public static function getBusinessStats(int $business_id): array
    {
        $stats = self::where('business_id', $business_id)
            ->selectRaw('
                status,
                severity,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(resolved_at, NOW()))) as avg_resolution_hours
            ')
            ->groupBy(['status', 'severity'])
            ->get();
            
        $summary = [
            'total' => $stats->sum('count'),
            'open' => $stats->where('status', self::STATUS_OPEN)->sum('count'),
            'resolved' => $stats->where('status', self::STATUS_RESOLVED)->sum('count'),
            'ignored' => $stats->where('status', self::STATUS_IGNORED)->sum('count'),
            'escalated' => $stats->where('status', self::STATUS_ESCALATED)->sum('count'),
            'by_severity' => [
                'low' => $stats->where('severity', self::SEVERITY_LOW)->sum('count'),
                'medium' => $stats->where('severity', self::SEVERITY_MEDIUM)->sum('count'),
                'high' => $stats->where('severity', self::SEVERITY_HIGH)->sum('count'),
                'critical' => $stats->where('severity', self::SEVERITY_CRITICAL)->sum('count')
            ],
            'avg_resolution_hours' => $stats->avg('avg_resolution_hours') ?? 0
        ];
        
        return $summary;
    }
    
    /**
     * Auto-resolve conflicts that can be automatically handled
     */
    public static function autoResolveConflicts(int $business_id, int $limit = 100): int
    {
        $conflicts = self::forBusiness($business_id)
            ->autoResolvable()
            ->limit($limit)
            ->get();
            
        $resolved_count = 0;
        
        foreach ($conflicts as $conflict) {
            try {
                $strategy = $conflict->resolution_strategy ?: $conflict->getRecommendedResolution();
                $conflict->resolve($strategy, 'Auto-resolved by system', null);
                $resolved_count++;
            } catch (\Exception $e) {
                \Log::error('Failed to auto-resolve conflict', [
                    'conflict_id' => $conflict->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $resolved_count;
    }
}