<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('stripe_subscription_id')->nullable()->after('payment_transaction_id');
            $table->string('stripe_customer_id')->nullable()->after('stripe_subscription_id');
            $table->boolean('is_recurring')->default(false)->after('stripe_customer_id');
            $table->boolean('auto_renewal')->default(false)->after('is_recurring');
            
            // Add index for faster queries
            $table->index('stripe_subscription_id');
            $table->index('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['stripe_subscription_id']);
            $table->dropIndex(['stripe_customer_id']);
            $table->dropColumn([
                'stripe_subscription_id',
                'stripe_customer_id',
                'is_recurring',
                'auto_renewal'
            ]);
        });
    }
};
