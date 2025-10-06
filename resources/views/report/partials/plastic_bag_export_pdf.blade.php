<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plastic Bag Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .report-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
        .date-range { margin-bottom: 5px; }
        .summary-box { border: 1px solid #ccc; padding: 10px; margin: 15px 0; }
        .summary-row { margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $business->name }}</div>
        <div class="report-title">Plastic Bag Report</div>
        <div class="date-range">
            Date Range: 
            @if($start_date && $end_date)
                {{ date('M d, Y', strtotime($start_date)) }} to {{ date('M d, Y', strtotime($end_date)) }}
            @else
                All Time
            @endif
        </div>
        <div>Generated On: {{ date('M d, Y H:i:s') }}</div>
    </div>

    <div class="summary-box">
        <div class="summary-row"><strong>Summary:</strong></div>
        <div class="summary-row">Total Bags Sold: {{ $data->sum('quantity') }}</div>
        <div class="summary-row">Total Revenue: ${{ number_format($data->sum('line_total'), 2) }}</div>
        <div class="summary-row">Total Transactions: {{ $data->count() }}</div>
        @if($location_name)
        <div class="summary-row">Location: {{ $location_name }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice No</th>
                <th>Location</th>
                <th>Product</th>
                <th>Quantity</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
            <tr>
                <td>{{ date('M d, Y', strtotime($row->transaction_date)) }}</td>
                <td>{{ $row->invoice_no }}</td>
                <td>{{ $row->location_name }}</td>
                <td>{{ $row->product_name }}</td>
                <td>{{ $row->quantity }}</td>
                <td class="text-right">${{ number_format($row->line_total, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">No plastic bag sales found</td>
            </tr>
            @endforelse
            @if($data->count() > 0)
            <tr class="total-row">
                <td colspan="4"><strong>Total</strong></td>
                <td><strong>{{ $data->sum('quantity') }}</strong></td>
                <td class="text-right"><strong>${{ number_format($data->sum('line_total'), 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>
</body>
</html>