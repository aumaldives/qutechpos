<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSchedulingPreferencesToWoocommerceLocationSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('woocommerce_location_settings', function (Blueprint $table) {
            // Scheduling preferences
            $table->boolean('enable_scheduling')->default(false);
            $table->string('default_timezone', 50)->nullable();
            $table->integer('max_concurrent_syncs')->default(2);
            $table->json('scheduling_preferences')->nullable(); // Store location-specific preferences
            
            // Queue configuration
            $table->string('preferred_queue', 50)->default('woocommerce-normal');
            $table->integer('default_priority')->default(5);
            
            // Business hours for scheduling conditions
            $table->json('business_hours')->nullable(); // Store business hours configuration
            
            // Auto-scheduling templates
            $table->boolean('auto_create_schedules')->default(false);
            $table->json('auto_schedule_templates')->nullable(); // Templates to auto-create
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('woocommerce_location_settings', function (Blueprint $table) {
            $table->dropColumn([
                'enable_scheduling',
                'default_timezone',
                'max_concurrent_syncs',
                'scheduling_preferences',
                'preferred_queue',
                'default_priority',
                'business_hours',
                'auto_create_schedules',
                'auto_schedule_templates'
            ]);
        });
    }
}