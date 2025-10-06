@extends('layouts.guest')
@section('title', 'Bank Transfer Payment - ' . $transaction->invoice_no)
@section('content')

<style>
    .payment-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px 0;
    }
    
    .payment-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin: 20px auto;
        max-width: 800px;
    }
    
    .payment-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .payment-header h1 {
        margin: 0;
        font-size: 2.2rem;
        font-weight: 300;
        letter-spacing: 1px;
    }
    
    .payment-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .invoice-summary {
        background: #f8f9fa;
        padding: 25px 30px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .invoice-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .invoice-detail {
        text-align: center;
    }
    
    .invoice-detail .label {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .invoice-detail .value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
    }
    
    .amount-due .value {
        color: #dc3545;
        font-size: 1.5rem;
    }
    
    .payment-content {
        padding: 40px;
    }
    
    .step-indicator {
        display: flex;
        justify-content: center;
        margin-bottom: 40px;
    }
    
    .step {
        display: flex;
        align-items: center;
        margin: 0 15px;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-right: 10px;
        transition: all 0.3s ease;
    }
    
    .step.active .step-number {
        background: #28a745;
        color: white;
    }
    
    .step-text {
        font-weight: 600;
        color: #6c757d;
    }
    
    .step.active .step-text {
        color: #28a745;
    }
    
    .bank-selector {
        margin-bottom: 30px;
    }
    
    .bank-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 120px));
        gap: 15px;
        margin-bottom: 30px;
        justify-content: flex-start;
    }
    
    .bank-option {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        width: 120px;
        height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    
    .bank-option:hover {
        border-color: #28a745;
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.1);
        transform: translateY(-2px);
    }
    
    .bank-option.selected {
        border-color: #28a745;
        background: rgba(40, 167, 69, 0.05);
    }
    
    .bank-logo {
        width: 60px;
        height: 60px;
        object-fit: contain;
        margin-bottom: 6px;
        border-radius: 6px;
    }
    
    .bank-name {
        font-weight: 600;
        font-size: 1rem;
        color: #2c3e50;
        line-height: 1.2;
        margin: 0;
    }
    
    .bank-details {
        font-size: 0.9rem;
        color: #6c757d;
        line-height: 1.5;
    }
    
    .account-details {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        border-left: 4px solid #28a745;
        display: none;
    }
    
    .account-details.show {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .account-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .info-label {
        font-weight: 600;
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .info-value {
        font-weight: 700;
        color: #2c3e50;
        font-family: 'Courier New', monospace;
    }
    
    .copy-btn {
        background: #28a745;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-left: 10px;
    }
    
    .copy-btn:hover {
        background: #218838;
        transform: scale(1.05);
    }
    
    .payment-form {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }
    
    .amount-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .amount-btn {
        padding: 8px 16px;
        border: 2px solid #e9ecef;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s ease;
        font-size: 0.9rem;
    }
    
    .amount-btn:hover {
        border-color: #28a745;
        color: #28a745;
    }
    
    .amount-btn.selected {
        background: #28a745;
        border-color: #28a745;
        color: white;
    }
    
    .file-upload {
        border: 2px dashed #e9ecef;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .file-upload:hover {
        border-color: #28a745;
        background: rgba(40, 167, 69, 0.05);
    }
    
    .file-upload.dragover {
        border-color: #28a745;
        background: rgba(40, 167, 69, 0.1);
    }
    
    .upload-icon {
        font-size: 3rem;
        color: #6c757d;
        margin-bottom: 15px;
    }
    
    .upload-text {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .upload-hint {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .file-preview {
        display: none;
        margin-top: 20px;
        padding: 15px;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 8px;
        border-left: 4px solid #28a745;
    }
    
    .submit-btn {
        width: 100%;
        padding: 15px 30px;
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .submit-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
    }
    
    .submit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 30px;
        transition: color 0.3s ease;
    }
    
    .back-btn:hover {
        color: #28a745;
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .payment-content {
            padding: 20px;
        }
        
        .invoice-details {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .bank-grid {
            grid-template-columns: 1fr;
        }
        
        .account-info {
            grid-template-columns: 1fr;
        }
        
        .info-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .amount-buttons {
            flex-direction: column;
        }
        
        .amount-btn {
            text-align: center;
        }
    }
</style>

<div class="payment-container">
    <div class="container">
        <div class="payment-card">
            <!-- Payment Header -->
            <div class="payment-header">
                <h1><i class="fas fa-university"></i> Bank Transfer Payment</h1>
                <p>Secure payment processing for Invoice #{{$transaction->invoice_no}}</p>
            </div>
            
            <!-- Invoice Summary -->
            <div class="invoice-summary">
                <div class="invoice-details">
                    <div class="invoice-detail">
                        <div class="label">Invoice Number</div>
                        <div class="value">{{$transaction->invoice_no}}</div>
                    </div>
                    <div class="invoice-detail">
                        <div class="label">Invoice Date</div>
                        <div class="value">{{@format_datetime($transaction->transaction_date)}}</div>
                    </div>
                    <div class="invoice-detail amount-due">
                        <div class="label">Amount Due</div>
                        <div class="value">{{$business->currency_symbol}}{{number_format($due_amount, 2)}}</div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Content -->
            <div class="payment-content">
                <a href="{{url('/invoice/'.$transaction->invoice_token)}}" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Invoice
                </a>
                
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" id="step1">
                        <div class="step-number">1</div>
                        <div class="step-text">Select Bank</div>
                    </div>
                    <div class="step" id="step2">
                        <div class="step-number">2</div>
                        <div class="step-text">Enter Amount</div>
                    </div>
                    <div class="step" id="step3">
                        <div class="step-number">3</div>
                        <div class="step-text">Upload Receipt</div>
                    </div>
                </div>
                
                <form id="bank-transfer-form" action="{{route('submit_bank_transfer_payment', $transaction->invoice_token)}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Bank Selection -->
                    <div class="bank-selector">
                        <h3>Select Bank Account</h3>
                        <div class="bank-grid">
                            @if(count($bank_accounts) === 1)
                                @foreach($bank_accounts as $account)
                                    <div class="bank-option selected" data-account-id="{{$account['id']}}">
                                        @if($account['bank_logo'])
                                            <img src="{{$account['bank_logo']}}" alt="{{$account['bank_name']}}" class="bank-logo">
                                        @else
                                            <div class="bank-logo" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #6c757d;">
                                                <i class="fas fa-university"></i>
                                            </div>
                                        @endif
                                        <div class="bank-name">{{$account['bank_name']}}</div>
                                    </div>
                                    <input type="hidden" name="bank_account_id" value="{{$account['id']}}">
                                @endforeach
                            @else
                                @foreach($bank_accounts as $account)
                                    <div class="bank-option" data-account-id="{{$account['id']}}">
                                        @if($account['bank_logo'])
                                            <img src="{{$account['bank_logo']}}" alt="{{$account['bank_name']}}" class="bank-logo">
                                        @else
                                            <div class="bank-logo" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #6c757d;">
                                                <i class="fas fa-university"></i>
                                            </div>
                                        @endif
                                        <div class="bank-name">{{$account['bank_name']}}</div>
                                    </div>
                                @endforeach
                                <input type="hidden" name="bank_account_id" id="selected_bank_account">
                            @endif
                        </div>
                    </div>
                    
                    <!-- Account Details -->
                    @foreach($bank_accounts as $account)
                        <div class="account-details" id="account-details-{{$account['id']}}" @if(count($bank_accounts) === 1) style="display: block;" @endif>
                            <h4>Transfer to this account:</h4>
                            <div class="account-info">
                                <div class="info-item">
                                    <div>
                                        <div class="info-label">Account Name</div>
                                        <div class="info-value">{{$account['account_name']}}</div>
                                    </div>
                                    <button type="button" class="copy-btn" onclick="copyToClipboard('{{$account['account_name']}}', this)">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                                <div class="info-item">
                                    <div>
                                        <div class="info-label">Account Number</div>
                                        <div class="info-value">{{$account['account_number']}}</div>
                                    </div>
                                    <button type="button" class="copy-btn" onclick="copyToClipboard('{{$account['account_number']}}', this)">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>
                            <p style="color: #6c757d; font-style: italic; text-align: center;">
                                <i class="fas fa-info-circle"></i> Please use Invoice #{{$transaction->invoice_no}} as your transfer reference
                            </p>
                        </div>
                    @endforeach
                    
                    <!-- Payment Form -->
                    <div class="payment-form">
                        <div class="form-group">
                            <label class="form-label">Payment Amount</label>
                            <div class="amount-buttons">
                                @if($due_amount > 0)
                                    <button type="button" class="amount-btn" data-amount="{{$due_amount}}">
                                        Full Amount ({{$business->currency_symbol}}{{number_format($due_amount, 2)}})
                                    </button>
                                @endif
                                @if($due_amount >= 100)
                                    <button type="button" class="amount-btn" data-amount="{{$due_amount / 2}}">
                                        50% ({{$business->currency_symbol}}{{number_format($due_amount / 2, 2)}})
                                    </button>
                                @endif
                                <button type="button" class="amount-btn" id="custom-amount-btn">Custom Amount</button>
                            </div>
                            <input type="number" 
                                   class="form-control" 
                                   name="amount" 
                                   id="payment_amount"
                                   placeholder="Enter payment amount" 
                                   min="0.01" 
                                   max="{{$due_amount}}" 
                                   step="0.01" 
                                   required>
                            <div style="color: #495057; margin-top: 10px; font-size: 14px; line-height: 1.4;">
                                <strong>You can make partial payments.</strong><br>
                                Minimum: <strong>{{$business->currency_symbol}}0.01</strong>, 
                                Maximum: <strong>{{$business->currency_symbol}}{{number_format($due_amount, 2)}}</strong>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Upload Payment Receipt</label>
                            <div class="file-upload" onclick="document.getElementById('receipt_file').click()">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="upload-text">Click to upload receipt</div>
                                <div class="upload-hint">or drag and drop your file here</div>
                                <div class="upload-hint">Supported: JPG, PNG, PDF (Max: 5MB)</div>
                            </div>
                            <input type="file" 
                                   id="receipt_file" 
                                   name="receipt_file" 
                                   accept="image/*,application/pdf" 
                                   style="display: none;" 
                                   required>
                            <div class="file-preview" id="file-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Add any additional information about this payment..."></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn" id="submit-payment">
                            <i class="fas fa-paper-plane"></i>
                            Submit Payment for Approval
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script>
$(document).ready(function() {
    // Bank selection
    $('.bank-option').click(function() {
        if ($(this).hasClass('selected')) return;
        
        $('.bank-option').removeClass('selected');
        $(this).addClass('selected');
        
        const accountId = $(this).data('account-id');
        $('#selected_bank_account').val(accountId);
        
        // Show corresponding account details
        $('.account-details').removeClass('show');
        $('#account-details-' + accountId).addClass('show');
        
        // Update step indicator
        updateStep(2);
    });
    
    // Amount button selection
    $('.amount-btn').click(function() {
        $('.amount-btn').removeClass('selected');
        $(this).addClass('selected');
        
        if ($(this).attr('id') === 'custom-amount-btn') {
            $('#payment_amount').focus();
        } else {
            const amount = $(this).data('amount');
            $('#payment_amount').val(amount);
        }
        
        updateStep(3);
    });
    
    // Custom amount input
    $('#payment_amount').on('input', function() {
        $('.amount-btn').removeClass('selected');
        $('#custom-amount-btn').addClass('selected');
    });
    
    // File upload handling
    $('#receipt_file').change(function() {
        const file = this.files[0];
        if (file) {
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            
            $('#file-preview').html(`
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-file-alt" style="color: #28a745; font-size: 1.5rem;"></i>
                    <div>
                        <div style="font-weight: 600;">${fileName}</div>
                        <div style="font-size: 0.9rem; color: #6c757d;">Size: ${fileSize} MB</div>
                    </div>
                    <button type="button" onclick="removeFile()" style="margin-left: auto; background: #dc3545; color: white; border: none; border-radius: 4px; padding: 5px 10px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).show();
            
            checkFormCompletion();
        }
    });
    
    // Drag and drop
    $('.file-upload').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    $('.file-upload').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    $('.file-upload').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            $('#receipt_file')[0].files = files;
            $('#receipt_file').trigger('change');
        }
    });
    
    // Form validation
    $('#bank-transfer-form').submit(function(e) {
        let isValid = true;
        let errors = [];
        
        // Check bank selection
        if ($('#selected_bank_account').val() === '' && $('.bank-option.selected').length === 0) {
            errors.push('Please select a bank account');
            isValid = false;
        }
        
        // Check amount
        const amount = parseFloat($('#payment_amount').val());
        const maxAmount = parseFloat('{{$due_amount}}');
        if (isNaN(amount) || amount <= 0) {
            errors.push('Please enter a valid payment amount');
            isValid = false;
        } else if (amount > maxAmount) {
            errors.push('Payment amount cannot exceed the due amount');
            isValid = false;
        }
        
        // Check file
        if (!$('#receipt_file')[0].files.length) {
            errors.push('Please upload a payment receipt');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n• ' + errors.join('\n• '));
            return false;
        }
        
        // Show loading state
        $('#submit-payment').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
    });
    
    function updateStep(step) {
        $('.step').removeClass('active');
        for (let i = 1; i <= step; i++) {
            $('#step' + i).addClass('active');
        }
    }
    
    function checkFormCompletion() {
        const hasBank = $('.bank-option.selected').length > 0;
        const hasAmount = $('#payment_amount').val() !== '';
        const hasFile = $('#receipt_file')[0].files.length > 0;
        
        $('#submit-payment').prop('disabled', !(hasBank && hasAmount && hasFile));
    }
    
    // Check completion on input changes
    $('#payment_amount').on('input', checkFormCompletion);
    
    // Initialize for single bank
    @if(count($bank_accounts) === 1)
        updateStep(2);
    @endif
});

function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        const originalHtml = $(button).html();
        $(button).html('<i class="fas fa-check"></i> Copied!').css('background', '#28a745');
        
        setTimeout(function() {
            $(button).html(originalHtml).css('background', '#28a745');
        }, 2000);
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        const originalHtml = $(button).html();
        $(button).html('<i class="fas fa-check"></i> Copied!');
        setTimeout(function() {
            $(button).html(originalHtml);
        }, 2000);
    });
}

function removeFile() {
    $('#receipt_file').val('');
    $('#file-preview').hide();
    checkFormCompletion();
}
</script>
@endsection