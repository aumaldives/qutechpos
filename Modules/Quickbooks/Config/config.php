<?php

return [
    'name' => 'Quickbooks',
    
    /**
     * QuickBooks API Configuration
     */
    'api' => [
        // API Version
        'version' => 'v3',
        
        // Base URLs for different environments
        'base_urls' => [
            'sandbox' => 'https://sandbox-quickbooks.api.intuit.com',
            'production' => 'https://quickbooks.api.intuit.com'
        ],
        
        // OAuth endpoints
        'oauth' => [
            'authorize_url' => 'https://appcenter.intuit.com/connect/oauth2',
            'token_endpoints' => [
                'sandbox' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer',
                'production' => 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer'
            ],
            'discovery_urls' => [
                'sandbox' => 'https://sandbox.developer.intuit.com/v2/catalog',
                'production' => 'https://developer.intuit.com/v2/catalog'
            ]
        ],
        
        // Default OAuth scopes
        'default_scopes' => [
            'com.intuit.quickbooks.accounting'
        ],
        
        // API request timeouts
        'timeout' => 30,
        'connect_timeout' => 10,
        
        // Rate limiting
        'rate_limit' => [
            'requests_per_minute' => 500,
            'burst_limit' => 100
        ]
    ],
    
    /**
     * Synchronization Settings
     */
    'sync' => [
        // Default sync intervals (in minutes)
        'default_intervals' => [
            'auto_sync' => 60,
            'token_refresh' => 30
        ],
        
        // Batch processing limits
        'batch_sizes' => [
            'customers' => 100,
            'products' => 50,
            'invoices' => 25,
            'payments' => 100
        ],
        
        // Retry configuration
        'retry' => [
            'max_attempts' => 3,
            'delay_seconds' => 5,
            'backoff_multiplier' => 2
        ],
        
        // Auto-disable after consecutive failures
        'failure_threshold' => 5,
        
        // Sync queue configuration
        'queue' => [
            'connection' => 'database',
            'queue_name' => 'quickbooks_sync',
            'retry_after' => 300
        ]
    ],
    
    /**
     * Security Settings
     */
    'security' => [
        // OAuth state parameter expiry (seconds)
        'state_expiry' => 3600,
        
        // Session security
        'session_lifetime' => 7200,
        
        // Token refresh security
        'refresh_margin_minutes' => 5,
        
        // Webhook validation
        'webhook_validation' => true,
        'webhook_timeout' => 30
    ],
    
    /**
     * Feature Flags
     */
    'features' => [
        'real_time_sync' => true,
        'webhook_support' => true,
        'bi_directional_sync' => false,
        'advanced_mapping' => false,
        'audit_logging' => true,
        'performance_monitoring' => true
    ],
    
    /**
     * Logging Configuration
     */
    'logging' => [
        'channel' => 'daily',
        'level' => 'info',
        'max_files' => 30,
        'context_data' => [
            'include_request_id' => true,
            'include_user_id' => true,
            'include_business_id' => true
        ]
    ],
    
    /**
     * Error Handling
     */
    'error_handling' => [
        'log_all_errors' => true,
        'notify_on_critical' => true,
        'auto_retry_transient' => true,
        'max_error_log_size' => '10MB'
    ]
];
