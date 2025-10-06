@extends('layouts.guest')
@section('title', $title)
@section('content')

<style>
    .modern-invoice-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px 0;
    }
    
    .invoice-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin: 20px auto;
        max-width: 900px;
    }
    
    .invoice-header {
        background: linear-gradient(135deg, #2c3e50, #3498db);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .invoice-header h1 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: 300;
        letter-spacing: 1px;
    }
    
    .invoice-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 1.1rem;
    }
    
    .invoice-actions {
        background: #f8f9fa;
        padding: 20px 30px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .modern-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-size: 0.95rem;
        cursor: pointer;
    }
    
    .btn-pay {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .btn-pay:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .btn-print {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
    }
    
    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
        color: white;
    }
    
    .btn-back {
        background: linear-gradient(135deg, #6c757d, #545b62);
        color: white;
    }
    
    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .invoice-content {
        padding: 40px;
        background: white;
    }
    
    .payment-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .status-paid {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .status-partial {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .status-due {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    @media print {
        .modern-invoice-container {
            background: white;
            padding: 0;
        }
        .invoice-card {
            box-shadow: none;
            border-radius: 0;
            margin: 0;
            max-width: none;
        }
        .invoice-header,
        .invoice-actions {
            display: none !important;
        }
        .no-print {
            display: none !important;
        }
    }
    
    @media (max-width: 768px) {
        .invoice-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .action-buttons {
            justify-content: center;
        }
        .modern-btn {
            flex: 1;
            justify-content: center;
            min-width: 140px;
        }
        .invoice-content {
            padding: 20px;
        }
        .invoice-header h1 {
            font-size: 2rem;
        }
    }
</style>

<div class="modern-invoice-container">
    <div class="container">
        <div class="invoice-card">
            <!-- Modern Header -->
            <div class="invoice-header no-print">
                <h1><i class="fas fa-file-invoice"></i> Invoice</h1>
                <p>Digital Receipt & Payment Portal</p>
            </div>
            
            <!-- Action Bar -->
            <div class="invoice-actions no-print">
                <div class="payment-status-container">
                    @php
                        $paymentStatus = 'due'; // Default
                        if (!empty($receipt['payment_info'])) {
                            if ($receipt['payment_info']['total_paid'] >= $receipt['payment_info']['total']) {
                                $paymentStatus = 'paid';
                            } elseif ($receipt['payment_info']['total_paid'] > 0) {
                                $paymentStatus = 'partial';
                            }
                        }
                    @endphp
                    <span class="payment-status status-{{$paymentStatus}}">
                        @if($paymentStatus == 'paid')
                            <i class="fas fa-check-circle"></i> Paid
                        @elseif($paymentStatus == 'partial')
                            <i class="fas fa-clock"></i> Partially Paid
                        @else
                            <i class="fas fa-exclamation-circle"></i> Payment Due
                        @endif
                    </span>
                </div>
                
                <div class="action-buttons">
                    @if(!empty($payment_link))
                        <a href="{{$payment_link}}" class="modern-btn btn-pay">
                            <i class="fas fa-credit-card"></i>
                            Pay Online
                        </a>
                    @endif
                    
                    <!-- Bank Transfer Payment Button -->
                    <a href="{{url('/invoice/'.request()->route('token').'/payment')}}" class="modern-btn btn-pay">
                        <i class="fas fa-university"></i>
                        Pay by Bank Transfer
                    </a>
                    
                    <button type="button" class="modern-btn btn-print" id="print_invoice">
                        <i class="fas fa-print"></i>
                        Print Invoice
                    </button>
                    
                    @auth
                        <a href="{{action([\App\Http\Controllers\SellController::class, 'index'])}}" class="modern-btn btn-back">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    @endauth
                </div>
            </div>
            
            <!-- Invoice Content -->
            <div class="invoice-content" id="invoice_content">
                {!! $receipt['html_content'] !!}
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        // Print functionality - removed auto-print on load
        $(document).on('click', '#print_invoice', function(){
            $('#invoice_content').printThis({
                importCSS: true,
                importStyle: true,
                printContainer: false,
                loadCSS: "{{ asset('css/app.css') }}",
                pageTitle: "Invoice - {{ $title }}",
                removeInline: false,
                printDelay: 333,
                header: null,
                footer: null
            });
        });
        
        // Smooth animations for buttons
        $('.modern-btn').hover(
            function() {
                $(this).addClass('animate__animated animate__pulse');
            },
            function() {
                $(this).removeClass('animate__animated animate__pulse');
            }
        );
    });
</script>
@endsection