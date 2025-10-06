<?php

namespace Modules\Woocommerce\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Modules\Woocommerce\Entities\WoocommerceSyncProgress;

class SyncProgressController extends Controller
{
    /**
     * Server-Sent Events endpoint for real-time sync progress
     */
    public function progressStream(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        $location_id = $request->get('location_id');
        $sync_id = $request->get('sync_id');

        // Set headers for Server-Sent Events
        $response = Response::stream(function () use ($business_id, $location_id, $sync_id) {
            // Set connection timeout
            set_time_limit(300); // 5 minutes max
            
            while (true) {
                // Get current progress data
                $progressData = $this->getCurrentProgress($business_id, $location_id, $sync_id);
                
                // Send the data as SSE
                echo "data: " . json_encode($progressData) . "\n\n";
                
                // Flush output buffer
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
                
                // Break if sync is completed or failed
                if (in_array($progressData['status'], ['completed', 'failed', 'cancelled'])) {
                    break;
                }
                
                // Sleep for 1 second before next update
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable Nginx buffering
        ]);

        return $response;
    }

    /**
     * Get current sync progress data
     */
    private function getCurrentProgress($business_id, $location_id = null, $sync_id = null)
    {
        if ($sync_id) {
            // Get specific sync progress
            $progress = WoocommerceSyncProgress::find($sync_id);
            
            if (!$progress || $progress->business_id !== $business_id) {
                return [
                    'status' => 'not_found',
                    'message' => 'Sync not found',
                    'timestamp' => now()->toISOString()
                ];
            }
            
            return [
                'sync_id' => $progress->id,
                'location_id' => $progress->location_id,
                'location_name' => $progress->location->name ?? 'Unknown',
                'sync_type' => $progress->sync_type,
                'status' => $progress->status,
                'progress_percentage' => $progress->progress_percentage,
                'current_step' => $progress->current_step,
                'total_steps' => $progress->total_steps,
                'records_processed' => $progress->records_processed,
                'records_total' => $progress->records_total,
                'records_success' => $progress->records_success,
                'records_failed' => $progress->records_failed,
                'current_operation' => $progress->current_operation,
                'estimated_time_remaining' => $progress->estimated_time_remaining,
                'started_at' => $progress->started_at?->toISOString(),
                'updated_at' => $progress->updated_at?->toISOString(),
                'error_message' => $progress->error_message,
                'timestamp' => now()->toISOString()
            ];
        }

        if ($location_id) {
            // Get active syncs for specific location
            $activeSyncs = WoocommerceSyncProgress::where('business_id', $business_id)
                                                 ->where('location_id', $location_id)
                                                 ->where('status', 'processing')
                                                 ->with('location')
                                                 ->get();
        } else {
            // Get all active syncs for business
            $activeSyncs = WoocommerceSyncProgress::where('business_id', $business_id)
                                                 ->where('status', 'processing')
                                                 ->with('location')
                                                 ->get();
        }

        return [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'active_syncs' => $activeSyncs->map(function ($sync) {
                return [
                    'sync_id' => $sync->id,
                    'location_id' => $sync->location_id,
                    'location_name' => $sync->location->name ?? 'Unknown',
                    'sync_type' => $sync->sync_type,
                    'status' => $sync->status,
                    'progress_percentage' => $sync->progress_percentage,
                    'current_operation' => $sync->current_operation,
                    'records_processed' => $sync->records_processed,
                    'records_total' => $sync->records_total,
                    'estimated_time_remaining' => $sync->estimated_time_remaining
                ];
            }),
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Get sync progress data (REST endpoint)
     */
    public function getProgress(Request $request)
    {
        $business_id = $request->session()->get('business.id');
        $location_id = $request->get('location_id');
        $sync_id = $request->get('sync_id');

        $progressData = $this->getCurrentProgress($business_id, $location_id, $sync_id);

        return response()->json($progressData);
    }

    /**
     * Cancel a running sync
     */
    public function cancelSync(Request $request, $sync_id)
    {
        $business_id = $request->session()->get('business.id');
        
        $progress = WoocommerceSyncProgress::where('id', $sync_id)
                                          ->where('business_id', $business_id)
                                          ->where('status', 'processing')
                                          ->first();

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Sync not found or not running'
            ], 404);
        }

        // Mark sync as cancelled
        $progress->update([
            'status' => 'cancelled',
            'current_operation' => 'Cancellation requested by user',
            'completed_at' => now()
        ]);

        // Set cancellation flag in cache for the job to check
        Cache::put("sync_cancel_{$sync_id}", true, 3600); // 1 hour TTL

        return response()->json([
            'success' => true,
            'message' => 'Sync cancellation requested'
        ]);
    }

