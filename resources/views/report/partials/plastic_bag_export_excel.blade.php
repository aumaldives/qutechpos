<table>
    <tr>
        <td colspan="6"><strong>{{ $business->name }}</strong></td>
    </tr>
    <tr>
        <td colspan="6"><strong>Plastic Bag Report</strong></td>
    </tr>
    <tr>
        <td colspan="6">
            Date Range: 
            @if($start_date && $end_date)
                {{ date('M d, Y', strtotime($start_date)) }} to {{ date('M d, Y', strtotime($end_date)) }}
            @else
                All Time
            @endif
        </td>
    </tr>
    <tr>
        <td colspan="6">Generated On: {{ date('M d, Y H:i:s') }}</td>
    </tr>
    <tr>
        <td colspan="6">Total Bags Sold: {{ $total_bags }}</td>
    </tr>
    <tr>
        <td colspan="6">Total Revenue: ${{ number_format($total_revenue, 2) }}</td>
    </tr>
    <tr></tr>
    <tr>
        <th><strong>Date</strong></th>
        <th><strong>Invoice No</strong></th>
        <th><strong>Location</strong></th>
        <th><strong>Product</strong></th>
        <th><strong>Quantity</strong></th>
        <th><strong>Amount</strong></th>
    </tr>
    @foreach($data as $row)
    <tr>
        <td>{{ date('M d, Y', strtotime($row->transaction_date)) }}</td>
        <td>{{ $row->invoice_no }}</td>
        <td>{{ $row->location_name }}</td>
        <td>{{ $row->product_name }}</td>
        <td>{{ $row->quantity }}</td>
        <td>${{ number_format($row->line_total, 2) }}</td>
    </tr>
    @endforeach
    @if($data->count() > 0)
    <tr>
        <td colspan="4"><strong>Total</strong></td>
        <td><strong>{{ $data->sum('quantity') }}</strong></td>
        <td><strong>${{ number_format($data->sum('line_total'), 2) }}</strong></td>
    </tr>
    @endif
</table>