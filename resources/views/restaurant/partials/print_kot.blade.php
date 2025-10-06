<!DOCTYPE html>
<html>
<head>
    <title>KOT - Order #{{ $order_data['invoice_no'] }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .order-info { margin-bottom: 15px; }
        .order-info table { width: 100%; }
        .order-info th { text-align: left; padding: 5px; border-bottom: 1px solid #ddd; }
        .order-info td { padding: 5px; border-bottom: 1px solid #ddd; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .items-table th { background-color: #f5f5f5; font-weight: bold; }
        .total-items { text-align: right; margin-top: 10px; font-weight: bold; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>KITCHEN ORDER TICKET (KOT)</h2>
        <h3>Order #{{ $order_data['invoice_no'] }}</h3>
        <p><strong>{{ @format_date($order_data['created_at']) }} {{ @format_time($order_data['created_at']) }}</strong></p>
    </div>

    <div class="order-info">
        <table>
            <tr>
                <th width="30%">Customer:</th>
                <td>{{ $order_data['customer_name'] }}</td>
            </tr>
            <tr>
                <th>Table:</th>
                <td>{{ $order_data['table_name'] }}</td>
            </tr>
            @if($order_data['waiter_name'] !== 'Unassigned')
            <tr>
                <th>Service Staff:</th>
                <td>{{ $order_data['waiter_name'] }}</td>
            </tr>
            @endif
            <tr>
                <th>Status:</th>
                <td>{{ ucfirst(str_replace('_', ' ', $order_data['order_status'])) }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="50%">Item</th>
                <th width="15%">Qty</th>
                <th width="10%">Unit</th>
                <th width="25%">Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order_data['items'] as $item)
            <tr>
                <td><strong>{{ $item['name'] }}</strong></td>
                <td>{{ number_format($item['quantity'], 2) }}</td>
                <td>{{ $item['unit'] }}</td>
                <td>{{ $item['notes'] ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-items">
        <p>Total Items: {{ $order_data['total_items'] }}</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>