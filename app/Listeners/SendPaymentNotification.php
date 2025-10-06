<?php

namespace App\Listeners;

use App\Events\TransactionPaymentAdded;
use App\Utils\NotificationUtil;
use App\Transaction;

class SendPaymentNotification
{
    protected $notificationUtil;

    /**
     * Create the event listener.
     *
     * @param  NotificationUtil  $notificationUtil
     * @return void
     */
    public function __construct(NotificationUtil $notificationUtil)
    {
        $this->notificationUtil = $notificationUtil;
    }

    /**
     * Handle the event.
     *
     * @param  TransactionPaymentAdded  $event
     * @return void
     */
    public function handle(TransactionPaymentAdded $event)
    {
        $transactionPayment = $event->transactionPayment;
        
        // Get the transaction with contact information
        $transaction = Transaction::with('contact')->find($transactionPayment->transaction_id);
        
        if (!$transaction || !$transaction->contact) {
            return;
        }

        // Only send notification for sell transactions (customer invoices)
        if ($transaction->type === 'sell') {
            // Send payment received notification
            $this->notificationUtil->autoSendNotification(
                $transaction->business_id,
                'payment_received',
                $transaction,
                $transaction->contact
            );
        }
    }
}