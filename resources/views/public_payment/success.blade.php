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
    
    .allocation-details {
        margin-bottom: 30px;
    }
    
    .allocation-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .allocation-table th,
    .allocation-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }
    
    .allocation-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    
    .invoice-number {
        font-weight: 600;
        color: #007bff;
    }
    
    .amount {
        font-weight: 600;
        text-align: right;
    }
    
    .paid-amount {
        color: #28a745;
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
        .next-steps {
            padding: 20px;
        }
        
        .summary-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .allocation-table {
            font-size: 0.9rem;
        }
        
        .allocation-table th,
        .allocation-table td {
            padding: 8px;
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
                        <strong>Reference Number: #{{ str_pad($submission_id, 6, '0', STR_PAD_LEFT) }}</strong>
                    </div>
                    <small>Please keep this reference number for your records</small>
                </div>

                <div class="payment-summary">
                    <h4 style="margin-bottom: 20px; color: #495057;">Payment Summary</h4>
                    
                    <div class="summary-row">
                        <span class="summary-label">Customer:</span>
                        <span class="summary-value">{{ $contact->name }}</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Payment Method:</span>
                        <span class="summary-value">Bank Transfer</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Submitted At:</span>
                        <span class="summary-value">{{ now()->format('M j, Y g:i A') }}</span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Total Amount:</span>
                        <span class="summary-value">{{ number_format($total_amount, 2) }}</span>
                    </div>
                </div>

                @if(count($allocations) > 0)
                <div class="allocation-details">
                    <h4 style="margin-bottom: 15px; color: #495057;">Payment Allocation</h4>
                    <p style="color: #6c757d; margin-bottom: 15px;">Your payment will be allocated to the following invoices:</p>
                    
                    <table class="allocation-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount Due</th>
                                <th class="amount">Payment Applied</th>
                                <th class="amount">Remaining Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allocations as $allocation)
                            <tr>
                                <td class="invoice-number">{{ $allocation['invoice_no'] }}</td>
                                <td class="amount">{{ number_format($allocation['due_amount'], 2) }}</td>
                                <td class="amount paid-amount">{{ number_format($allocation['applied_amount'], 2) }}</td>
                                <td class="amount">{{ number_format($allocation['remaining_balance'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="next-steps">
                    <h4><i class="fas fa-list-check"></i> What happens next?</h4>
                    <ol>
                        <li><strong>Review Process:</strong> Our team will review your payment submission within 1-2 business days</li>
                        <li><strong>Verification:</strong> We will verify the payment details and receipt you provided</li>
                        <li><strong>Approval:</strong> Once approved, the payment will be applied to your invoices automatically</li>
                        <li><strong>Confirmation:</strong> You will receive an email confirmation once the payment is processed</li>
                        <li><strong>Updated Records:</strong> Your account and invoice statuses will be updated accordingly</li>
                    </ol>
                </div>

                <div class="contact-info">
                    <p><strong>Need help?</strong></p>
                    <p>If you have any questions about your payment submission, please contact us with reference number <strong>#{{ str_pad($submission_id, 6, '0', STR_PAD_LEFT) }}</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection