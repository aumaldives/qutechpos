<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Create table for monthly SMS schedule configuration
        Schema::create('monthly_sms_schedules', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->unsigned();
            $table->tinyInteger('send_day')->default(1); // Day of month (1-31)
            $table->time('send_time')->default('09:00:00'); // Time to send
            $table->boolean('is_enabled')->default(true);
            $table->string('timezone', 50)->default('UTC');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
            
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->unique('business_id');
        });

        // Add new notification templates to existing businesses
        $businesses = DB::table('business')->pluck('id');
        
        foreach ($businesses as $business_id) {
            // Check if templates already exist to avoid duplicates
            $existing_monthly = DB::table('notification_templates')
                ->where('business_id', $business_id)
                ->where('template_for', 'monthly_payment_link')
                ->exists();
                
            $existing_approved = DB::table('notification_templates')
                ->where('business_id', $business_id)
                ->where('template_for', 'payment_approved')
                ->exists();
            
            if (!$existing_monthly) {
                DB::table('notification_templates')->insert([
                    'business_id' => $business_id,
                    'template_for' => 'monthly_payment_link',
                    'email_body' => '<p>Dear {contact_name},</p>

                        <p>This is a monthly reminder that you have outstanding invoices with a total due amount of <strong>{total_due_amount}</strong>.</p>

                        <p>You can make payments for all your outstanding invoices at once using our convenient payment portal:</p>

                        <p><a href="{payment_link}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Make Payment Now</a></p>

                        <p>Number of outstanding invoices: {invoice_count}</p>

                        <p>Thank you for your business.</p>

                        <p>{business_logo}</p>',
                    'sms_body' => 'Dear {contact_name}, You have {invoice_count} outstanding invoices totaling {total_due_amount}. Pay conveniently here: {payment_link} - {business_name}',
                    'subject' => 'Monthly Payment Reminder - {business_name}',
                    'auto_send' => 0,
                    'auto_send_sms' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            if (!$existing_approved) {
                DB::table('notification_templates')->insert([
                    'business_id' => $business_id,
                    'template_for' => 'payment_approved',
                    'email_body' => '<p>Dear {contact_name},</p>

                        <p>Good news! Your payment of <strong>{payment_amount}</strong> has been approved and processed.</p>

                        <p>Payment Details:</p>
                        <ul>
                            <li>Amount: {payment_amount}</li>
                            <li>Payment Method: {payment_method}</li>
                            <li>Reference: {payment_ref_number}</li>
                            <li>Approved Date: {approval_date}</li>
                        </ul>

                        <p>Thank you for your payment.</p>

                        <p>{business_logo}</p>',
                    'sms_body' => 'Dear {contact_name}, Your payment of {payment_amount} has been approved and processed. Ref: {payment_ref_number}. Thank you! - {business_name}',
                    'subject' => 'Payment Approved - {business_name}',
                    'auto_send' => 0,
                    'auto_send_sms' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Create default monthly SMS schedule for each business
            $existing_schedule = DB::table('monthly_sms_schedules')
                ->where('business_id', $business_id)
                ->exists();
                
            if (!$existing_schedule) {
                DB::table('monthly_sms_schedules')->insert([
                    'business_id' => $business_id,
                    'send_day' => 1, // 1st of each month
                    'send_time' => '09:00:00', // 9 AM
                    'is_enabled' => false, // Disabled by default
                    'timezone' => 'UTC',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monthly_sms_schedules');
    }
};
