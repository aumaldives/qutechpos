<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApiUsageLog extends Model
{
    use HasFactory;

    protected $table = 'api_usage_logs';

    protected $fillable = [
        'api_key_id',
        'business_id',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'response_status',
        'response_time_ms',
        'request_data',
        'response_data',
        'error_message'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'response_time_ms' => 'integer',
        'response_status' => 'integer'
    ];

    /**
     * Log an API request
     *
     * @param ApiKey $api_key
     * @param string $endpoint
     * @param string $method
     * @param string $ip_address
     * @param string|null $user_agent
     * @param int $response_status
     * @param int $response_time_ms
     * @param array|null $request_data
     * @param array|null $response_data
     * @param string|null $error_message
     * @return self
     */
    public static function logRequest(
        ApiKey $api_key,
        string $endpoint,
        string $method,
        string $ip_address,
        ?string $user_agent,
        int $response_status,
        int $response_time_ms,
        ?array $request_data = null,
        ?array $response_data = null,
        ?string $error_message = null
    ): self {
        return self::create([
            'api_key_id' => $api_key->id,
            'business_id' => $api_key->business_id,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'ip_address' => $ip_address,
            'user_agent' => $user_agent ? substr($user_agent, 0, 500) : null,
            'response_status' => $response_status,
            'response_time_ms' => $response_time_ms,
            'request_data' => $request_data ? self::truncateData($request_data) : null,
            'response_data' => $response_data ? self::truncateData($response_data) : null,
            'error_message' => $error_message
        ]);
    }

    /**
     * Get usage statistics for an API key in a given period
     *
     * @param int $api_key_id
     * @param Carbon|null $start_date
     * @param Carbon|null $end_date
     * @return array
     */
    public static function getUsageStats(int $api_key_id, ?Carbon $start_date = null, ?Carbon $end_date = null): array
    {
        $query = self::where('api_key_id', $api_key_id);

        if ($start_date) {
            $query->where('created_at', '>=', $start_date);
        }

        if ($end_date) {
            $query->where('created_at', '<=', $end_date);
        }

        $logs = $query->get();

        return [
            'total_requests' => $logs->count(),
            'successful_requests' => $logs->where('response_status', '<', 400)->count(),
            'failed_requests' => $logs->where('response_status', '>=', 400)->count(),
            'avg_response_time' => $logs->avg('response_time_ms'),
            'max_response_time' => $logs->max('response_time_ms'),
            'min_response_time' => $logs->min('response_time_ms'),
            'status_breakdown' => $logs->groupBy('response_status')->map->count(),
            'endpoint_breakdown' => $logs->groupBy('endpoint')->map->count(),
            'method_breakdown' => $logs->groupBy('method')->map->count()
        ];
    }

    /**
     * Check rate limiting for an API key
     *
     * @param ApiKey $api_key
     * @return array ['allowed' => bool, 'requests_made' => int, 'requests_remaining' => int]
     */
    public static function checkRateLimit(ApiKey $api_key): array
    {
        $requests_made = self::where('api_key_id', $api_key->id)
            ->where('created_at', '>=', now()->subMinute())
            ->count();

        $requests_remaining = max(0, $api_key->rate_limit_per_minute - $requests_made);

        return [
            'allowed' => $requests_made < $api_key->rate_limit_per_minute,
            'requests_made' => $requests_made,
            'requests_remaining' => $requests_remaining,
            'reset_time' => now()->addMinute()
        ];
    }

    /**
     * Truncate data for storage (prevent massive request/response data)
     *
     * @param array $data
     * @param int $max_size
     * @return array
     */
    private static function truncateData(array $data, int $max_size = 5000): array
    {
        $json_string = json_encode($data);
        
        if (strlen($json_string) <= $max_size) {
            return $data;
        }

        // Try to keep important fields and truncate others
        $truncated = [];
        $current_size = 0;
        
        foreach ($data as $key => $value) {
            $item_json = json_encode([$key => $value]);
            
            if ($current_size + strlen($item_json) > $max_size) {
                $truncated['_truncated'] = true;
                $truncated['_original_size'] = strlen($json_string);
                break;
            }
            
            $truncated[$key] = $value;
            $current_size += strlen($item_json);
        }

        return $truncated;
    }

    /**
     * Relationships
     */
    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    /**
     * Scopes
     */
    public function scopeForApiKey($query, int $api_key_id)
    {
        return $query->where('api_key_id', $api_key_id);
    }

    public function scopeForBusiness($query, int $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('response_status', '<', 400);
    }

    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    public function scopeInLastMinute($query)
    {
        return $query->where('created_at', '>=', now()->subMinute());
    }

    public function scopeInLastHour($query)
    {
        return $query->where('created_at', '>=', now()->subHour());
    }

    public function scopeInLastDay($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }
}
