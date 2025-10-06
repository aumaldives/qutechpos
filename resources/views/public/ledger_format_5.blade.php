<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Due Invoices - {{ $contact->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
        .business-info { text-align: right; }
        .contact-info h3 { color: #337ab7; margin-bottom: 10px; }
        .filters { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .filters form { display: flex; gap: 15px; align-items: end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #337ab7; color: white; }
        .btn-primary:hover { background: #286090; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .table th { background: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .totals { background: #f9f9f9; font-weight: bold; }
        .due-amount { color: #d9534f; font-weight: bold; }
        .no-data { text-align: center; padding: 40px; color: #666; }
        .alert-info { background: #d9edf7; border: 1px solid #bce8f1; color: #31708f; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="contact-info">
            <h3>{{ $contact->name }}</h3>
            <div>
                {!! $contact->contact_address !!}
                @if(!empty($contact->email))
                    <br>Email: {{ $contact->email }}
                @endif
                <br>Mobile: {{ $contact->mobile }}
                @if(!empty($contact->tax_number))
                    <br>Tax No: {{ $contact->tax_number }}
                @endif
            </div>
        </div>
        <div class="business-info">
            <strong>{{ $business->name }}</strong>
            <br>{!! $business->business_address !!}
        </div>
    </div>

    <div class="filters">
        <form method="GET" action="">
            <div class="form-group">
                <label for="location_id">Business Location:</label>
                <select name="location_id" id="location_id" class="form-control">
                    @foreach($business_locations as $location_key => $location_name)
                        <option value="{{ $location_key }}" {{ $location_id == $location_key ? 'selected' : '' }}>
                            {{ $location_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>

    <div class="alert-info">
        <strong>Note:</strong> This report shows ALL due invoices (unpaid or partially paid) with no date filter applied.
    </div>

    <h2>Due Invoices Only</h2>

    @if($ledger_details['invoices']->count() > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice #</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Tax</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">Paid</th>
                    <th class="text-right">Due Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total_qty = 0;
                    $total_paid = 0;
                    $total_due = 0;
                @endphp
                @foreach($ledger_details['invoices'] as $invoice)
                    @php
                        // Get quantity for this invoice
                        $qty = DB::table('transaction_sell_lines')
                            ->where('transaction_id', $invoice->id)
                            ->sum('quantity');
                        $total_qty += $qty;
                        $total_paid += $invoice->paid_amount;
                        $total_due += $invoice->due_amount;
                    @endphp
                    <tr>
                        <td>{{ date('d/m/Y', strtotime($invoice->date)) }}</td>
                        <td>{{ $invoice->invoice_no }}</td>
                        <td class="text-right">{{ number_format($qty, 2) }}</td>
                        <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
                        <td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td>
                        <td class="text-right">{{ number_format($invoice->final_total, 2) }}</td>
                        <td class="text-right">{{ number_format($invoice->paid_amount, 2) }}</td>
                        <td class="text-right due-amount">{{ number_format($invoice->due_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals">
                    <td colspan="2"><strong>Totals:</strong></td>
                    <td class="text-right"><strong>{{ number_format($total_qty, 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($ledger_details['grand_subtotal'], 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($ledger_details['total_tax'], 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($ledger_details['final_total'], 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($total_paid, 2) }}</strong></td>
                    <td class="text-right due-amount"><strong>{{ number_format($total_due, 2) }}</strong></td>
                </tr>
                <tr class="totals">
                    <td colspan="7"><strong>Grand Subtotal:</strong></td>
                    <td class="text-right"><strong>{{ number_format($ledger_details['grand_subtotal'], 2) }}</strong></td>
                </tr>
                <tr class="totals">
                    <td colspan="7"><strong>Total Tax:</strong></td>
                    <td class="text-right"><strong>{{ number_format($ledger_details['total_tax'], 2) }}</strong></td>
                </tr>
                @if($ledger_details['plastic_bag_total'] > 0)
                    <tr class="totals">
                        <td colspan="7"><strong>Plastic Bag Fees Total:</strong></td>
                        <td class="text-right"><strong>{{ number_format($ledger_details['plastic_bag_total'], 2) }}</strong></td>
                    </tr>
                @endif
                <tr class="totals">
                    <td colspan="7"><strong>Final Total:</strong></td>
                    <td class="text-right"><strong>{{ number_format($ledger_details['final_total'], 2) }}</strong></td>
                </tr>
                <tr class="totals">
                    <td colspan="7"><strong>Total Due Amount:</strong></td>
                    <td class="text-right due-amount"><strong>{{ number_format($total_due, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="no-data">
            <p>No due invoices found. All invoices are fully paid!</p>
        </div>
    @endif

    <div style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
        Generated on {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html>