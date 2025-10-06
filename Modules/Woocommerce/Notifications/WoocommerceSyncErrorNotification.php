<?php

namespace Modules\Woocommerce\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Woocommerce\Entities\WoocommerceSyncError;
use Modules\Woocommerce\Entities\WoocommerceLocationSetting;

class WoocommerceSyncErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $syncError;
    protected $errorStats;
    protected $notificationType;

    // Notification types
    const TYPE_CRITICAL_ERROR = 'critical_error';
    const TYPE_ERROR_THRESHOLD = 'error_threshold';
    const TYPE_RECOVERY_FAILED = 'recovery_failed';
    const TYPE_DAILY_SUMMARY = 'daily_summary';

    /**
     * Create a new notification instance.
     */
    public function __construct(WoocommerceSyncError $syncError = null, $errorStats = null, $notificationType = self::TYPE_CRITICAL_ERROR)
    {
        $this->syncError = $syncError;
        $this->errorStats = $errorStats;
        $this->notificationType = $notificationType;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['mail'];

        // Add database notification for in-app alerts
        if (in_array($this->notificationType, [self::TYPE_CRITICAL_ERROR, self::TYPE_ERROR_THRESHOLD])) {
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        switch ($this->notificationType) {
            case self::TYPE_CRITICAL_ERROR:
                return $this->criticalErrorMail($notifiable);
            case self::TYPE_ERROR_THRESHOLD:
                return $this->errorThresholdMail($notifiable);
            case self::TYPE_RECOVERY_FAILED:
                return $this->recoveryFailedMail($notifiable);
            case self::TYPE_DAILY_SUMMARY:
                return $this->dailySummaryMail($notifiable);
            default:
                return $this->criticalErrorMail($notifiable);
        }
    }

    /**
     * Critical error email notification
     */
    private function criticalErrorMail($notifiable)
    {
        $error = $this->syncError;
        $businessName = $error->business->name ?? 'Unknown Business';
        $locationName = $error->location->name ?? 'Unknown Location';

        return (new MailMessage)
            ->error()
            ->subject("🚨 Critical WooCommerce Sync Error - {$businessName}")
            ->greeting("Critical Sync Error Alert")
            ->line("A critical error has occurred during WooCommerce synchronization.")
            ->line("**Business:** {$businessName}")
            ->line("**Location:** {$locationName}")
            ->line("**Sync Type:** " . ucfirst($error->sync_type))
            ->line("**Error Category:** " . str_replace('_', ' ', ucwords($error->error_category, '_')))
            ->line("**Severity:** " . ucfirst($error->severity_level))
            ->line("**Error Message:** {$error->error_message}")
            ->when($error->affected_entity_type, function($mail) use ($error) {
                $mail->line("**Affected Entity:** " . ucfirst($error->affected_entity_type) . " (ID: {$error->affected_entity_id})");
            })
            ->line("**Occurred At:** " . $error->created_at->format('Y-m-d H:i:s T'))
            ->action('View Error Details', url('/woocommerce/errors/' . $error->id))
            ->line('This error requires immediate attention as it may impact your WooCommerce integration.')
            ->salutation('IsleBooks POS System');
    }

    /**
     * Error threshold reached email notification  
     */
    private function errorThresholdMail($notifiable)
    {
        $stats = $this->errorStats;
        $businessName = $stats['business_name'] ?? 'Unknown Business';
        $locationName = $stats['location_name'] ?? 'All Locations';

        return (new MailMessage)
            ->warning()
            ->subject("⚠️ WooCommerce Error Threshold Exceeded - {$businessName}")
            ->greeting("Error Threshold Alert")
            ->line("The number of WooCommerce sync errors has exceeded the acceptable threshold.")
            ->line("**Business:** {$businessName}")
            ->line("**Location:** {$locationName}")
            ->line("**Time Period:** Last 24 hours")
            ->line("**Total Errors:** {$stats['total_errors']}")
            ->line("**Critical Errors:** {$stats['critical_errors']}")
            ->line("**Unresolved Errors:** {$stats['unresolved_errors']}")
            ->line("**Error Categories:**")
            ->when(isset($stats['by_category']), function($mail) use ($stats) {
                foreach ($stats['by_category'] as $category => $count) {
                    $mail->line("  • " . str_replace('_', ' ', ucwords($category, '_')) . ": {$count}");
                }
            })
            ->action('View Error Dashboard', url('/woocommerce/errors'))
            ->line('Please review these errors to maintain optimal sync performance.')
            ->salutation('IsleBooks POS System');
    }

    /**
     * Recovery failed email notification
     */
    private function recoveryFailedMail($notifiable)
    {
        $error = $this->syncError;
        $businessName = $error->business->name ?? 'Unknown Business';
        $locationName = $error->location->name ?? 'Unknown Location';

        return (new MailMessage)
            ->warning()
            ->subject("🔄 WooCommerce Error Recovery Failed - {$businessName}")
            ->greeting("Recovery Failure Alert")
            ->line("Automatic recovery failed for a WooCommerce sync error after multiple attempts.")
            ->line("**Business:** {$businessName}")
            ->line("**Location:** {$locationName}")
            ->line("**Error ID:** {$error->id}")
            ->line("**Error Category:** " . str_replace('_', ' ', ucwords($error->error_category, '_')))
            ->line("**Recovery Attempts:** {$error->recovery_attempts}")
            ->line("**Original Error:** {$error->error_message}")
            ->line("**First Occurred:** " . $error->created_at->format('Y-m-d H:i:s T'))
            ->action('Manual Intervention Required', url('/woocommerce/errors/' . $error->id))
            ->line('This error requires manual review and resolution.')
            ->salutation('IsleBooks POS System');
    }

    /**
     * Daily summary email notification
     */
    private function dailySummaryMail($notifiable)
    {
        $stats = $this->errorStats;
        $businessName = $stats['business_name'] ?? 'Multiple Businesses';

        $mailMessage = (new MailMessage)
            ->subject("📊 Daily WooCommerce Sync Report - {$businessName}")
            ->greeting("Daily Sync Summary")
            ->line("Here's your daily WooCommerce synchronization report.");

        // Overall statistics
        if (isset($stats['overall'])) {
            $overall = $stats['overall'];
            $mailMessage
                ->line("**Overall Statistics (Last 24 Hours):**")
                ->line("• Total Sync Operations: {$overall['total_syncs']}")
                ->line("• Successful Syncs: {$overall['successful_syncs']}")
                ->line("• Failed Syncs: {$overall['failed_syncs']}")
                ->line("• Success Rate: " . number_format($overall['success_rate'], 1) . "%")
                ->line("")
                ->line("**Error Summary:**")
                ->line("• New Errors: {$overall['new_errors']}")
                ->line("• Resolved Errors: {$overall['resolved_errors']}")
                ->line("• Auto-Recovered: {$overall['auto_recovered']}")
                ->line("• Pending Resolution: {$overall['pending_errors']}");
        }

        // Per-location breakdown
        if (isset($stats['locations']) && is_array($stats['locations'])) {
            $mailMessage->line("")->line("**Location Breakdown:**");
            
            foreach ($stats['locations'] as $location) {
                $mailMessage->line("📍 **{$location['name']}:**")
                           ->line("  • Syncs: {$location['sync_count']}")
                           ->line("  • Errors: {$location['error_count']}")
                           ->line("  • Status: " . ($location['error_count'] > 0 ? '⚠️ Issues' : '✅ Healthy'));
            }
        }

        $mailMessage
            ->action('View Detailed Report', url('/woocommerce/reports'))
            ->salutation('IsleBooks POS System');

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        $data = [
            'type' => $this->notificationType,
            'created_at' => now(),
        ];

        if ($this->syncError) {
            $data['error'] = [
                'id' => $this->syncError->id,
                'business_id' => $this->syncError->business_id,
                'location_id' => $this->syncError->location_id,
                'sync_type' => $this->syncError->sync_type,
                'error_category' => $this->syncError->error_category,
                'severity_level' => $this->syncError->severity_level,
                'error_message' => $this->syncError->error_message,
                'business_name' => $this->syncError->business->name ?? 'Unknown',
                'location_name' => $this->syncError->location->name ?? 'Unknown',
            ];
        }

        if ($this->errorStats) {
            $data['stats'] = $this->errorStats;
        }

        return $data;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}