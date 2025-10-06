@extends('layouts.guest')
@section('title', 'Payment Submitted Successfully')
@section('content')

<style>
    .success-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px 0;
        display: flex;
        align-items: center;
    }
    
    .success-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin: 20px auto;
        max-width: 800px;
    }
    
    .success-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 40px 30px;
        text-align: center;
    }
    
    .success-icon {
        font-size: 4rem;
        margin-bottom: 20px;
    }
    
    .success-header h1 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: 300;
        letter-spacing: 1px;
    }
    
    .success-header p {
        margin: 15px 0 0 0;
        opacity: 0.9;
        font-size: 1.2rem;
    }
    
    .success-content {
        padding: 40px 30px;
    }
    
    .payment-summary {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .summary-row:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 1.1rem;
        color: #28a745;
    }
    
    .summary-label {
        color: #6c757d;
    }
    
    .summary-value {
        font-weight: 600;
        color: #495057;
    }
    
    .invoice-details {
        background: #e8f5e8;
        border: 1px solid #c8e6c9;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }
    
    .invoice-details h4 {
        color: #2e7d32;
        margin-bottom: 15px;
    }
    
    .next-steps {
        background: #e3f2fd;
        border: 1px solid #bbdefb;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }
    
    .next-steps h4 {
        color: #1976d2;
        margin-bottom: 15px;
    }
    
    .next-steps ol {
        margin: 0;
        padding-left: 20px;
    }
    
    .next-steps li {
        margin-bottom: 8px;
        color: #495057;
    }
    
    .reference-number {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .reference-number strong {
        font-size: 1.2rem;
        color: #856404;
    }
    
    .contact-info {
        text-align: center;
        color: #6c757d;
        font-size: 0.95rem;
    }
    
    .action-buttons {
        text-align: center;
        margin-top: 30px;
    }
    
    .btn-primary-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        padding: 12px 30px;
        border-radius: 25px;
        text-decoration: none;
        display: inline-block;
        transition: transform 0.2s ease;
        margin: 0 10px;
    }
    
    .btn-primary-custom:hover {
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .success-card {
            margin: 10px;
            border-radius: 10px;
        }
        
        .success-header {
            padding: 30px 20px;
        }
        
        .success-header h1 {
            font-size: 2rem;
        }
        
        .success-icon {
            font-size: 3rem;
        }
        
        .success-content {
            padding: 30px 20px;
        }
        
        .payment-summary,
        .invoice-details,
        .next-steps {
            padding: 20px;
        }
        
        .summary-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn-primary-custom {
            margin: 5px 0;
            display: block;
        }
    }
</style>

<div class="success-container">
    <div class="container">
        <div class="success-card">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Payment Submitted!</h1>
                <p>Your payment has been successfully submitted for review</p>
            </div>

            <div class="success-content">
                <div class="reference-number">
                    <div>
                        <strong>Reference Number: #{{ str_pad($payment_id, 6, '0', STR_PAD_LEFT) }}</strong>
                    </div>
                    <small>Please keep this reference number for your records</small>
                </div>

                <div class="payment-summary">
                    <h4 style="margin-bottom: 20px; color: #495057;">Payment Summary</h4>
                    
                    <div class="summary-row">
                        <span class="summary-label">Customer:</span>
                        <span class="summary-value">{{ $contact->name ?? 'Guest Customer' }}</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Payment Method:</span>
                        <span class="summary-value">Bank Transfer</span>
                    </div>
                    
                    @if($bank_account)
                    <div class="summary-row">
                        <span class="summary-label">Bank Account:</span>
                        <span class="summary-value">{{ $bank_account->bank_name ?? '' }} - {{ $bank_account->account_name ?? '' }}</span>
                    </div>
                    @endif
                    
                    <div class="summary-row">
                        <span class="summary-label">Submitted At:</span>
                        <span class="summary-value">{{ now()->format('M j, Y g:i A') }}</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Payment Amount:</span>
                        <span class="summary-value">{{ number_format($amount, 2) }}</span>
                    </div>
                </div>

                <div class="invoice-details">
                    <h4><i class="fas fa-file-invoice"></i> Invoice Details</h4>
                    <div class="summary-row">
                        <span class="summary-label">Invoice Number:</span>
                        <span class="summary-value">{{ $transaction->invoice_no }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Invoice Date:</span>
                        <span class="summary-value">{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('M j, Y') }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Invoice Total:</span>
                        <span class="summary-value">{{ number_format($transaction->final_total, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Payment Applied:</span>
                        <span class="summary-value">{{ number_format($amount, 2) }}</span>
                    </div>
                    @php
                        $remaining = $transaction->final_total - $amount;
                    @endphp
                    <div class="summary-row">
                        <span class="summary-label">Remaining Balance:</span>
                        <span class="summary-value" style="color: {{ $remaining <= 0 ? '#28a745' : '#dc3545' }}">
                            {{ number_format($remaining, 2) }}
                            @if($remaining <= 0)
                                <small>(Fully Paid)</small>
                            @endif
                        </span>
                    </div>
                </div>

                <div class="next-steps">
                    <h4><i class="fas fa-list-check"></i> What happens next?</h4>
                    <ol>
                        <li><strong>Review Process:</strong> Our team will review your payment submission within 1-2 business days</li>
                        <li><strong>Verification:</strong> We will verify the payment details and receipt you provided</li>
                        <li><strong>Approval:</strong> Once approved, the payment will be applied to your invoice automatically</li>
                        <li><strong>Confirmation:</strong> You will receive an email confirmation once the payment is processed</li>
                        <li><strong>Updated Records:</strong> Your account and invoice status will be updated accordingly</li>
                    </ol>
                </div>

                <div class="contact-info">
                    <p><strong>Need help?</strong></p>
                    <p>If you have any questions about your payment submission, please contact us with reference number <strong>#{{ str_pad($payment_id, 6, '0', STR_PAD_LEFT) }}</strong></p>
                </div>

                <div class="action-buttons">
                    <a href="{{ route('show_invoice', $token) }}" class="btn-primary-custom">
                        <i class="fas fa-file-invoice"></i> View Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection