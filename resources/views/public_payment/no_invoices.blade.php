@extends('layouts.guest')
@section('title', 'No Outstanding Invoices')
@section('content')

<style>
    .no-invoices-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px 0;
        display: flex;
        align-items: center;
    }
    
    .no-invoices-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin: 20px auto;
        max-width: 600px;
        text-align: center;
    }
    
    .no-invoices-header {
        background: linear-gradient(135deg, #17a2b8, #20c997);
        color: white;
        padding: 40px 30px;
    }
    
    .no-invoices-icon {
        font-size: 4rem;
        margin-bottom: 20px;
        opacity: 0.9;
    }
    
    .no-invoices-header h1 {
        margin: 0;
        font-size: 2.2rem;
        font-weight: 300;
        letter-spacing: 1px;
    }
    
    .no-invoices-header p {
        margin: 15px 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .no-invoices-content {
        padding: 40px 30px;
    }
    
    .customer-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }
    
    .customer-name {
        font-size: 1.3rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 10px;
    }
    
    .customer-details {
        color: #6c757d;
        font-size: 0.95rem;
    }
    
    .status-message {
        font-size: 1.1rem;
        color: #28a745;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .info-box {
        background: #e3f2fd;
        border: 1px solid #bbdefb;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 30px;
    }
    
    .info-box h4 {
        color: #1976d2;
        margin-bottom: 15px;
    }
    
    .info-box p {
        color: #495057;
        margin-bottom: 10px;
        line-height: 1.6;
    }
    
    .contact-info {
        color: #6c757d;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    @media (max-width: 768px) {
        .no-invoices-card {
            margin: 10px;
            border-radius: 10px;
        }
        
        .no-invoices-header {
            padding: 30px 20px;
        }
        
        .no-invoices-header h1 {
            font-size: 1.8rem;
        }
        
        .no-invoices-icon {
            font-size: 3rem;
        }
        
        .no-invoices-content {
            padding: 30px 20px;
        }
        
        .customer-info,
        .info-box {
            padding: 20px;
        }
    }
</style>

<div class="no-invoices-container">
    <div class="container">
        <div class="no-invoices-card">
            <div class="no-invoices-header">
                <div class="no-invoices-icon">
                    <i class="fas fa-check-double"></i>
                </div>
                <h1>All Caught Up!</h1>
                <p>No outstanding invoices to pay</p>
            </div>

            <div class="no-invoices-content">
                <div class="customer-info">
                    <div class="customer-name">{{ $contact->name }}</div>
                    @if($contact->email || $contact->mobile)
                    <div class="customer-details">
                        @if($contact->email)
                            <div><i class="fas fa-envelope"></i> {{ $contact->email }}</div>
                        @endif
                        @if($contact->mobile)
                            <div><i class="fas fa-phone"></i> {{ $contact->mobile }}</div>
                        @endif
                    </div>
                    @endif
                </div>

                <div class="status-message">
                    <i class="fas fa-smile"></i> Great news! You currently have no outstanding invoices that require payment.
                </div>

                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> What this means:</h4>
                    <p>• All your invoices have been paid in full</p>
                    <p>• Your account is in good standing</p>
                    <p>• No immediate payment action is required</p>
                </div>

                <div class="contact-info">
                    <p><strong>Questions about your account?</strong></p>
                    <p>If you believe this is an error or if you have questions about your account status, please contact us for assistance.</p>
                    <br>
                    <p><small>This payment portal will become available again when new invoices are issued to your account.</small></p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection