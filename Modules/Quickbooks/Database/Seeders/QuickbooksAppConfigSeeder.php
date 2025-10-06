<?php

namespace Modules\Quickbooks\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Quickbooks\Entities\QuickbooksAppConfig;

class QuickbooksAppConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Create sandbox configuration
        QuickbooksAppConfig::updateOrCreate(
            ['environment' => 'sandbox'],
            [
                'client_id' => env('QUICKBOOKS_SANDBOX_CLIENT_ID', 'YOUR_SANDBOX_CLIENT_ID'),
                'client_secret' => env('QUICKBOOKS_SANDBOX_CLIENT_SECRET', 'YOUR_SANDBOX_CLIENT_SECRET'),
                'discovery_document_url' => 'https://sandbox.developer.intuit.com/.well-known/connect/discovery',
                'oauth_redirect_uri' => route('quickbooks.oauth.callback'),
                'scopes' => ['com.intuit.quickbooks.accounting'],
                'is_active' => true,
                'webhook_verifier_token' => env('QUICKBOOKS_WEBHOOK_VERIFIER_TOKEN', null)
            ]
        );

        // Create production configuration (initially disabled)
        QuickbooksAppConfig::updateOrCreate(
            ['environment' => 'production'],
            [
                'client_id' => env('QUICKBOOKS_PROD_CLIENT_ID', 'YOUR_PRODUCTION_CLIENT_ID'),
                'client_secret' => env('QUICKBOOKS_PROD_CLIENT_SECRET', 'YOUR_PRODUCTION_CLIENT_SECRET'),
                'discovery_document_url' => 'https://developer.intuit.com/.well-known/connect/discovery',
                'oauth_redirect_uri' => route('quickbooks.oauth.callback'),
                'scopes' => ['com.intuit.quickbooks.accounting'],
                'is_active' => false, // Start disabled for safety
                'webhook_verifier_token' => env('QUICKBOOKS_WEBHOOK_VERIFIER_TOKEN', null)
            ]
        );

        $this->command->info('QuickBooks app configurations created/updated successfully!');
        $this->command->warn('Please update your .env file with the correct QuickBooks app credentials:');
        $this->command->line('QUICKBOOKS_SANDBOX_CLIENT_ID=your_sandbox_client_id');
        $this->command->line('QUICKBOOKS_SANDBOX_CLIENT_SECRET=your_sandbox_client_secret');
        $this->command->line('QUICKBOOKS_PROD_CLIENT_ID=your_production_client_id');
        $this->command->line('QUICKBOOKS_PROD_CLIENT_SECRET=your_production_client_secret');
        $this->command->line('QUICKBOOKS_WEBHOOK_VERIFIER_TOKEN=your_webhook_token (optional)');

        Model::reguard();
    }
}