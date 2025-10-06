<?php

namespace Modules\Quickbooks\Console\Commands;

use Illuminate\Console\Command;
use Modules\Quickbooks\Entities\QuickbooksAppConfig;

class ValidateQuickBooksConfig extends Command
{
    protected $signature = 'quickbooks:validate-config';
    protected $description = 'Validate QuickBooks app configuration';

    public function handle()
    {
        $this->info('Validating QuickBooks Configuration...');
        
        $hasErrors = false;
        
        // Check environment variables
        $envVars = [
            'QUICKBOOKS_SANDBOX_CLIENT_ID' => env('QUICKBOOKS_SANDBOX_CLIENT_ID'),
            'QUICKBOOKS_SANDBOX_CLIENT_SECRET' => env('QUICKBOOKS_SANDBOX_CLIENT_SECRET'),
            'QUICKBOOKS_PROD_CLIENT_ID' => env('QUICKBOOKS_PROD_CLIENT_ID'),
            'QUICKBOOKS_PROD_CLIENT_SECRET' => env('QUICKBOOKS_PROD_CLIENT_SECRET'),
        ];

        foreach ($envVars as $name => $value) {
            if (empty($value) || $value === 'YOUR_SANDBOX_CLIENT_ID' || $value === 'YOUR_PRODUCTION_CLIENT_ID') {
                $this->error("❌ {$name} is not properly configured");
                $hasErrors = true;
            } else {
                $this->info("✅ {$name} is configured");
            }
        }

        // Check database configuration
        try {
            $sandboxConfig = QuickbooksAppConfig::getActiveConfig('sandbox');
            $prodConfig = QuickbooksAppConfig::getActiveConfig('production');

            if (!$sandboxConfig || !$sandboxConfig->isValid()) {
                $this->error('❌ Sandbox configuration is invalid or missing');
                $hasErrors = true;
            } else {
                $this->info('✅ Sandbox configuration is valid');
            }

            if (!$prodConfig || !$prodConfig->isValid()) {
                $this->warn('⚠️  Production configuration is invalid or missing (this is normal during development)');
            } else {
                $this->info('✅ Production configuration is valid');
            }

            // Validate redirect URIs
            $redirectUri = route('quickbooks.oauth.callback');
            $this->info("OAuth Redirect URI: {$redirectUri}");
            
            if ($sandboxConfig && $sandboxConfig->oauth_redirect_uri !== $redirectUri) {
                $this->warn("⚠️  Sandbox redirect URI in database doesn't match current route");
                $this->line("  Database: {$sandboxConfig->oauth_redirect_uri}");
                $this->line("  Expected: {$redirectUri}");
            }

        } catch (\Exception $e) {
            $this->error('❌ Database configuration check failed: ' . $e->getMessage());
            $hasErrors = true;
        }

        // Final verdict
        if ($hasErrors) {
            $this->error('❌ QuickBooks configuration has errors that need to be fixed');
            $this->line('');
            $this->line('To fix:');
            $this->line('1. Update your .env file with correct QuickBooks app credentials');
            $this->line('2. Run: php artisan db:seed --class="Modules\\Quickbooks\\Database\\Seeders\\QuickbooksAppConfigSeeder"');
            return 1;
        } else {
            $this->info('✅ QuickBooks configuration is ready!');
            $this->line('');
            $this->line('Next steps:');
            $this->line('1. Set your redirect URI in QuickBooks app settings to: ' . $redirectUri);
            $this->line('2. Test the connection from /quickbooks');
            return 0;
        }
    }
}