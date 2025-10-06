@extends('layouts.guest')
@section('title', 'Payment Portal - ' . $contact->name)
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
        max-width: 900px;
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
    
    .customer-summary {
        background: #f8f9fa;
        padding: 25px 30px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .customer-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .detail-item {
        text-align: center;
    }
    
    .detail-label {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .detail-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
    }
    
    .invoices-section {
        padding: 30px;
    }
    
    .invoices-table {
        width: 100%;
        margin-bottom: 30px;
        border-collapse: collapse;
    }
    
    .invoices-table th,
    .invoices-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }
    
    .invoices-table th {
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
    
    .download-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9rem;
        transition: background-color 0.2s;
    }
    
    .download-btn:hover {
        background-color: #0056b3;
        color: white;
        text-decoration: none;
    }
    
    .download-btn i {
        font-size: 0.85rem;
    }
    
    .total-row {
        background-color: #e3f2fd;
        font-weight: 700;
    }
    
    .payment-form {
        background: #f8f9fa;
        padding: 30px;
        margin-top: 20px;
        border-radius: 10px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #495057;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    
    .file-upload {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .file-upload-input {
        position: absolute;
        left: -9999px;
    }
    
    .file-upload-label {
        display: block;
        padding: 12px 15px;
        border: 2px dashed #007bff;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #007bff;
        background: white;
    }
    
    .file-upload-label:hover {
        background: #f8f9ff;
        border-color: #0056b3;
    }
    
    .submit-btn {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 15px 40px;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
    }
    
    .submit-btn:active {
        transform: translateY(0);
    }
    
    .allocation-preview {
        background: #e8f5e8;
        border: 1px solid #c3e6cb;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        display: none;
    }
    
    .allocation-preview.show {
        display: block;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    
    .alert-danger {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
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
    
    .copy-btn:hover {
        background: #218838;
        transform: scale(1.05);
    }
    
    .account-details.show {
        display: block !important;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
        .payment-card {
            margin: 10px;
            border-radius: 10px;
        }
        
        .payment-header {
            padding: 20px;
        }
        
        .payment-header h1 {
            font-size: 1.8rem;
        }
        
        .customer-summary,
        .invoices-section {
            padding: 20px;
        }
        
        .customer-details {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .invoices-table {
            font-size: 0.9rem;
        }
        
        .invoices-table th,
        .invoices-table td {
            padding: 8px;
        }
        
        .bank-grid {
            grid-template-columns: 1fr !important;
        }
        
        .account-info {
            grid-template-columns: 1fr !important;
        }
        
        .info-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .copy-btn {
            margin-left: 0 !important;
            margin-top: 10px;
        }
    }
</style>

<div class="payment-container">
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h1>Payment Portal</h1>
                <p>Secure payment submission for {{ $contact->name }}</p>
            </div>

            <div class="customer-summary">
                <div class="customer-details">
                    <div class="detail-item">
                        <div class="detail-label">Customer</div>
                        <div class="detail-value">{{ $contact->name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Outstanding</div>
                        <div class="detail-value">{{ number_format($unpaid_invoices->sum('due_amount'), 2) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Number of Invoices</div>
                        <div class="detail-value">{{ $unpaid_invoices->count() }}</div>
                    </div>
                </div>
            </div>

            <div class="invoices-section">
                <h3 style="margin-bottom: 20px; color: #495057;">Outstanding Invoices</h3>
                
                <table class="invoices-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th class="amount">Due Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaid_invoices as $invoice)
                        <tr>
                            <td class="invoice-number">{{ $invoice->invoice_no }}</td>
                            <td>{{ \Carbon\Carbon::parse($invoice->transaction_date)->format('M j, Y') }}</td>
                            <td class="amount">{{ number_format($invoice->final_total, 2) }}</td>
                            <td class="amount">{{ number_format($invoice->total_paid, 2) }}</td>
                            <td class="amount">{{ number_format($invoice->due_amount, 2) }}</td>
                            <td>
                                <a href="{{ route('public-payment.download-invoice', [$contact->payment_token, $invoice->id]) }}" 
                                   class="download-btn" 
                                   target="_blank"
                                   title="Download Invoice">
                                    <i class="fa fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="5"><strong>Total Outstanding</strong></td>
                            <td class="amount"><strong>{{ number_format($unpaid_invoices->sum('due_amount'), 2) }}</strong></td>
                        </tr>
                    </tbody>
                </table>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <!-- Bank Account Selection Section -->
                @if(!empty($bank_accounts))
                <div style="margin-bottom: 30px;">
                    <h4 style="margin-bottom: 25px; color: #495057;">Select Payment Method</h4>
                    
                    <div class="bank-selector">
                        <div class="bank-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
                            @foreach($bank_accounts as $account)
                                <div class="bank-option" data-account-id="{{$account['id']}}" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: white;">
                                    @if(!empty($account['bank_logo']))
                                        <img src="{{$account['bank_logo']}}" alt="{{$account['bank_name']}}" style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 10px; border-radius: 6px;">
                                    @else
                                        <div style="width: 60px; height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #6c757d; margin: 0 auto 10px; border-radius: 6px;">
                                            <i class="fas fa-university"></i>
                                        </div>
                                    @endif
                                    <div style="font-weight: 600; font-size: 1rem; color: #2c3e50; margin-bottom: 5px;">{{$account['bank_name']}}</div>
                                    <div style="font-size: 0.9rem; color: #6c757d;">Bank Transfer</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Bank Account Details -->
                    @foreach($bank_accounts as $account)
                        <div class="account-details" id="account-details-{{$account['id']}}" style="background: #f8f9fa; border-radius: 12px; padding: 25px; margin-bottom: 30px; border-left: 4px solid #28a745; display: none;">
                            <h5 style="margin-bottom: 20px; color: #495057;">Transfer to this account: {{$account['bank_name']}}</h5>
                            <div class="account-info" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div class="info-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    <div>
                                        <div style="font-weight: 600; color: #6c757d; font-size: 0.9rem; margin-bottom: 5px;">Account Name</div>
                                        <div style="font-weight: 700; color: #2c3e50; font-family: 'Courier New', monospace;">{{$account['account_name']}}</div>
                                    </div>
                                    <button type="button" class="copy-btn" onclick="copyToClipboard('{{$account['account_name']}}', this)" style="background: #28a745; color: white; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s ease; margin-left: 10px;">
                                        <i class="fa fa-copy"></i> Copy
                                    </button>
                                </div>
                                <div class="info-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    <div>
                                        <div style="font-weight: 600; color: #6c757d; font-size: 0.9rem; margin-bottom: 5px;">Account Number</div>
                                        <div style="font-weight: 700; color: #2c3e50; font-family: 'Courier New', monospace;">{{$account['account_number']}}</div>
                                    </div>
                                    <button type="button" class="copy-btn" onclick="copyToClipboard('{{$account['account_number']}}', this)" style="background: #28a745; color: white; border: none; border-radius: 6px; padding: 8px 12px; font-size: 0.8rem; cursor: pointer; transition: all 0.2s ease; margin-left: 10px;">
                                        <i class="fa fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>
                            <p style="color: #6c757d; font-style: italic; text-align: center; margin: 0;">
                                <i class="fa fa-info-circle"></i> Please use your name as the transfer reference for easier identification
                            </p>
                        </div>
                    @endforeach
                </div>
                @endif

                <form action="{{ route('public-payment.submit', $contact->payment_token) }}" method="POST" enctype="multipart/form-data" id="paymentForm">
                    @csrf
                    @if(!empty($bank_accounts))
                        <input type="hidden" name="bank_account_id" id="selected_bank_account">
                    @endif
                    <div class="payment-form">
                        <h4 style="margin-bottom: 25px; color: #495057;">Payment Information</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="total_amount">Payment Amount *</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="total_amount" 
                                           name="total_amount" 
                                           step="0.01" 
                                           min="0.01"
                                           max="{{ $unpaid_invoices->sum('due_amount') }}" 
                                           placeholder="0.00"
                                           value="{{ old('total_amount', $unpaid_invoices->sum('due_amount')) }}" 
                                           required>
                                    <small class="text-muted">Maximum: {{ number_format($unpaid_invoices->sum('due_amount'), 2) }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="customer_name">Your Name *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="customer_name" 
                                           name="customer_name" 
                                           value="{{ old('customer_name', $contact->name) }}" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="customer_email">Email</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="customer_email" 
                                           name="customer_email" 
                                           value="{{ old('customer_email', $contact->email) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="customer_mobile">Phone Number</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="customer_mobile" 
                                           name="customer_mobile" 
                                           value="{{ old('customer_mobile', $contact->mobile) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Receipt/Proof</label>
                            <div class="file-upload">
                                <input type="file" 
                                       class="file-upload-input" 
                                       id="receipt_file" 
                                       name="receipt_file" 
                                       accept=".jpg,.jpeg,.png,.pdf">
                                <label for="receipt_file" class="file-upload-label" id="fileLabel">
                                    <i class="fa fa-cloud-upload"></i> Choose file or drag here
                                    <br><small>JPG, PNG, PDF (Max: 5MB)</small>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="notes">Additional Notes</label>
                            <textarea class="form-control" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="Any additional information...">{{ old('notes') }}</textarea>
                        </div>

                        <div class="allocation-preview" id="allocationPreview">
                            <h5 style="margin-bottom: 15px; color: #495057;">Payment Allocation Preview</h5>
                            <div id="allocationContent"></div>
                        </div>

                        <button type="submit" class="submit-btn">
                            <i class="fa fa-credit-card"></i> Submit Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Wait for jQuery to be available
function initializeBankSelection() {
    if (typeof jQuery === 'undefined') {
        setTimeout(initializeBankSelection, 100);
        return;
    }
    
    jQuery(document).ready(function($) {
        const invoices = @json($unpaid_invoices);
        
        // Bank account selection - Use event delegation to ensure it works
        $(document).on('click', '.bank-option', function() {
            const $this = $(this);
            
            if ($this.hasClass('selected')) return;
            
            $('.bank-option').removeClass('selected');
            $this.addClass('selected');
            
            const accountId = $this.data('account-id');
            $('#selected_bank_account').val(accountId);
            
            // Show corresponding account details
            $('.account-details').removeClass('show').hide();
            $('#account-details-' + accountId).addClass('show').show();
        });
        
        // File upload handler
        $('#receipt_file').on('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'Choose file or drag here';
            $('#fileLabel').html('<i class="fa fa-check"></i> ' + fileName);
        });
        
        // Amount input handler - show allocation preview
        $('#total_amount').on('input', function() {
            const amount = parseFloat($(this).val()) || 0;
            if (amount > 0) {
                showAllocationPreview(amount);
            } else {
                hideAllocationPreview();
            }
        });
        
        // Trigger allocation preview on page load since amount is prefilled
        $(document).ready(function() {
            const initialAmount = parseFloat($('#total_amount').val()) || 0;
            if (initialAmount > 0) {
                showAllocationPreview(initialAmount);
            }
        });
        
        function showAllocationPreview(totalAmount) {
            let remaining = totalAmount;
            let html = '<table style="width: 100%; font-size: 0.9rem;"><thead><tr><th>Invoice</th><th>Due</th><th>Will Pay</th><th>Remaining</th></tr></thead><tbody>';
            
            invoices.forEach(invoice => {
                if (remaining <= 0) return;
                
                const dueAmount = parseFloat(invoice.due_amount);
                const payAmount = Math.min(remaining, dueAmount);
                const remainingBalance = dueAmount - payAmount;
                
                html += `<tr>
                    <td>${invoice.invoice_no}</td>
                    <td style="text-align: right;">${dueAmount.toFixed(2)}</td>
                    <td style="text-align: right; color: #28a745; font-weight: 600;">${payAmount.toFixed(2)}</td>
                    <td style="text-align: right;">${remainingBalance.toFixed(2)}</td>
                </tr>`;
                
                remaining -= payAmount;
            });
            
            html += '</tbody></table>';
            
            if (remaining > 0) {
                html += `<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 5px; color: #856404;">
                    <strong>Note:</strong> Excess amount of ${remaining.toFixed(2)} will be kept as credit for future invoices.
                </div>`;
            }
            
            $('#allocationContent').html(html);
            $('#allocationPreview').addClass('show');
        }
        
        function hideAllocationPreview() {
            $('#allocationPreview').removeClass('show');
        }
        
        // Form validation
        $('#paymentForm').on('submit', function(e) {
            let isValid = true;
            let errors = [];
            
            // Check bank account selection if available
            @if(!empty($bank_accounts))
            if (!$('#selected_bank_account').val()) {
                errors.push('Please select a bank account for payment');
                isValid = false;
            }
            @endif
            
            const amount = parseFloat($('#total_amount').val()) || 0;
            const maxAmount = parseFloat('{{ $unpaid_invoices->sum("due_amount") }}');
            
            if (amount <= 0) {
                errors.push('Please enter a valid payment amount');
                isValid = false;
            }
            
            if (amount > maxAmount) {
                errors.push('Payment amount cannot exceed the total outstanding amount');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fix the following errors:\n• ' + errors.join('\n• '));
                return false;
            }
            
            // Show loading state
            $(this).find('.submit-btn').html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        });
    });
}

// Copy to clipboard function (works without jQuery)
function copyToClipboard(text, button) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showCopySuccess(button);
        }).catch(function() {
            fallbackCopyTextToClipboard(text, button);
        });
    } else {
        fallbackCopyTextToClipboard(text, button);
    }
}

function fallbackCopyTextToClipboard(text, button) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess(button);
    } catch (err) {
        alert('Failed to copy. Please copy manually: ' + text);
    }
    
    document.body.removeChild(textArea);
}

function showCopySuccess(button) {
    // Use vanilla JS if jQuery isn't available yet
    if (typeof jQuery !== 'undefined') {
        const originalHtml = jQuery(button).html();
        jQuery(button).html('<i class="fa fa-check"></i> Copied!').css('background', '#28a745');
        
        setTimeout(function() {
            jQuery(button).html(originalHtml).css('background', '#28a745');
        }, 2000);
    } else {
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fa fa-check"></i> Copied!';
        button.style.background = '#28a745';
        
        setTimeout(function() {
            button.innerHTML = originalHtml;
            button.style.background = '#28a745';
        }, 2000);
    }
}

// Initialize when page loads
initializeBankSelection();
</script>

@endsection