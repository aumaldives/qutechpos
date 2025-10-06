<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Receipt-{{$receipt_details->invoice_no}}</title>
    <style>
        .thermal-receipt-container {
            font-family: 'Courier New', 'Roboto Mono', monospace;
            background: radial-gradient(circle, #f0f0f0 0%, #e0e0e0 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        
        .thermal-receipt-container * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .thermal-receipt {
            width: 80mm;
            max-width: 384px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }
        
        /* Perforation edges */
        .thermal-receipt::before,
        .thermal-receipt::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 8px;
            background: repeating-linear-gradient(
                45deg,
                transparent 0px,
                transparent 3px,
                white 3px,
                white 6px
            );
            z-index: 2;
        }
        
        .thermal-receipt::before {
            top: 0;
        }
        
        .thermal-receipt::after {
            bottom: 0;
        }
        
        .receipt-content {
            padding: 12px 8px;
            font-size: 11px;
            line-height: 1.2;
            margin-top: 8px;
            margin-bottom: 8px;
        }
        
        .center {
            text-align: center;
        }
        
        .bold {
            font-weight: bold;
        }
        
        .uppercase {
            text-transform: uppercase;
        }
        
        .divider {
            border-top: 1px dashed #999;
            margin: 8px 0;
        }
        
        .dotted-divider {
            border-top: 1px dotted #999;
            margin: 6px 0;
        }
        
        .flex-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .store-name {
            font-size: 10px;
            margin-bottom: 4px;
            color: #666;
        }
        
        .receipt-title {
            font-size: 14px;
            margin: 6px 0;
        }
        
        .meta-info {
            font-size: 9px;
            margin: 6px 0;
        }
        
        .line-item {
            margin: 2px 0;
        }
        
        .item-description {
            margin-bottom: 1px;
        }
        
        .item-details {
            font-size: 9px;
            color: #666;
            padding-left: 8px;
        }
        
        .subtotal-section {
            margin-top: 8px;
        }
        
        .total-section {
            margin-top: 6px;
            font-size: 12px;
        }
        
        .footer-message {
            margin: 12px 0 8px 0;
            font-size: 10px;
        }
        
        .barcode-section {
            margin: 8px 0;
        }
        
        .barcode-placeholder {
            height: 30px;
            background: repeating-linear-gradient(
                90deg,
                #000 0px,
                #000 1px,
                transparent 1px,
                transparent 2px
            );
            margin: 0 auto;
            width: 80%;
        }
        
        .credit {
            font-size: 8px;
            color: #999;
            margin-top: 8px;
        }
        
        /* Handle HTML formatting in address and other fields */
        .thermal-receipt-container b,
        .thermal-receipt-container strong {
            font-weight: bold;
        }
        
        .thermal-receipt-container br {
            display: block;
            margin: 2px 0;
            content: "";
        }
        
        .thermal-receipt-container p {
            margin: 2px 0;
        }
        
        .negative {
            color: #666;
        }
        
        .right-align {
            text-align: right;
        }
        
        .dots {
            flex: 1;
            border-bottom: 1px dotted #999;
            margin: 0 4px;
            height: 1px;
            align-self: flex-end;
        }
        
        @media print {
            .thermal-receipt-container {
                background: none;
                padding: 0;
                min-height: auto;
            }
            
            .thermal-receipt {
                width: 100%;
                max-width: none;
                box-shadow: none;
                border-radius: 0;
            }
            
            .thermal-receipt::before,
            .thermal-receipt::after {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="thermal-receipt-container">
    <div class="thermal-receipt">
        <div class="receipt-content">
            
            {{-- Header Section --}}
            @if(empty($receipt_details->letter_head))
                @if(!empty($receipt_details->logo))
                    <div class="center" style="margin-bottom: 8px;">
                        <img style="max-height: 60px; width: auto;" src="{{$receipt_details->logo}}" alt="Logo">
                    </div>
                @endif

                @if(!empty($receipt_details->header_text))
                    <div class="center" style="margin-bottom: 6px;">
                        {!! $receipt_details->header_text !!}
                    </div>
                @endif

                <div class="store-name center uppercase">
                    @if(!empty($receipt_details->display_name))
                        {{$receipt_details->display_name}}
                    @endif
                </div>

                @if(!empty($receipt_details->address))
                    <div class="center" style="font-size: 9px; margin: 4px 0; color: #666;">
                        {!! html_entity_decode($receipt_details->address, ENT_QUOTES, 'UTF-8') !!}
                    </div>
                @endif

                @if(!empty($receipt_details->contact) || !empty($receipt_details->website))
                    <div class="center" style="font-size: 9px; margin: 4px 0; color: #666;">
                        @if(!empty($receipt_details->contact))
                            {{$receipt_details->contact}}
                        @endif
                        @if(!empty($receipt_details->contact) && !empty($receipt_details->website))
                            |
                        @endif
                        @if(!empty($receipt_details->website))
                            {{$receipt_details->website}}
                        @endif
                    </div>
                @endif

                @if(!empty($receipt_details->sub_heading_line1) || !empty($receipt_details->sub_heading_line2))
                    <div class="center" style="font-size: 9px; margin: 4px 0; color: #666;">
                        @if(!empty($receipt_details->sub_heading_line1))
                            {{$receipt_details->sub_heading_line1}}<br>
                        @endif
                        @if(!empty($receipt_details->sub_heading_line2))
                            {{$receipt_details->sub_heading_line2}}
                        @endif
                    </div>
                @endif
            @else
                <div class="center">
                    <img style="max-width: 100%;" src="{{$receipt_details->letter_head}}" alt="Letter Head">
                </div>
            @endif
            
            <div class="dotted-divider"></div>
            
            <div class="receipt-title center bold uppercase">
                @if(!empty($receipt_details->invoice_heading))
                    {{$receipt_details->invoice_heading}}
                @else
                    *** RECEIPT ***
                @endif
            </div>
            
            <div class="meta-info flex-row uppercase">
                <span>
                    @if(!empty($receipt_details->sales_person))
                        @if(!empty($receipt_details->sales_person_label))
                            {{$receipt_details->sales_person_label}}: {{$receipt_details->sales_person}}
                        @else
                            CASHIER: {{$receipt_details->sales_person}}
                        @endif
                    @else
                        CASHIER: #1
                    @endif
                </span>
                <span>
                    @if(!empty($receipt_details->date_label))
                        {{$receipt_details->date_label}}:
                    @endif
                    {{@format_datetime($receipt_details->transaction_date)}}
                </span>
            </div>

            @if(!empty($receipt_details->customer_name))
                <div class="flex-row" style="margin: 4px 0; font-size: 9px;">
                    <span class="uppercase">
                        @if(!empty($receipt_details->customer_label))
                            {{$receipt_details->customer_label}}:
                        @else
                            CUSTOMER:
                        @endif
                    </span>
                    <span>{{$receipt_details->customer_name}}</span>
                </div>
            @endif

            @if(!empty($receipt_details->invoice_no))
                <div class="flex-row" style="margin: 4px 0; font-size: 9px;">
                    <span class="uppercase">INVOICE NO:</span>
                    <span>{{$receipt_details->invoice_no}}</span>
                </div>
            @endif
            
            <div class="dotted-divider"></div>
            
            {{-- Line Items Block --}}
            @php
                $canShowLineItems = !empty($receipt_details->lines) && is_array($receipt_details->lines);
                $hasValidLines = false;
                if ($canShowLineItems) {
                    foreach ($receipt_details->lines as $testLine) {
                        if (!empty($testLine) && is_array($testLine)) {
                            $hasValidLines = true;
                            break;
                        }
                    }
                }
            @endphp
            
            @if($canShowLineItems && $hasValidLines)
                @foreach($receipt_details->lines as $line)
                    @php
                        // Skip if line is null or not array
                        if (empty($line) || !is_array($line)) {
                            continue;
                        }
                        
                        // Extract all values safely in one place with try-catch
                        $lineName = '';
                        $lineTotal = '';
                        $quantity = 1;
                        $unitPriceIncTax = '';
                        $unitPriceExcTax = '';
                        $lineDiscountAmount = 0;
                        $lineDiscountPercent = 0;
                        
                        try {
                            $lineName = array_key_exists('name', $line) && !is_null($line['name']) ? strval($line['name']) : '';
                            $lineTotal = array_key_exists('line_total', $line) && !is_null($line['line_total']) ? strval($line['line_total']) : '';
                            $quantity = array_key_exists('quantity', $line) && !is_null($line['quantity']) && is_numeric($line['quantity']) ? floatval($line['quantity']) : 1;
                            $unitPriceIncTax = array_key_exists('unit_price_inc_tax', $line) && !is_null($line['unit_price_inc_tax']) ? strval($line['unit_price_inc_tax']) : '';
                            $unitPriceExcTax = array_key_exists('unit_price_exc_tax', $line) && !is_null($line['unit_price_exc_tax']) ? strval($line['unit_price_exc_tax']) : '';
                            $lineDiscountAmount = array_key_exists('line_discount_amount', $line) && !is_null($line['line_discount_amount']) && is_numeric($line['line_discount_amount']) ? floatval($line['line_discount_amount']) : 0;
                            $lineDiscountPercent = array_key_exists('line_discount_percent', $line) && !is_null($line['line_discount_percent']) && is_numeric($line['line_discount_percent']) ? floatval($line['line_discount_percent']) : 0;
                        } catch (Exception $e) {
                            // Skip this line if extraction fails
                            continue;
                        }
                        
                        $showQuantityDetails = $quantity != 1 || !empty($unitPriceExcTax);
                        $showDiscount = !empty($lineDiscountAmount) && $lineDiscountAmount != 0;
                    @endphp
                    
                    <div class="line-item">
                        <div class="item-description flex-row uppercase">
                            <span>{{$lineName}}</span>
                            <span class="dots"></span>
                            <span>{{$lineTotal}}</span>
                        </div>
                        
                        @if($showQuantityDetails)
                            <div class="item-details">
                                @if($quantity != 1)
                                    @php
                                        $formattedQuantity = $quantity;
                                        if (function_exists('format_quantity')) {
                                            try {
                                                $formattedQuantity = format_quantity($quantity);
                                            } catch (Exception $e) {
                                                $formattedQuantity = number_format($quantity, 2);
                                            }
                                        } else {
                                            $formattedQuantity = number_format($quantity, 2);
                                        }
                                    @endphp
                                    x{{$formattedQuantity}} @ {{$unitPriceIncTax}}
                                @endif
                            </div>
                        @endif
                        
                        @if($showDiscount)
                            <div class="item-details negative">
                                DISC. {{$lineDiscountPercent}}% @ {{$unitPriceIncTax}}
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                {{-- Fallback when line items can't be displayed --}}
                <div class="line-item">
                    <div class="item-description flex-row uppercase">
                        <span>Items details unavailable</span>
                        <span class="dots"></span>
                        <span></span>
                    </div>
                </div>
            @endif
            
            <div class="dotted-divider"></div>
            
            {{-- Subtotals & Discounts --}}
            <div class="subtotal-section">
                <div class="flex-row uppercase">
                    <span>SUBTOTAL</span>
                    <span>{{$receipt_details->subtotal}}</span>
                </div>
                
                @if(!empty($receipt_details->total_line_discount))
                    <div class="flex-row uppercase">
                        <span>DISCOUNT</span>
                        <span class="negative">-{{$receipt_details->total_line_discount}}</span>
                    </div>
                @endif
                
                @if(!empty($receipt_details->tax_label_1))
                    <div class="flex-row uppercase">
                        <span>{{$receipt_details->tax_label_1}}</span>
                        <span>{{$receipt_details->total_tax}}</span>
                    </div>
                @endif
                
                @if(!empty($receipt_details->shipping_charges))
                    <div class="flex-row uppercase">
                        <span>SHIPPING</span>
                        <span>{{$receipt_details->shipping_charges}}</span>
                    </div>
                @endif
                
                @if(!empty($receipt_details->packing_charge))
                    <div class="flex-row uppercase">
                        <span>PACKING</span>
                        <span>{{$receipt_details->packing_charge}}</span>
                    </div>
                @endif
                
                @if(!empty($receipt_details->plasticbag_charges))
                    <div class="flex-row uppercase">
                        <span>PLASTIC BAGS</span>
                        <span>{{$receipt_details->plasticbag_charges}}</span>
                    </div>
                @endif
            </div>
            
            <div class="divider"></div>
            
            {{-- Grand Total Block --}}
            <div class="total-section">
                <div class="flex-row bold uppercase">
                    <span>TOTAL AMOUNT</span>
                    <span>{{$receipt_details->total}}</span>
                </div>
                
                @if(!empty($receipt_details->payments))
                    @foreach($receipt_details->payments as $payment)
                        <div class="flex-row uppercase">
                            <span>{{isset($payment['method']) ? $payment['method'] : ''}}</span>
                            <span>{{isset($payment['amount']) ? $payment['amount'] : ''}}</span>
                        </div>
                    @endforeach
                @endif
                
                @if(!empty($receipt_details->change_return))
                    <div class="flex-row uppercase">
                        <span>CHANGE</span>
                        <span>{{$receipt_details->change_return}}</span>
                    </div>
                @endif
            </div>
            
            <div class="divider"></div>
            
            {{-- Footer --}}
            @if(!empty($receipt_details->footer_text))
                <div class="footer-message center uppercase bold">
                    {!! $receipt_details->footer_text !!}
                </div>
                <div class="dotted-divider"></div>
            @endif
            
            {{-- Barcode Section --}}
            @if(!empty($receipt_details->show_barcode) && !empty($receipt_details->invoice_no))
                <div class="barcode-section center">
                    <div class="barcode-placeholder"></div>
                    <div style="font-size: 8px; margin-top: 4px;">{{$receipt_details->invoice_no}}</div>
                </div>
            @endif
            
        </div>
    </div>
    </div>
</body>
</html>