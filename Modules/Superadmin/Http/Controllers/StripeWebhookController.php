<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Superadmin\Entities\Package;
use Modules\Superadmin\Entities\Subscription;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Stripe\StripeClient;

class StripeWebhookController extends BaseController
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(env('STRIPE_SECRET_KEY'));
    }

    /**
     * Handle Stripe webhook events
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe webhook invalid payload: ' . $e->getMessage());
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe webhook invalid signature: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;
            
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;
            
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;
            
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;
            
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;
            
            default:
                Log::info('Unhandled Stripe webhook event: ' . $event->type);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle subscription created event
     */
    protected function handleSubscriptionCreated($stripeSubscription)
    {
        Log::info('Stripe subscription created', ['subscription_id' => $stripeSubscription->id]);
        
        try {
            DB::beginTransaction();

            // Get business_id and package_id from subscription metadata
            $businessId = $stripeSubscription->metadata->business_id ?? null;
            $packageId = $stripeSubscription->metadata->package_id ?? null;

            if (!$businessId || !$packageId) {
                Log::error('Missing business_id or package_id in subscription metadata');
                return;
            }

            $package = Package::find($packageId);
            if (!$package) {
                Log::error('Package not found', ['package_id' => $packageId]);
                return;
            }

            // Create or update local subscription record
            $subscription = Subscription::updateOrCreate(
                ['stripe_subscription_id' => $stripeSubscription->id],
                [
                    'business_id' => $businessId,
                    'package_id' => $packageId,
                    'gateway' => 'stripe',
                    'payment_transaction_id' => $stripeSubscription->id,
                    'status' => $this->mapStripeStatus($stripeSubscription->status),
                    'start_date' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                    'end_date' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                    'package_price' => $package->price,
                    'package_details' => json_encode($package->toArray()),
                    'is_recurring' => true,
                    'auto_renewal' => true,
                    'created_id' => $stripeSubscription->metadata->user_id ?? 1,
                ]
            );

            DB::commit();
            Log::info('Subscription created successfully', ['subscription_id' => $subscription->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating subscription: ' . $e->getMessage());
        }
    }

    /**
     * Handle subscription updated event
     */
    protected function handleSubscriptionUpdated($stripeSubscription)
    {
        Log::info('Stripe subscription updated', ['subscription_id' => $stripeSubscription->id]);

        try {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
            
            if ($subscription) {
                $subscription->update([
                    'status' => $this->mapStripeStatus($stripeSubscription->status),
                    'start_date' => date('Y-m-d H:i:s', $stripeSubscription->current_period_start),
                    'end_date' => date('Y-m-d H:i:s', $stripeSubscription->current_period_end),
                ]);
                
                Log::info('Subscription updated successfully', ['subscription_id' => $subscription->id]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating subscription: ' . $e->getMessage());
        }
    }

    /**
     * Handle subscription deleted/cancelled event
     */
    protected function handleSubscriptionDeleted($stripeSubscription)
    {
        Log::info('Stripe subscription deleted', ['subscription_id' => $stripeSubscription->id]);

        try {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
            
            if ($subscription) {
                $subscription->update([
                    'status' => 'cancelled',
                    'auto_renewal' => false,
                ]);
                
                Log::info('Subscription cancelled successfully', ['subscription_id' => $subscription->id]);
            }
        } catch (\Exception $e) {
            Log::error('Error cancelling subscription: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful invoice payment (renewal)
     */
    protected function handleInvoicePaymentSucceeded($invoice)
    {
        if ($invoice->subscription) {
            Log::info('Invoice payment succeeded', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription
            ]);

            try {
                $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();
                
                if ($subscription) {
                    // Create a new subscription record for the renewal
                    $newSubscription = $subscription->replicate();
                    $newSubscription->payment_transaction_id = $invoice->id;
                    $newSubscription->start_date = date('Y-m-d H:i:s', $invoice->period_start);
                    $newSubscription->end_date = date('Y-m-d H:i:s', $invoice->period_end);
                    $newSubscription->status = 'approved';
                    $newSubscription->created_at = now();
                    $newSubscription->save();

                    Log::info('Renewal subscription created', ['subscription_id' => $newSubscription->id]);
                }
            } catch (\Exception $e) {
                Log::error('Error handling invoice payment: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle failed invoice payment
     */
    protected function handleInvoicePaymentFailed($invoice)
    {
        if ($invoice->subscription) {
            Log::warning('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription
            ]);

            try {
                $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();
                
                if ($subscription) {
                    // Mark subscription as having payment issues
                    $subscription->update([
                        'status' => 'payment_failed',
                    ]);
                    
                    Log::info('Subscription marked as payment failed', ['subscription_id' => $subscription->id]);
                }
            } catch (\Exception $e) {
                Log::error('Error handling failed payment: ' . $e->getMessage());
            }
        }
    }

    /**
     * Map Stripe subscription status to local status
     */
    protected function mapStripeStatus($stripeStatus)
    {
        $statusMap = [
            'active' => 'approved',
            'trialing' => 'approved',
            'past_due' => 'payment_failed',
            'canceled' => 'cancelled',
            'unpaid' => 'payment_failed',
            'incomplete' => 'waiting',
            'incomplete_expired' => 'cancelled',
        ];

        return $statusMap[$stripeStatus] ?? 'waiting';
    }
}