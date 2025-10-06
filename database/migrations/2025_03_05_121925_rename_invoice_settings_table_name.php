<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('invoice-settings', 'invoice_settings');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')
                ->on('invoice_settings')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')
                ->on('invoice_settings')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('invoice_settings', 'invoice-settings');
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
        });


        // Restore original foreign keys
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')
                ->on('invoice-settings')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreign('template_id')->references('id')
                ->on('invoice-settings')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
};
