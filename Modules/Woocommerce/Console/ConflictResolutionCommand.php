<?php

namespace Modules\Woocommerce\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Business;
use Modules\Woocommerce\Services\ConflictResolutionService;
use Modules\Woocommerce\Services\SyncMonitoringService;
use Modules\Woocommerce\Entities\WoocommerceSyncConflict;

class ConflictResolutionCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'woocommerce:resolve-conflicts 
                           {--business_id= : Specific business ID to process}
                           {--detect-only : Only detect conflicts, do not resolve}
                           {--auto-resolve : Automatically resolve resolvable conflicts}
                           {--max-conflicts=100 : Maximum number of conflicts to process}
                           {--types= : Comma-separated conflict types to process}
                           {--dry-run : Show what would be done without making changes}
                           {--monitor : Enable real-time monitoring integration}
                           {--alert-threshold=10 : Alert threshold for critical conflicts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and resolve WooCommerce synchronization conflicts with monitoring';

    protected ConflictResolutionService $conflictService;
    protected SyncMonitoringService $monitoringService;

    /**
     * Create a new command instance.
     *
     * @param ConflictResolutionService $conflictService
     * @param SyncMonitoringService $monitoringService
     */
    public function __construct(
        ConflictResolutionService $conflictService,
        SyncMonitoringService $monitoringService
    ) {
        parent::__construct();
        $this->conflictService = $conflictService;
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('WooCommerce Conflict Resolution Tool v2.0');
        $this->info('=============================================');

        $businessId = $this->option('business_id');
        $detectOnly = $this->option('detect-only');
        $autoResolve = $this->option('auto-resolve');
        $maxConflicts = (int) $this->option('max-conflicts');
        $types = $this->option('types') ? explode(',', $this->option('types')) : null;
        $dryRun = $this->option('dry-run');
        $enableMonitoring = $this->option('monitor');
        $alertThreshold = (int) $this->option('alert-threshold');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        if ($enableMonitoring) {
            $this->info('Real-time monitoring enabled');
        }

        try {
            if ($businessId) {
                $this->processBusinessConflicts($businessId, [
                    'detect_only' => $detectOnly,
                    'auto_resolve' => $autoResolve,
                    'max_conflicts' => $maxConflicts,
                    'types' => $types,
                    'dry_run' => $dryRun,
                    'enable_monitoring' => $enableMonitoring,
                    'alert_threshold' => $alertThreshold
                ]);
            } else {
                $this->processAllBusinesses([
                    'detect_only' => $detectOnly,
                    'auto_resolve' => $autoResolve,
                    'max_conflicts' => $maxConflicts,
                    'types' => $types,
                    'dry_run' => $dryRun,
                    'enable_monitoring' => $enableMonitoring,
                    'alert_threshold' => $alertThreshold
                ]);
            }

            // Generate monitoring report if enabled
            if ($enableMonitoring) {
                $this->generateMonitoringReport($businessId);
            }

        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            Log::error('Conflict resolution command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Process conflicts for all businesses
     */
    protected function processAllBusinesses(array $options): void
    {
        $businesses = Business::whereHas('modules', function($query) {
            $query->where('module_name', 'woocommerce')
                  ->where('is_enabled', true);
        })->get();

        if ($businesses->isEmpty()) {
            $this->info('No businesses with WooCommerce module enabled found.');
            return;
        }

        $this->info("Processing {$businesses->count()} businesses...");
        $totalConflicts = 0;
        $totalResolved = 0;

        foreach ($businesses as $business) {
            $this->line("\nProcessing Business: {$business->name} (ID: {$business->id})");
            $result = $this->processBusinessConflicts($business->id, $options);
            
            $totalConflicts += $result['total_conflicts'] ?? 0;
            $totalResolved += $result['resolved_conflicts'] ?? 0;

            // Check alert threshold
            if ($options['enable_monitoring'] && 
                ($result['critical_conflicts'] ?? 0) >= $options['alert_threshold']) {
                $this->sendCriticalAlert($business->id, $result['critical_conflicts']);
            }
        }

        $this->info("\n=== SUMMARY ===");
        $this->info("Total conflicts found: {$totalConflicts}");
        $this->info("Total conflicts resolved: {$totalResolved}");
        
        if ($totalConflicts > 0) {
            $successRate = round(($totalResolved / $totalConflicts) * 100, 1);
            $this->info("Resolution success rate: {$successRate}%");
        }
    }

    /**
     * Process conflicts for a specific business
     */
    protected function processBusinessConflicts(int $businessId, array $options): array
    {
        $result = [
            'total_conflicts' => 0,
            'resolved_conflicts' => 0,
            'critical_conflicts' => 0,
            'errors' => []
        ];

        try {
            // Step 1: Detect conflicts
            $this->line('Detecting conflicts...');
            
            $detectionOptions = [];
            if ($options['max_conflicts']) {
                $detectionOptions['limit'] = $options['max_conflicts'];
            }

            $conflicts = $this->conflictService->detectConflicts($businessId, $detectionOptions);
            
            $this->displayConflictSummary($conflicts);
            $result['total_conflicts'] = $conflicts['summary']['total_conflicts'];

            // Count critical conflicts
            if (isset($conflicts['summary']['by_severity']['critical'])) {
                $result['critical_conflicts'] = $conflicts['summary']['by_severity']['critical'];
            }

            // Step 2: Save detected conflicts to database
            if (!$options['dry_run'] && $conflicts['summary']['total_conflicts'] > 0) {
                $saved = $this->saveConflictsToDatabase($businessId, $conflicts);
                $this->info("✓ Saved {$saved} new conflicts to database");
            }

            // Step 3: Monitor conflicts if enabled
            if ($options['enable_monitoring']) {
                $this->monitoringService->recordConflictDetectionResults(
                    $businessId,
                    $conflicts['summary']
                );
            }

            // Step 4: Auto-resolve if requested
            if ($options['auto_resolve'] && !$options['detect_only']) {
                $this->line("\nAuto-resolving conflicts...");
                
                $resolutionOptions = [
                    'max_conflicts' => $options['max_conflicts']
                ];
                
                if ($options['types']) {
                    $resolutionOptions['conflict_types'] = $options['types'];
                }

                if (!$options['dry_run']) {
                    $resolutionResults = $this->conflictService->autoResolveConflicts($businessId, $resolutionOptions);
                    $this->displayResolutionResults($resolutionResults);
                    $result['resolved_conflicts'] = $resolutionResults['resolved'];

                    // Log resolution results to monitoring
                    if ($options['enable_monitoring']) {
                        $this->monitoringService->recordConflictResolutionResults(
                            $businessId,
                            $resolutionResults
                        );
                    }
                } else {
                    $this->line('DRY RUN: Would attempt auto-resolution');
                }
            }

            // Step 5: Display remaining conflicts
            $this->displayRemainingConflicts($businessId);

            // Step 6: Health check and recommendations
            if ($options['enable_monitoring']) {
                $this->performHealthCheck($businessId);
            }

        } catch (\Exception $e) {
            $this->error("Failed to process business {$businessId}: " . $e->getMessage());
            Log::error('Business conflict processing failed', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            
            $result['errors'][] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Display conflict summary with enhanced formatting
     */
    protected function displayConflictSummary(array $conflicts): void
    {
        $summary = $conflicts['summary'];

        if ($summary['total_conflicts'] === 0) {
            $this->info('✓ No conflicts detected');
            return;
        }

        $this->warn("⚠ Found {$summary['total_conflicts']} conflicts:");

        // By entity type
        $this->line("\nBy Entity Type:");
        foreach ($summary['by_entity'] as $type => $count) {
            if ($count > 0) {
                $this->line("  - {$type}: {$count}");
            }
        }

        // By severity
        if (!empty($summary['by_severity'])) {
            $this->line("\nBy Severity:");
            foreach ($summary['by_severity'] as $severity => $count) {
                $color = $this->getSeverityColor($severity);
                $icon = $this->getSeverityIcon($severity);
                $this->line("  {$icon} <{$color}>{$severity}: {$count}</{$color}>");
            }
        }

        // By type
        if (!empty($summary['by_type'])) {
            $this->line("\nBy Type:");
            foreach ($summary['by_type'] as $type => $count) {
                $this->line("  - {$type}: {$count}");
            }
        }

        // Auto-resolvable count
        if (isset($summary['auto_resolvable'])) {
            $this->line("\n<info>Auto-resolvable: {$summary['auto_resolvable']}</info>");
        }
    }

    /**
     * Save detected conflicts to database with monitoring integration
     */
    protected function saveConflictsToDatabase(int $businessId, array $conflicts): int
    {
        $this->line('Saving conflicts to database...');
        $saved = 0;

        foreach (['products', 'categories', 'orders'] as $entityType) {
            if (!isset($conflicts[$entityType])) continue;

            foreach ($conflicts[$entityType] as $entityConflicts) {
                foreach ($entityConflicts['conflicts'] as $conflict) {
                    try {
                        // Check if conflict already exists
                        $existing = WoocommerceSyncConflict::where('business_id', $businessId)
                            ->where('entity_type', $conflict['entity_type'] ?? $entityType)
                            ->where('entity_id', $entityConflicts['entity_id'])
                            ->where('field_name', $conflict['field'] ?? null)
                            ->where('status', WoocommerceSyncConflict::STATUS_OPEN)
                            ->first();

                        if (!$existing) {
                            $conflictRecord = WoocommerceSyncConflict::createConflict(
                                $businessId,
                                $conflict['entity_type'] ?? $entityType,
                                $entityConflicts['entity_id'],
                                $entityConflicts['woocommerce_id'],
                                $conflict['type'],
                                ['value' => $conflict['pos_value'], 'updated_at' => $conflict['pos_updated']],
                                ['value' => $conflict['wc_value'], 'date_modified' => $conflict['wc_updated']],
                                [
                                    'field_name' => $conflict['field'],
                                    'severity' => $conflict['severity'],
                                    'auto_resolvable' => $conflict['auto_resolvable'] ?? false,
                                    'resolution_strategy' => $conflict['recommended_resolution'] ?? null
                                ]
                            );

                            // Log to monitoring service
                            $this->monitoringService->logConflictCreated(
                                $businessId,
                                $conflictRecord->toArray()
                            );

                            $saved++;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to save conflict to database', [
                            'business_id' => $businessId,
                            'entity_type' => $entityType,
                            'entity_id' => $entityConflicts['entity_id'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        return $saved;
    }

    /**
     * Display resolution results with enhanced metrics
     */
    protected function displayResolutionResults(array $results): void
    {
        $this->line("\n=== RESOLUTION RESULTS ===");
        $this->info("✓ Processed: {$results['processed']}");
        $this->info("✓ Resolved: {$results['resolved']}");
        
        if ($results['failed'] > 0) {
            $this->error("✗ Failed: {$results['failed']}");
            
            if (!empty($results['errors'])) {
                $this->line("\nErrors:");
                foreach ($results['errors'] as $error) {
                    $this->error("  - Conflict {$error['conflict_id']}: {$error['error']}");
                }
            }
        }

        // Success rate
        if ($results['processed'] > 0) {
            $successRate = round(($results['resolved'] / $results['processed']) * 100, 1);
            $this->line("\nSuccess Rate: <info>{$successRate}%</info>");
        }

        // Resolution strategies used
        if (!empty($results['strategies_used'])) {
            $this->line("\nStrategies Used:");
            foreach ($results['strategies_used'] as $strategy => $count) {
                $this->line("  - {$strategy}: {$count}");
            }
        }
    }

    /**
     * Display remaining unresolved conflicts
     */
    protected function displayRemainingConflicts(int $businessId): void
    {
        $remaining = WoocommerceSyncConflict::forBusiness($businessId)
            ->open()
            ->count();

        $critical = WoocommerceSyncConflict::forBusiness($businessId)
            ->open()
            ->where('severity', WoocommerceSyncConflict::SEVERITY_CRITICAL)
            ->count();

        if ($remaining > 0) {
            $this->line("\n{$remaining} conflicts remain unresolved.");
            
            if ($critical > 0) {
                $this->error("⚠ {$critical} critical conflicts require immediate attention!");
            }
            
            $this->line('Use the WooCommerce dashboard to review and resolve them.');
        } else {
            $this->info("\n✓ All conflicts have been resolved!");
        }
    }

    /**
     * Perform health check with recommendations
     */
    protected function performHealthCheck(int $businessId): void
    {
        $this->line("\n=== HEALTH CHECK ===");
        
        try {
            $healthStatus = $this->monitoringService->getSystemHealth($businessId);
            
            $this->displayHealthStatus($healthStatus);
            $this->displayRecommendations($healthStatus);
            
        } catch (\Exception $e) {
            $this->error("Health check failed: " . $e->getMessage());
        }
    }

    /**
     * Display health status
     */
    protected function displayHealthStatus(array $healthStatus): void
    {
        $overallStatus = $healthStatus['overall_status'];
        $color = match($overallStatus) {
            'healthy' => 'info',
            'warning' => 'comment',
            'critical' => 'error',
            default => 'question'
        };

        $this->line("Overall Health: <{$color}>" . strtoupper($overallStatus) . "</{$color}>");

        foreach ($healthStatus['checks'] as $check => $result) {
            $status = $result['status'];
            $icon = $status === 'passed' ? '✓' : '✗';
            $checkColor = $status === 'passed' ? 'info' : 'error';
            
            $this->line("  {$icon} <{$checkColor}>{$check}: {$result['message']}</{$checkColor}>");
        }
    }

    /**
     * Display recommendations
     */
    protected function displayRecommendations(array $healthStatus): void
    {
        if (empty($healthStatus['recommendations'])) {
            return;
        }

        $this->line("\n=== RECOMMENDATIONS ===");
        foreach ($healthStatus['recommendations'] as $recommendation) {
            $priority = $recommendation['priority'];
            $color = match($priority) {
                'high' => 'error',
                'medium' => 'comment',
                'low' => 'info',
                default => 'question'
            };
            
            $this->line("<{$color}>[{$priority}] {$recommendation['message']}</{$color}>");
            
            if (!empty($recommendation['action'])) {
                $this->line("  Action: {$recommendation['action']}");
            }
        }
    }

    /**
     * Generate monitoring report
     */
    protected function generateMonitoringReport(?int $businessId): void
    {
        $this->line("\n=== MONITORING REPORT ===");
        
        try {
            $report = $this->monitoringService->generateConflictReport($businessId);
            
            $this->line("Report generated: {$report['timestamp']}");
            $this->line("Businesses analyzed: {$report['businesses_count']}");
            $this->line("Total conflicts: {$report['total_conflicts']}");
            $this->line("Resolution rate: {$report['resolution_rate']}%");
            
            if (!empty($report['trends'])) {
                $this->line("\nTrends:");
                foreach ($report['trends'] as $trend => $data) {
                    $this->line("  - {$trend}: {$data}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to generate monitoring report: " . $e->getMessage());
        }
    }

    /**
     * Send critical alert
     */
    protected function sendCriticalAlert(int $businessId, int $criticalCount): void
    {
        $this->error("🚨 CRITICAL ALERT: Business {$businessId} has {$criticalCount} critical conflicts!");
        
        try {
            $this->monitoringService->sendCriticalAlert($businessId, [
                'critical_conflicts' => $criticalCount,
                'timestamp' => now(),
                'source' => 'conflict_resolution_command'
            ]);
            
            $this->line("Alert notification sent.");
        } catch (\Exception $e) {
            $this->error("Failed to send alert: " . $e->getMessage());
        }
    }

    /**
     * Get color for severity display
     */
    protected function getSeverityColor(string $severity): string
    {
        return match($severity) {
            WoocommerceSyncConflict::SEVERITY_CRITICAL => 'error',
            WoocommerceSyncConflict::SEVERITY_HIGH => 'comment',
            WoocommerceSyncConflict::SEVERITY_MEDIUM => 'info',
            WoocommerceSyncConflict::SEVERITY_LOW => 'question',
            default => 'info'
        };
    }

    /**
     * Get icon for severity display
     */
    protected function getSeverityIcon(string $severity): string
    {
        return match($severity) {
            WoocommerceSyncConflict::SEVERITY_CRITICAL => '🚨',
            WoocommerceSyncConflict::SEVERITY_HIGH => '⚠️',
            WoocommerceSyncConflict::SEVERITY_MEDIUM => '⚪',
            WoocommerceSyncConflict::SEVERITY_LOW => '🔵',
            default => '⚪'
        };
    }
}