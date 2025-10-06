<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbooksAppConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create global QuickBooks app configuration table
        Schema::create('quickbooks_app_config', function (Blueprint $table) {
            $table->id();
            $table->string('environment')->comment('sandbox or production');
            $table->string('client_id')->comment('QuickBooks App Client ID');
            $table->text('client_secret')->comment('QuickBooks App Client Secret (encrypted)');
            $table->string('discovery_document_url')->comment('QuickBooks Discovery Document URL');
            $table->string('oauth_redirect_uri')->comment('OAuth Redirect URI');
            $table->boolean('is_active')->default(true);
            $table->json('scopes')->nullable()->comment('Required OAuth scopes');
            $table->text('webhook_verifier_token')->nullable()->comment('Webhook verification token');
            $table->timestamps();
            
            // Ensure only one config per environment
            $table->unique(['environment']);
        });

        // Remove client credentials from location settings and add connection status
        if (Schema::hasTable('quickbooks_location_settings')) {
            Schema::table('quickbooks_location_settings', function (Blueprint $table) {
                // Remove client credentials - these are now global
                if (Schema::hasColumn('quickbooks_location_settings', 'client_id')) {
                    $table->dropColumn('client_id');
                }
                if (Schema::hasColumn('quickbooks_location_settings', 'client_secret')) {
                    $table->dropColumn('client_secret');
                }
                
                // Add connection metadata
                $table->string('connection_status')->default('disconnected')->after('company_id')
                      ->comment('disconnected, connected, token_expired, error');
                $table->string('quickbooks_company_name')->nullable()->after('connection_status')
                      ->comment('Name of connected QuickBooks company');
                $table->string('quickbooks_country')->nullable()->after('quickbooks_company_name')
                      ->comment('Country of QuickBooks company');
                $table->timestamp('connected_at')->nullable()->after('quickbooks_country')
                      ->comment('When the connection was established');
                $table->timestamp('last_token_refresh_at')->nullable()->after('token_expires_at')
                      ->comment('Last time tokens were refreshed');
                
                // Add more detailed error tracking
                $table->integer('consecutive_failed_refreshes')->default(0)->after('failed_syncs_count');
                $table->json('connection_metadata')->nullable()->after('last_sync_error')
                      ->comment('Additional connection and company info from QuickBooks');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the app config table
        Schema::dropIfExists('quickbooks_app_config');

        // Restore client credentials to location settings
        if (Schema::hasTable('quickbooks_location_settings')) {
            Schema::table('quickbooks_location_settings', function (Blueprint $table) {
                // Add back client credentials
                $table->string('client_id')->nullable()->after('company_id');
                $table->string('client_secret')->nullable()->after('client_id');
                
                // Remove new columns
                $table->dropColumn([
                    'connection_status',
                    'quickbooks_company_name', 
                    'quickbooks_country',
                    'connected_at',
                    'last_token_refresh_at',
                    'consecutive_failed_refreshes',
                    'connection_metadata'
                ]);
            });
        }
    }
}