    /**
     * Pause a running sync
     */
    public function pauseSync(Request $request, $sync_id)
    {
        $business_id = $request->session()->get('business.id');
        
        $progress = WoocommerceSyncProgress::where('id', $sync_id)
                                          ->where('business_id', $business_id)
                                          ->where('status', 'processing')
                                          ->first();

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Sync not found or not running'
            ], 404);
        }

        // Mark sync as paused
        $progress->update([
            'status' => 'paused',
            'current_operation' => 'Sync paused by user'
        ]);

        // Set pause flag in cache
        Cache::put("sync_pause_{$sync_id}", true, 3600);

        return response()->json([
            'success' => true,
            'message' => 'Sync paused successfully'
        ]);
    }

    /**
     * Resume a paused sync
     */
    public function resumeSync(Request $request, $sync_id)
    {
        $business_id = $request->session()->get('business.id');
        
        $progress = WoocommerceSyncProgress::where('id', $sync_id)
                                          ->where('business_id', $business_id)
                                          ->where('status', 'paused')
                                          ->first();

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'Sync not found or not paused'
            ], 404);
        }

        // Resume sync
        $progress->update([
            'status' => 'processing',
            'current_operation' => 'Sync resumed by user'
        ]);

        // Remove pause flag
        Cache::forget("sync_pause_{$sync_id}");

        return response()->json([
            'success' => true,
            'message' => 'Sync resumed successfully'
        ]);
    }

    /**
     * Get sync statistics for dashboard
     */
    public function getSyncStats(Request $request)
    {
        $business_id = $request->session()->get('business.id');

        // Get active syncs count
        $activeSyncs = WoocommerceSyncProgress::where('business_id', $business_id)
                                             ->where('status', 'processing')
                                             ->count();

        // Get paused syncs count
        $pausedSyncs = WoocommerceSyncProgress::where('business_id', $business_id)
                                             ->where('status', 'paused')
                                             ->count();

        // Get recent completed syncs (last 24 hours)
        $recentCompleted = WoocommerceSyncProgress::where('business_id', $business_id)
                                                 ->where('status', 'completed')
                                                 ->where('completed_at', '>=', now()->subDay())
                                                 ->count();

        // Get recent failed syncs (last 24 hours)
        $recentFailed = WoocommerceSyncProgress::where('business_id', $business_id)
                                              ->where('status', 'failed')
                                              ->where('completed_at', '>=', now()->subDay())
                                              ->count();

        // Get average sync duration (last 10 completed syncs)
        $avgDuration = WoocommerceSyncProgress::where('business_id', $business_id)
                                             ->where('status', 'completed')
                                             ->whereNotNull('duration_seconds')
                                             ->latest()
                                             ->limit(10)
                                             ->avg('duration_seconds');

        return response()->json([
            'active_syncs' => $activeSyncs,
            'paused_syncs' => $pausedSyncs,
            'recent_completed' => $recentCompleted,
            'recent_failed' => $recentFailed,
            'average_duration_seconds' => round($avgDuration ?? 0),
            'average_duration_formatted' => $this->formatDuration($avgDuration ?? 0),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Format duration in seconds to human readable format
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 60) {
            return round($seconds) . 's';
        } elseif ($seconds < 3600) {
            return round($seconds / 60) . 'm';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = round(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }
}