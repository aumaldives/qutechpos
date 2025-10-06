<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Woocommerce\Entities\WoocommerceSyncError;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;
use Modules\Woocommerce\Utils\WoocommerceSyncErrorHandler;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncHealthController extends Controller
{
    /**
     * Display the sync health dashboard
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        
        // Get business locations with WooCommerce settings
        $locations = \App\BusinessLocation::where('business_id', $business_id)
                                         ->whereHas('woocommerceLocationSettings')
                                         ->with('woocommerceLocationSettings')
                                         ->get();

        // Get overall health metrics
        $healthMetrics = $this->getHealthMetrics($business_id);
        
        // Get recent sync activities
        $recentActivities = $this->getRecentSyncActivities($business_id, 10);
        
        // Get error trends (last 30 days)
        $errorTrends = $this->getErrorTrends($business_id, 30);

        return view('woocommerce::sync_health.index', compact(
            'locations', 
            'healthMetrics', 
            'recentActivities', 
            'errorTrends'
        ));
    }

    /**
     * Get location-specific health details
     */
    public function locationHealth(Request $request, $locationId)
    {
        $business_id = $request->session()->get('user.business_id');
        
        $location = \App\BusinessLocation::where('business_id', $business_id)
                                        ->where('id', $locationId)
                                        ->whereHas('woocommerceLocationSettings')
                                        ->with('woocommerceLocationSettings')
                                        ->firstOrFail();

        // Get detailed health metrics for this location
        $locationMetrics = $this->getLocationHealthMetrics($business_id, $locationId);
        
        // Get sync history for this location
        $syncHistory = $this->getLocationSyncHistory($business_id, $locationId, 50);
        
        // Get error breakdown by category
        $errorBreakdown = $this->getLocationErrorBreakdown($business_id, $locationId);

        return view('woocommerce::sync_health.location', compact(
            'location', 
            'locationMetrics', 
            'syncHistory', 
            'errorBreakdown'
        ));
    }

    /**
     * Get API endpoint for health metrics (for real-time updates)
     */
    public function healthMetricsApi(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $locationId = $request->get('location_id');

        if ($locationId) {
            $metrics = $this->getLocationHealthMetrics($business_id, $locationId);
        } else {
            $metrics = $this->getHealthMetrics($business_id);
        }

        return response()->json($metrics);
    }

    /**
     * Get sync activity feed for dashboard
     */
    public function activityFeedApi(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $limit = $request->get('limit', 20);
        $offset = $request->get('offset', 0);

        $activities = $this->getRecentSyncActivities($business_id, $limit, $offset);

        return response()->json([
            'activities' => $activities,
            'has_more' => count($activities) >= $limit
        ]);
    }

    /**
     * Get overall health metrics for business
     */
    private function getHealthMetrics($businessId)
    {
        $timeFrame24h = Carbon::now()->subHours(24);
        $timeFrame7d = Carbon::now()->subDays(7);

        // Get sync statistics
        $totalSyncs24h = $this->getSyncCount($businessId, $timeFrame24h);
        $successfulSyncs24h = $this->getSuccessfulSyncCount($businessId, $timeFrame24h);
        $failedSyncs24h = $totalSyncs24h - $successfulSyncs24h;

        // Get error statistics
        $totalErrors24h = WoocommerceSyncError::where('business_id', $businessId)
                                            ->where('created_at', '>=', $timeFrame24h)
                                            ->count();

        $criticalErrors24h = WoocommerceSyncError::where('business_id', $businessId)
                                               ->where('created_at', '>=', $timeFrame24h)
                                               ->where('severity_level', WoocommerceSyncError::SEVERITY_CRITICAL)
                                               ->count();

        $unresolvedErrors = WoocommerceSyncError::where('business_id', $businessId)
                                              ->where('is_resolved', false)
                                              ->count();

        // Calculate health score (0-100)
        $healthScore = $this->calculateHealthScore(
            $totalSyncs24h,
            $successfulSyncs24h,
            $totalErrors24h,
            $criticalErrors24h,
            $unresolvedErrors
        );

        // Get location statuses
        $locationCount = \App\BusinessLocation::where('business_id', $businessId)
                                            ->whereHas('woocommerceLocationSettings')
                                            ->count();

        $healthyLocations = $this->getHealthyLocationCount($businessId);

        return [
            'health_score' => $healthScore,
            'sync_success_rate_24h' => $totalSyncs24h > 0 ? round(($successfulSyncs24h / $totalSyncs24h) * 100, 1) : 100,
            'total_syncs_24h' => $totalSyncs24h,
            'successful_syncs_24h' => $successfulSyncs24h,
            'failed_syncs_24h' => $failedSyncs24h,
            'total_errors_24h' => $totalErrors24h,
            'critical_errors_24h' => $criticalErrors24h,
            'unresolved_errors' => $unresolvedErrors,
            'location_count' => $locationCount,
            'healthy_locations' => $healthyLocations,
            'locations_with_issues' => $locationCount - $healthyLocations
        ];
    }

    /**
     * Get health metrics for specific location
     */
    private function getLocationHealthMetrics($businessId, $locationId)
    {
        $timeFrame24h = Carbon::now()->subHours(24);

        $locationSyncs24h = $this->getSyncCount($businessId, $timeFrame24h, $locationId);
        $locationSuccessful24h = $this->getSuccessfulSyncCount($businessId, $timeFrame24h, $locationId);

        $locationErrors24h = WoocommerceSyncError::where('business_id', $businessId)
                                               ->where('location_id', $locationId)
                                               ->where('created_at', '>=', $timeFrame24h)
                                               ->count();

        $locationCriticalErrors24h = WoocommerceSyncError::where('business_id', $businessId)
                                                        ->where('location_id', $locationId)
                                                        ->where('created_at', '>=', $timeFrame24h)
                                                        ->where('severity_level', WoocommerceSyncError::SEVERITY_CRITICAL)
                                                        ->count();

        $locationUnresolved = WoocommerceSyncError::where('business_id', $businessId)
                                                 ->where('location_id', $locationId)
                                                 ->where('is_resolved', false)
                                                 ->count();

        return [
            'sync_success_rate_24h' => $locationSyncs24h > 0 ? round(($locationSuccessful24h / $locationSyncs24h) * 100, 1) : 100,
            'total_syncs_24h' => $locationSyncs24h,
            'successful_syncs_24h' => $locationSuccessful24h,
            'failed_syncs_24h' => $locationSyncs24h - $locationSuccessful24h,
            'total_errors_24h' => $locationErrors24h,
            'critical_errors_24h' => $locationCriticalErrors24h,
            'unresolved_errors' => $locationUnresolved,
            'last_sync_at' => $this->getLastSyncTime($businessId, $locationId),
            'next_scheduled_sync' => $this->getNextScheduledSync($locationId)
        ];
    }

    /**
     * Get recent sync activities
     */
    private function getRecentSyncActivities($businessId, $limit = 20, $offset = 0)
    {
        // Get sync logs if they exist
        $syncLogs = DB::table('woocommerce_sync_logs')
                     ->where('business_id', $businessId)
                     ->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->offset($offset)
                     ->get()
                     ->map(function ($log) {
                         return [
                             'type' => 'sync',
                             'action' => ucfirst($log->sync_type) . ' Sync',
                             'location_id' => $log->location_id,
                             'location_name' => $this->getLocationName($log->location_id),
                             'status' => $log->status,
                             'message' => $log->message ?? 'Sync ' . $log->status,
                             'created_at' => Carbon::parse($log->created_at),
                             'icon' => $this->getSyncIcon($log->sync_type, $log->status),
                             'color' => $this->getSyncColor($log->status)
                         ];
                     });

        // Get error logs
        $errorLogs = WoocommerceSyncError::where('business_id', $businessId)
                                        ->with(['location', 'business'])
                                        ->orderBy('created_at', 'desc')
                                        ->limit($limit)
                                        ->offset($offset)
                                        ->get()
                                        ->map(function ($error) {
                                            return [
                                                'type' => 'error',
                                                'action' => 'Sync Error',
                                                'location_id' => $error->location_id,
                                                'location_name' => $error->location->name ?? 'Unknown Location',
                                                'status' => $error->severity_level,
                                                'message' => $error->error_message,
                                                'created_at' => $error->created_at,
                                                'icon' => $this->getErrorIcon($error->severity_level),
                                                'color' => $this->getErrorColor($error->severity_level),
                                                'category' => $error->error_category
                                            ];
                                        });

        // Merge and sort by date
        $activities = collect($syncLogs)
                        ->merge($errorLogs)
                        ->sortByDesc('created_at')
                        ->take($limit)
                        ->values();

        return $activities->toArray();
    }

    /**
     * Get error trends data
     */
    private function getErrorTrends($businessId, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        
        $trends = WoocommerceSyncError::where('business_id', $businessId)
                                     ->where('created_at', '>=', $startDate)
                                     ->selectRaw('DATE(created_at) as date, COUNT(*) as error_count, error_category')
                                     ->groupBy('date', 'error_category')
                                     ->orderBy('date')
                                     ->get()
                                     ->groupBy('date');

        return $trends->toArray();
    }

    // Helper methods
    private function getSyncCount($businessId, $timeFrame, $locationId = null)
    {
        $query = DB::table('woocommerce_sync_logs')
                   ->where('business_id', $businessId)
                   ->where('created_at', '>=', $timeFrame);
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        return $query->count();
    }

    private function getSuccessfulSyncCount($businessId, $timeFrame, $locationId = null)
    {
        $query = DB::table('woocommerce_sync_logs')
                   ->where('business_id', $businessId)
                   ->where('created_at', '>=', $timeFrame)
                   ->where('status', 'completed');
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        return $query->count();
    }

    private function calculateHealthScore($totalSyncs, $successfulSyncs, $totalErrors, $criticalErrors, $unresolvedErrors)
    {
        $score = 100;
        
        // Deduct points based on sync failure rate
        if ($totalSyncs > 0) {
            $failureRate = ($totalSyncs - $successfulSyncs) / $totalSyncs;
            $score -= $failureRate * 30; // Max 30 points deduction
        }
        
        // Deduct points for errors
        $score -= min($totalErrors * 2, 20); // Max 20 points for regular errors
        $score -= min($criticalErrors * 5, 30); // Max 30 points for critical errors
        $score -= min($unresolvedErrors * 3, 20); // Max 20 points for unresolved errors
        
        return max(0, round($score));
    }

    private function getHealthyLocationCount($businessId)
    {
        // A location is healthy if it has < 3 unresolved errors and > 80% success rate in last 24h
        $locations = \App\BusinessLocation::where('business_id', $businessId)
                                         ->whereHas('woocommerceLocationSettings')
                                         ->get();
        
        $healthyCount = 0;
        $timeFrame24h = Carbon::now()->subHours(24);
        
        foreach ($locations as $location) {
            $unresolvedErrors = WoocommerceSyncError::where('business_id', $businessId)
                                                  ->where('location_id', $location->id)
                                                  ->where('is_resolved', false)
                                                  ->count();
            
            $totalSyncs = $this->getSyncCount($businessId, $timeFrame24h, $location->id);
            $successfulSyncs = $this->getSuccessfulSyncCount($businessId, $timeFrame24h, $location->id);
            
            $successRate = $totalSyncs > 0 ? ($successfulSyncs / $totalSyncs) * 100 : 100;
            
            if ($unresolvedErrors < 3 && $successRate >= 80) {
                $healthyCount++;
            }
        }
        
        return $healthyCount;
    }

    private function getLocationName($locationId)
    {
        $location = \App\BusinessLocation::find($locationId);
        return $location ? $location->name : 'Unknown Location';
    }

    private function getSyncIcon($syncType, $status)
    {
        $icons = [
            'products' => 'fas fa-box',
            'orders' => 'fas fa-shopping-cart',
            'customers' => 'fas fa-users',
            'inventory' => 'fas fa-warehouse'
        ];
        
        return $icons[$syncType] ?? 'fas fa-sync';
    }

    private function getSyncColor($status)
    {
        $colors = [
            'completed' => 'success',
            'failed' => 'danger',
            'processing' => 'warning'
        ];
        
        return $colors[$status] ?? 'secondary';
    }

    private function getErrorIcon($severity)
    {
        $icons = [
            'critical' => 'fas fa-exclamation-triangle',
            'high' => 'fas fa-exclamation-circle',
            'medium' => 'fas fa-info-circle',
            'low' => 'fas fa-info'
        ];
        
        return $icons[$severity] ?? 'fas fa-exclamation';
    }

    private function getErrorColor($severity)
    {
        $colors = [
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary'
        ];
        
        return $colors[$severity] ?? 'secondary';
    }

    private function getLastSyncTime($businessId, $locationId)
    {
        $lastSync = DB::table('woocommerce_sync_logs')
                     ->where('business_id', $businessId)
                     ->where('location_id', $locationId)
                     ->orderBy('created_at', 'desc')
                     ->first();
        
        return $lastSync ? Carbon::parse($lastSync->created_at) : null;
    }

    private function getNextScheduledSync($locationId)
    {
        // This would integrate with your scheduling system
        // For now, return a placeholder
        return Carbon::now()->addHour();
    }

    private function getLocationSyncHistory($businessId, $locationId, $limit)
    {
        return DB::table('woocommerce_sync_logs')
                 ->where('business_id', $businessId)
                 ->where('location_id', $locationId)
                 ->orderBy('created_at', 'desc')
                 ->limit($limit)
                 ->get();
    }

    private function getLocationErrorBreakdown($businessId, $locationId)
    {
        return WoocommerceSyncError::where('business_id', $businessId)
                                  ->where('location_id', $locationId)
                                  ->where('created_at', '>=', Carbon::now()->subDays(30))
                                  ->groupBy('error_category', 'severity_level')
                                  ->selectRaw('error_category, severity_level, COUNT(*) as count')
                                  ->get()
                                  ->groupBy('error_category');
    }
}