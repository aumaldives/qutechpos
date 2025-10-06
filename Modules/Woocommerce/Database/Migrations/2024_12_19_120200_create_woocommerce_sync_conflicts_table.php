<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWoocommerceSyncConflictsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('woocommerce_sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('entity_type', 50)
                  ->comment('Type of entity with conflict (product, category, order, etc.)');
            $table->unsignedInteger('entity_id')
                  ->comment('POS entity ID');
            $table->unsignedInteger('woocommerce_id')
                  ->comment('WooCommerce entity ID');
            $table->string('conflict_type', 50)
                  ->comment('Type of conflict (data_mismatch, duplicate, validation_error, etc.)');
            $table->string('field_name', 100)->nullable()
                  ->comment('Specific field with conflict');
            $table->json('pos_data')
                  ->comment('Current POS data');
            $table->json('woocommerce_data')
                  ->comment('Current WooCommerce data');
            $table->json('metadata')->nullable()
                  ->comment('Additional metadata about the conflict');
            $table->enum('resolution_strategy', [
                'pos_wins', 'wc_wins', 'newest_wins', 'manual', 'merge', 'skip'
            ])->nullable()
                  ->comment('How the conflict should be resolved');
            $table->enum('status', ['open', 'resolved', 'ignored', 'escalated'])
                  ->default('open')
                  ->comment('Current status of the conflict');
            $table->text('resolution_notes')->nullable()
                  ->comment('Notes about how the conflict was resolved');
            $table->unsignedInteger('resolved_by')->nullable()
                  ->comment('User ID who resolved the conflict');
            $table->timestamp('resolved_at')->nullable()
                  ->comment('When the conflict was resolved');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])
                  ->default('medium')
                  ->comment('Severity level of the conflict');
            $table->boolean('auto_resolvable')->default(false)
                  ->comment('Whether this conflict type can be auto-resolved');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            
            // Performance indexes
            $table->index(['business_id', 'status'], 'idx_business_status');
            $table->index(['business_id', 'resolved_at'], 'idx_business_resolved');
            $table->index(['entity_type', 'entity_id'], 'idx_entity');
            $table->index(['conflict_type', 'severity'], 'idx_type_severity');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['auto_resolvable', 'status'], 'idx_auto_resolvable');
            
            // Unique constraint to prevent duplicate conflicts
            $table->unique(['business_id', 'entity_type', 'entity_id', 'field_name'], 'unique_conflict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('woocommerce_sync_conflicts');
    }
}