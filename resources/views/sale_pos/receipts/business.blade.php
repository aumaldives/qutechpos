<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{$receipt_details->invoice_heading ?? 'Invoice'}}-{{$receipt_details->invoice_no}}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, {{ $receipt_details->highlight_color ?? '#2c3e50' }}, {{ $receipt_details->highlight_color ?? '#34495e' }});
            color: white;
            padding: 30px;
            position: relative;
        }
        
        .invoice-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(45deg, transparent 49%, white 50%, transparent 51%);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
        }
        
        .business-info {
            flex: 1;
        }
        
        .business-logo {
            max-height: 80px;
            margin-bottom: 15px;
        }
        
        .business-name {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .business-address {
            font-size: 14px;
            line-height: 1.5;
            opacity: 0.9;
        }
        
        .invoice-meta {
            text-align: right;
            min-width: 250px;
        }
        
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .invoice-details {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
        
        .invoice-details table {
            width: 100%;
        }
        
        .invoice-details td {
            padding: 3px 0;
            color: white;
        }
        
        .invoice-details .label {
            font-weight: bold;
            opacity: 0.9;
        }
        
        .content-section {
            padding: 30px;
        }
        
        .section-header {
            background: #f8f9fa;
            padding: 12px 20px;
            margin: 20px -30px 20px -30px;
            border-left: 4px solid {{ $receipt_details->highlight_color ?? '#2c3e50' }};
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            color: #2c3e50;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-block h4 {
            color: {{ $receipt_details->highlight_color ?? '#2c3e50' }};
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .info-block p {
            margin-bottom: 4px;
            line-height: 1.5;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .items-table thead {
            background: {{ $receipt_details->highlight_color ?? '#2c3e50' }};
            color: white;
        }
        
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .items-table th {
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .items-table tbody tr:hover {
            background: #e3f2fd;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .product-image {
            max-width: 40px;
            max-height: 40px;
            border-radius: 4px;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .product-details {
            font-size: 11px;
            color: #6c757d;
            margin-top: 4px;
        }
        
        .line-notes {
            font-style: italic;
            color: #6c757d;
            margin-top: 4px;
            font-size: 11px;
        }
        
        .totals-section {
            background: #f8f9fa;
            padding: 30px;
            margin: 0 -30px;
            border-top: 2px solid #e9ecef;
        }
        
        .totals-grid {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
        }
        
        .payment-info h4,
        .totals h4 {
            color: {{ $receipt_details->highlight_color ?? '#2c3e50' }};
            margin-bottom: 15px;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .totals-table {
            width: 100%;
        }
        
        .totals-table td {
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .totals-table .total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid {{ $receipt_details->highlight_color ?? '#2c3e50' }};
            color: {{ $receipt_details->highlight_color ?? '#2c3e50' }};
        }
        
        .payment-method {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .footer-section {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-notes {
            margin-bottom: 20px;
            color: #6c757d;
            line-height: 1.6;
        }
        
        .barcode-section {
            margin: 20px 0;
        }
        
        .tax-summary {
            margin: 20px 0;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .tax-summary-header {
            background: {{ $receipt_details->highlight_color ?? '#2c3e50' }};
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
        }
        
        .tax-summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tax-summary-row:last-child {
            border-bottom: none;
        }
        
        .plastic-bag-details {
            margin: 10px 0;
        }
        
        .plastic-bag-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 11px;
            color: #6c757d;
        }
        
        .repair-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .repair-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .checklist-section {
            margin: 15px 0;
        }
        
        .checklist-item {
            display: flex;
            align-items: center;
            margin: 5px 0;
            font-size: 11px;
        }
        
        .checklist-checkbox {
            width: 12px;
            height: 12px;
            border: 1px solid #666;
            margin-right: 8px;
            display: inline-block;
            position: relative;
        }
        
        .checklist-checkbox.checked::after {
            content: '✓';
            position: absolute;
            left: 1px;
            top: -2px;
            font-size: 10px;
            color: green;
        }
        
        .defects-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
        }
        
        .defect-item {
            margin: 3px 0;
            font-size: 11px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                border-radius: 0;
                max-width: none;
            }
            
            .invoice-header::after {
                display: none;
            }
            
            .section-header {
                margin-left: 0;
                margin-right: 0;
            }
            
            .totals-section {
                margin-left: 0;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        {{-- Header Section --}}
        <div class="invoice-header">
            <div class="header-content">
                <div class="business-info">
                    @if(!empty($receipt_details->letter_head))
                        <img src="{{$receipt_details->letter_head}}" alt="Letter Head" style="max-width: 100%; height: auto;">
                    @else
                        @if(!empty($receipt_details->logo))
                            <img src="{{$receipt_details->logo}}" alt="Logo" class="business-logo">
                        @endif
                        
                        @if(!empty($receipt_details->header_text))
                            <div style="margin-bottom: 15px;">
                                {!! $receipt_details->header_text !!}
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->display_name))
                            <div class="business-name">{{$receipt_details->display_name}}</div>
                        @endif
                        
                        <div class="business-address">
                            @if(!empty($receipt_details->address))
                                {!! html_entity_decode($receipt_details->address, ENT_QUOTES, 'UTF-8') !!}
                            @endif
                            
                            @if(!empty($receipt_details->contact))
                                <br>{{$receipt_details->contact}}
                            @endif
                            
                            @if(!empty($receipt_details->website))
                                <br>{{$receipt_details->website}}
                            @endif
                            
                            @if(!empty($receipt_details->location_custom_fields))
                                <br>{{$receipt_details->location_custom_fields}}
                            @endif
                        </div>
                        
                        @if(!empty($receipt_details->sub_heading_line1) || !empty($receipt_details->sub_heading_line2) || !empty($receipt_details->sub_heading_line3) || !empty($receipt_details->sub_heading_line4) || !empty($receipt_details->sub_heading_line5))
                            <div style="margin-top: 10px; font-size: 13px;">
                                @if(!empty($receipt_details->sub_heading_line1))
                                    {{$receipt_details->sub_heading_line1}}<br>
                                @endif
                                @if(!empty($receipt_details->sub_heading_line2))
                                    {{$receipt_details->sub_heading_line2}}<br>
                                @endif
                                @if(!empty($receipt_details->sub_heading_line3))
                                    {{$receipt_details->sub_heading_line3}}<br>
                                @endif
                                @if(!empty($receipt_details->sub_heading_line4))
                                    {{$receipt_details->sub_heading_line4}}<br>
                                @endif
                                @if(!empty($receipt_details->sub_heading_line5))
                                    {{$receipt_details->sub_heading_line5}}
                                @endif
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->tax_info1) || !empty($receipt_details->tax_info2))
                            <div style="margin-top: 10px; font-size: 12px;">
                                @if(!empty($receipt_details->tax_info1))
                                    <strong>{{$receipt_details->tax_label1}}:</strong> {{$receipt_details->tax_info1}}<br>
                                @endif
                                @if(!empty($receipt_details->tax_info2))
                                    <strong>{{$receipt_details->tax_label2}}:</strong> {{$receipt_details->tax_info2}}
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
                
                <div class="invoice-meta">
                    <div class="invoice-title">
                        @if(!empty($receipt_details->invoice_heading))
                            {{$receipt_details->invoice_heading}}
                        @else
                            Invoice
                        @endif
                    </div>
                    
                    <div class="invoice-details">
                        <table>
                            @if(!empty($receipt_details->invoice_no))
                                <tr>
                                    <td class="label">
                                        @if(!empty($receipt_details->invoice_no_prefix))
                                            {!! $receipt_details->invoice_no_prefix !!}
                                        @else
                                            Invoice No:
                                        @endif
                                    </td>
                                    <td>{{$receipt_details->invoice_no}}</td>
                                </tr>
                            @endif
                            
                            <tr>
                                <td class="label">
                                    @if(!empty($receipt_details->date_label))
                                        {{$receipt_details->date_label}}:
                                    @else
                                        Date:
                                    @endif
                                </td>
                                <td>{{@format_datetime($receipt_details->transaction_date)}}</td>
                            </tr>
                            
                            @if(!empty($receipt_details->due_date))
                                <tr>
                                    <td class="label">
                                        @if(!empty($receipt_details->due_date_label))
                                            {{$receipt_details->due_date_label}}:
                                        @else
                                            Due Date:
                                        @endif
                                    </td>
                                    <td>{{$receipt_details->due_date}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->porder_no))
                                <tr>
                                    <td class="label">PO Number:</td>
                                    <td>{{$receipt_details->porder_no}}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Main Content --}}
        <div class="content-section">
            {{-- Customer and Service Information --}}
            <div class="info-grid">
                <div class="info-block">
                    <h4>
                        @if(!empty($receipt_details->customer_label))
                            {{$receipt_details->customer_label}}
                        @else
                            Bill To
                        @endif
                    </h4>
                    
                    @if(!empty($receipt_details->customer_info))
                        {!! $receipt_details->customer_info !!}
                    @elseif(!empty($receipt_details->customer_name))
                        <p><strong>{{$receipt_details->customer_name}}</strong></p>
                    @endif
                    
                    @if(!empty($receipt_details->client_id))
                        <p>
                            @if(!empty($receipt_details->client_id_label))
                                <strong>{{$receipt_details->client_id_label}}:</strong>
                            @else
                                <strong>Client ID:</strong>
                            @endif
                            {{$receipt_details->client_id}}
                        </p>
                    @endif
                    
                    @if(!empty($receipt_details->customer_tax_number))
                        <p>
                            @if(!empty($receipt_details->customer_tax_label))
                                <strong>{{$receipt_details->customer_tax_label}}:</strong>
                            @else
                                <strong>Tax Number:</strong>
                            @endif
                            {{$receipt_details->customer_tax_number}}
                        </p>
                    @endif
                    
                    @if(!empty($receipt_details->customer_custom_fields))
                        {!! $receipt_details->customer_custom_fields !!}
                    @endif
                    
                    @if(!empty($receipt_details->customer_total_rp))
                        <p>
                            @if(!empty($receipt_details->customer_rp_label))
                                <strong>{{$receipt_details->customer_rp_label}}:</strong>
                            @else
                                <strong>Reward Points:</strong>
                            @endif
                            {{$receipt_details->customer_total_rp}}
                        </p>
                    @endif
                </div>
                
                <div class="info-block">
                    <h4>Service Details</h4>
                    
                    @if(!empty($receipt_details->sales_person))
                        <p>
                            @if(!empty($receipt_details->sales_person_label))
                                <strong>{{$receipt_details->sales_person_label}}:</strong>
                            @else
                                <strong>Sales Person:</strong>
                            @endif
                            {{$receipt_details->sales_person}}
                        </p>
                    @endif
                    
                    @if(!empty($receipt_details->commission_agent))
                        <p>
                            @if(!empty($receipt_details->commission_agent_label))
                                <strong>{{$receipt_details->commission_agent_label}}:</strong>
                            @else
                                <strong>Commission Agent:</strong>
                            @endif
                            {{$receipt_details->commission_agent}}
                        </p>
                    @endif
                    
                    @if(!empty($receipt_details->service_staff))
                        <p>
                            @if(!empty($receipt_details->service_staff_label))
                                <strong>{{$receipt_details->service_staff_label}}:</strong>
                            @else
                                <strong>Service Staff:</strong>
                            @endif
                            {{$receipt_details->service_staff}}
                        </p>
                    @endif
                    
                    @if(!empty($receipt_details->types_of_service))
                        <p>
                            @if(!empty($receipt_details->types_of_service_label))
                                <strong>{{$receipt_details->types_of_service_label}}:</strong>
                            @else
                                <strong>Service Type:</strong>
                            @endif
                            {{$receipt_details->types_of_service}}
                        </p>
                        
                        @if(!empty($receipt_details->types_of_service_custom_fields))
                            @foreach($receipt_details->types_of_service_custom_fields as $key => $value)
                                <p><strong>{{$key}}:</strong> {{$value}}</p>
                            @endforeach
                        @endif
                    @endif
                    
                    @if(!empty($receipt_details->table))
                        <p>
                            @if(!empty($receipt_details->table_label))
                                <strong>{{$receipt_details->table_label}}:</strong>
                            @else
                                <strong>Table:</strong>
                            @endif
                            {{$receipt_details->table}}
                        </p>
                    @endif
                    
                    {{-- Shipping Custom Fields --}}
                    @if(!empty($receipt_details->shipping_custom_field_1_value))
                        <p><strong>{{$receipt_details->shipping_custom_field_1_label}}:</strong> {{$receipt_details->shipping_custom_field_1_value}}</p>
                    @endif
                    @if(!empty($receipt_details->shipping_custom_field_2_value))
                        <p><strong>{{$receipt_details->shipping_custom_field_2_label}}:</strong> {{$receipt_details->shipping_custom_field_2_value}}</p>
                    @endif
                    @if(!empty($receipt_details->shipping_custom_field_3_value))
                        <p><strong>{{$receipt_details->shipping_custom_field_3_label}}:</strong> {{$receipt_details->shipping_custom_field_3_value}}</p>
                    @endif
                    @if(!empty($receipt_details->shipping_custom_field_4_value))
                        <p><strong>{{$receipt_details->shipping_custom_field_4_label}}:</strong> {{$receipt_details->shipping_custom_field_4_value}}</p>
                    @endif
                    @if(!empty($receipt_details->shipping_custom_field_5_value))
                        <p><strong>{{$receipt_details->shipping_custom_field_5_label}}:</strong> {{$receipt_details->shipping_custom_field_5_value}}</p>
                    @endif
                    
                    {{-- Sale Orders --}}
                    @if(!empty($receipt_details->sale_orders_invoice_no))
                        <p><strong>@lang('restaurant.order_no'):</strong> {{$receipt_details->sale_orders_invoice_no}}</p>
                    @endif
                    @if(!empty($receipt_details->sale_orders_invoice_date))
                        <p><strong>@lang('lang_v1.order_dates'):</strong> {{$receipt_details->sale_orders_invoice_date}}</p>
                    @endif
                </div>
            </div>
            
            {{-- Repair Module Section --}}
            @if(!empty($receipt_details->repair_brand) || !empty($receipt_details->repair_device) || !empty($receipt_details->repair_model_no) || !empty($receipt_details->repair_serial_no) || !empty($receipt_details->repair_status) || !empty($receipt_details->repair_warranty))
                <div class="section-header">Device Information</div>
                <div class="repair-section">
                    <div class="repair-grid">
                        @if(!empty($receipt_details->repair_brand))
                            <div>
                                <strong>
                                    @if(!empty($receipt_details->brand_label))
                                        {{$receipt_details->brand_label}}:
                                    @else
                                        Brand:
                                    @endif
                                </strong>
                                <br>{{$receipt_details->repair_brand}}
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->repair_device))
                            <div>
                                <strong>
                                    @if(!empty($receipt_details->device_label))
                                        {{$receipt_details->device_label}}:
                                    @else
                                        Device:
                                    @endif
                                </strong>
                                <br>{{$receipt_details->repair_device}}
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->repair_model_no))
                            <div>
                                <strong>
                                    @if(!empty($receipt_details->model_no_label))
                                        {{$receipt_details->model_no_label}}:
                                    @else
                                        Model:
                                    @endif
                                </strong>
                                <br>{{$receipt_details->repair_model_no}}
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->repair_serial_no))
                            <div>
                                <strong>
                                    @if(!empty($receipt_details->serial_no_label))
                                        {{$receipt_details->serial_no_label}}:
                                    @else
                                        Serial No:
                                    @endif
                                </strong>
                                <br>{{$receipt_details->repair_serial_no}}
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->repair_status))
                            <div>
                                <strong>
                                    @if(!empty($receipt_details->repair_status_label))
                                        {{$receipt_details->repair_status_label}}:
                                    @else
                                        Status:
                                    @endif
                                </strong>
                                <br>{{$receipt_details->repair_status}}
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->repair_warranty))
                            <div>
                                <strong>
                                    @if(!empty($receipt_details->repair_warranty_label))
                                        {{$receipt_details->repair_warranty_label}}:
                                    @else
                                        Warranty:
                                    @endif
                                </strong>
                                <br>{{$receipt_details->repair_warranty}}
                            </div>
                        @endif
                    </div>
                    
                    {{-- Repair Checklist --}}
                    @if(!empty($receipt_details->repair_checklist))
                        <div class="checklist-section">
                            <h4>
                                @if(!empty($receipt_details->repair_checklist_label))
                                    {{$receipt_details->repair_checklist_label}}
                                @else
                                    Repair Checklist
                                @endif
                            </h4>
                            @php
                                $checklist = json_decode($receipt_details->repair_checklist, true);
                                $checked_items = !empty($receipt_details->checked_repair_checklist) ? json_decode($receipt_details->checked_repair_checklist, true) : [];
                            @endphp
                            @if(is_array($checklist))
                                @foreach($checklist as $index => $item)
                                    <div class="checklist-item">
                                        <span class="checklist-checkbox @if(in_array($index, $checked_items)) checked @endif"></span>
                                        {{$item}}
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endif
                    
                    {{-- Repair Defects --}}
                    @if(!empty($receipt_details->repair_defects))
                        <div>
                            <h4>
                                @if(!empty($receipt_details->defects_label))
                                    {{$receipt_details->defects_label}}
                                @else
                                    Reported Defects
                                @endif
                            </h4>
                            <div class="defects-list">
                                @php
                                    $defects = json_decode($receipt_details->repair_defects, true);
                                @endphp
                                @if(is_array($defects))
                                    @foreach($defects as $defect)
                                        <div class="defect-item">&bull; {{$defect}}</div>
                                    @endforeach
                                @else
                                    {{$receipt_details->repair_defects}}
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
            
            {{-- Line Items Section --}}
            @if(!empty($receipt_details->lines))
                <div class="section-header">Items</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">
                                @if(!empty($receipt_details->table_product_label))
                                    {{$receipt_details->table_product_label}}
                                @else
                                    Product
                                @endif
                            </th>
                            <th class="text-center" style="width: 10%;">
                                @if(!empty($receipt_details->table_qty_label))
                                    {{$receipt_details->table_qty_label}}
                                @else
                                    Qty
                                @endif
                            </th>
                            <th class="text-right" style="width: 15%;">
                                @if(!empty($receipt_details->table_unit_price_label))
                                    {{$receipt_details->table_unit_price_label}}
                                @else
                                    Unit Price
                                @endif
                            </th>
                            @if(!empty($receipt_details->discounted_unit_price_label))
                                <th class="text-right" style="width: 15%;">{{$receipt_details->discounted_unit_price_label}}</th>
                            @endif
                            @if(!empty($receipt_details->item_discount_label))
                                <th class="text-right" style="width: 10%;">{{$receipt_details->item_discount_label}}</th>
                            @endif
                            <th class="text-right" style="width: 15%;">
                                @if(!empty($receipt_details->table_subtotal_label))
                                    {{$receipt_details->table_subtotal_label}}
                                @else
                                    Total
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($receipt_details->lines as $line)
                            <tr>
                                <td>
                                    @if(!empty($line['image']))
                                        <img src="{{$line['image']}}" alt="Product" class="product-image">
                                    @endif
                                    
                                    <strong>{{$line['name']}}</strong>
                                    
                                    @if(!empty($line['product_variation']) || !empty($line['variation']))
                                        {{$line['product_variation']}} {{$line['variation']}}
                                    @endif
                                    
                                    <div class="product-details">
                                        @if(!empty($line['sub_sku']))
                                            SKU: {{$line['sub_sku']}}
                                        @endif
                                        @if(!empty($line['brand']))
                                            | Brand: {{$line['brand']}}
                                        @endif
                                        @if(!empty($line['cat_code']))
                                            | Code: {{$line['cat_code']}}
                                        @endif
                                        @if(!empty($line['product_custom_fields']))
                                            | {{$line['product_custom_fields']}}
                                        @endif
                                    </div>
                                    
                                    @if(!empty($line['product_description']))
                                        <div class="product-details">{!!$line['product_description']!!}</div>
                                    @endif
                                    
                                    @if(!empty($line['sell_line_note']))
                                        <div class="line-notes">{!!$line['sell_line_note']!!}</div>
                                    @endif
                                    
                                    @if(!empty($line['lot_number']))
                                        <div class="product-details">
                                            {{$line['lot_number_label'] ?? 'Lot'}}: {{$line['lot_number']}}
                                        </div>
                                    @endif
                                    
                                    @if(!empty($line['product_expiry']))
                                        <div class="product-details">
                                            {{$line['product_expiry_label'] ?? 'Expiry'}}: {{$line['product_expiry']}}
                                        </div>
                                    @endif
                                    
                                    @if(!empty($line['warranty_name']))
                                        <div class="product-details">
                                            Warranty: {{$line['warranty_name']}}
                                            @if(!empty($line['warranty_exp_date']))
                                                - {{@format_date($line['warranty_exp_date'])}}
                                            @endif
                                            @if(!empty($line['warranty_description']))
                                                | {{$line['warranty_description']}}
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                        <div class="product-details">
                                            1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}}
                                            <br>{{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    {{$line['quantity']}} {{$line['units']}}
                                    
                                    @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                        <div class="product-details">
                                            {{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-right">{{$line['unit_price_before_discount']}}</td>
                                @if(!empty($receipt_details->discounted_unit_price_label))
                                    <td class="text-right">{{$line['unit_price_inc_tax']}}</td>
                                @endif
                                @if(!empty($receipt_details->item_discount_label))
                                    <td class="text-right">
                                        {{$line['total_line_discount'] ?? '0.00'}}
                                        @if(!empty($line['line_discount_percent']))
                                            ({{$line['line_discount_percent']}}%)
                                        @endif
                                    </td>
                                @endif
                                <td class="text-right"><strong>{{$line['line_total']}}</strong></td>
                            </tr>
                            
                            {{-- Product Modifiers --}}
                            @if(!empty($line['modifiers']))
                                @foreach($line['modifiers'] as $modifier)
                                    <tr style="background: #f8f9fa;">
                                        <td style="padding-left: 30px;">
                                            <em>+ {{$modifier['name']}} {{$modifier['variation']}}</em>
                                            @if(!empty($modifier['sub_sku']))
                                                <span class="product-details">SKU: {{$modifier['sub_sku']}}</span>
                                            @endif
                                            @if(!empty($modifier['cat_code']))
                                                <span class="product-details">Code: {{$modifier['cat_code']}}</span>
                                            @endif
                                            @if(!empty($modifier['sell_line_note']))
                                                <div class="line-notes">({!!$modifier['sell_line_note']!!})</div>
                                            @endif
                                        </td>
                                        <td class="text-center">{{$modifier['quantity']}} {{$modifier['units']}}</td>
                                        <td class="text-right">{{$modifier['unit_price_inc_tax']}}</td>
                                        @if(!empty($receipt_details->discounted_unit_price_label))
                                            <td class="text-right">{{$modifier['unit_price_exc_tax']}}</td>
                                        @endif
                                        @if(!empty($receipt_details->item_discount_label))
                                            <td class="text-right">0.00</td>
                                        @endif
                                        <td class="text-right">{{$modifier['line_total']}}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            @endif
            
            {{-- Totals Section --}}
            <div class="totals-section">
                <div class="totals-grid">
                    <div class="payment-info">
                        @if(!empty($receipt_details->payments))
                            <h4>Payment Information</h4>
                            @foreach($receipt_details->payments as $payment)
                                <div class="payment-method">
                                    <span>{{$payment['method']}}</span>
                                    <span>{{$payment['amount']}}</span>
                                    <span>{{$payment['date']}}</span>
                                </div>
                            @endforeach
                            
                            @if(!empty($receipt_details->total_paid))
                                <div class="payment-method" style="font-weight: bold; border-top: 2px solid #333; margin-top: 10px; padding-top: 10px;">
                                    <span>{!! $receipt_details->total_paid_label ?? 'Total Paid' !!}</span>
                                    <span>{{$receipt_details->total_paid}}</span>
                                </div>
                            @endif
                        @endif
                        
                        {{-- Due amount should show even if no payments exist --}}
                        @if(!empty($receipt_details->total_due))
                            <div class="payment-method" style="color: #dc3545; font-weight: bold;">
                                <span>{!! $receipt_details->total_due_label ?? 'Total Due' !!}</span>
                                <span>{{$receipt_details->total_due}}</span>
                            </div>
                        @endif
                        
                        @if(!empty($receipt_details->all_due))
                            <div class="payment-method" style="color: #dc3545;">
                                <span>{!! $receipt_details->all_bal_label ?? 'All Balance Due' !!}</span>
                                <span>{{$receipt_details->all_due}}</span>
                            </div>
                        @endif
                    </div>
                    
                    <div class="totals">
                        <h4>Summary</h4>
                        <table class="totals-table">
                            @if(!empty($receipt_details->total_quantity))
                                <tr>
                                    <td><strong>{!! $receipt_details->total_quantity_label ?? 'Total Quantity' !!}</strong></td>
                                    <td class="text-right">{{$receipt_details->total_quantity}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->total_items))
                                <tr>
                                    <td><strong>{!! $receipt_details->total_items_label ?? 'Total Items' !!}</strong></td>
                                    <td class="text-right">{{$receipt_details->total_items}}</td>
                                </tr>
                            @endif
                            
                            <tr>
                                <td><strong>{!! $receipt_details->subtotal_label ?? 'Subtotal' !!}</strong></td>
                                <td class="text-right">{{$receipt_details->subtotal ?? $receipt_details->subtotal_exc_tax}}</td>
                            </tr>
                            
                            @if(!empty($receipt_details->total_exempt))
                                <tr>
                                    <td>@lang('lang_v1.exempt')</td>
                                    <td class="text-right">{{$receipt_details->total_exempt}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->shipping_charges))
                                <tr>
                                    <td>{!! $receipt_details->shipping_charges_label ?? 'Shipping' !!}</td>
                                    <td class="text-right">{{$receipt_details->shipping_charges}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->packing_charge))
                                <tr>
                                    <td>{!! $receipt_details->packing_charge_label ?? 'Packing' !!}</td>
                                    <td class="text-right">{{$receipt_details->packing_charge}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->plasticbag_charges))
                                @if(!empty($receipt_details->plastic_bag_details))
                                    <tr>
                                        <td colspan="2">
                                            <strong>{!! $receipt_details->plasticbag_charges_label ?? 'Plastic Bags' !!}</strong>
                                            <div class="plastic-bag-details">
                                                @foreach($receipt_details->plastic_bag_details as $detail)
                                                    <div class="plastic-bag-item">
                                                        <span>{{$detail['type']}} ({{$detail['quantity']}} x {{$detail['price']}})</span>
                                                        <span>{{$detail['total']}}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>{!! $receipt_details->plasticbag_charges_label ?? 'Plastic Bags' !!}</td>
                                        <td class="text-right">{{$receipt_details->plasticbag_charges}}</td>
                                    </tr>
                                @endif
                            @endif
                            
                            @if(!empty($receipt_details->discount))
                                <tr>
                                    <td>{!! $receipt_details->discount_label ?? 'Discount' !!}</td>
                                    <td class="text-right">(-) {{$receipt_details->discount}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->total_line_discount))
                                <tr>
                                    <td>{!! $receipt_details->line_discount_label ?? 'Line Discount' !!}</td>
                                    <td class="text-right">(-) {{$receipt_details->total_line_discount}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->additional_expenses))
                                @foreach($receipt_details->additional_expenses as $key => $val)
                                    <tr>
                                        <td>{{$key}}</td>
                                        <td class="text-right">(+) {{$val}}</td>
                                    </tr>
                                @endforeach
                            @endif
                            
                            @if(!empty($receipt_details->reward_point_amount))
                                <tr>
                                    <td>{!! $receipt_details->reward_point_label ?? 'Reward Points' !!}</td>
                                    <td class="text-right">(-) {{$receipt_details->reward_point_amount}}</td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->tax) || !empty($receipt_details->total_tax))
                                <tr>
                                    <td>{!! $receipt_details->tax_label ?? 'Tax' !!}</td>
                                    <td class="text-right">(+) {{$receipt_details->tax ?? $receipt_details->total_tax}}</td>
                                </tr>
                            @endif
                            
                            @if($receipt_details->round_off_amount > 0)
                                <tr>
                                    <td>{!! $receipt_details->round_off_label ?? 'Round Off' !!}</td>
                                    <td class="text-right">{{$receipt_details->round_off}}</td>
                                </tr>
                            @endif
                            
                            <tr class="total-row">
                                <td><strong>{!! $receipt_details->total_label ?? 'Grand Total' !!}</strong></td>
                                <td class="text-right"><strong>{{$receipt_details->total}}</strong></td>
                            </tr>
                            
                            @if(!empty($receipt_details->total_in_words))
                                <tr>
                                    <td colspan="2" style="font-style: italic; color: #666; border: none; padding-top: 8px;">
                                        ({{$receipt_details->total_in_words}})
                                    </td>
                                </tr>
                            @endif
                            
                            @if(!empty($receipt_details->change_return))
                                <tr style="color: #28a745; font-weight: bold;">
                                    <td>{!! $receipt_details->change_return_label ?? 'Change Return' !!}</td>
                                    <td class="text-right">{{$receipt_details->change_return}}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            
            {{-- Tax Summary --}}
            @if(!empty($receipt_details->taxes) && empty($receipt_details->hide_price) && empty($receipt_details->hide_invoice_tax))
                <div class="tax-summary">
                    <div class="tax-summary-header">
                        {{$receipt_details->tax_summary_label ?? 'Tax Summary'}}
                    </div>
                    @foreach($receipt_details->taxes as $key => $val)
                        <div class="tax-summary-row">
                            <span><strong>{{$key}}</strong></span>
                            <span>{{$val}}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            
            {{-- Additional Notes --}}
            @if(!empty($receipt_details->additional_notes))
                <div class="section-header">Additional Notes</div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    {!! nl2br($receipt_details->additional_notes) !!}
                </div>
            @endif
        </div>
        
        {{-- Footer Section --}}
        <div class="footer-section">
            @if(!empty($receipt_details->footer_text))
                <div class="footer-notes">
                    {!! $receipt_details->footer_text !!}
                </div>
            @endif
            
            @if($receipt_details->show_barcode || $receipt_details->show_qr_code)
                <div class="barcode-section">
                    @if($receipt_details->show_barcode && !empty($receipt_details->invoice_no))
                        <div style="margin: 10px 0;">
                            <img src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}" alt="Barcode">
                        </div>
                    @endif
                    
                    @if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
                        <div style="margin: 10px 0;">
                            <img src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}" alt="QR Code">
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</body>
</html>