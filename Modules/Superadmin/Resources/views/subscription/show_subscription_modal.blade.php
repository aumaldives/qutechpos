<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header no-print">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">
        <i class="fa fa-file-invoice"></i> Subscription Invoice
      </h4>
    </div>

    <div class="modal-body invoice-content">
      <style>
        .invoice-content {
          font-family: 'Arial', sans-serif;
        }
        .invoice-header {
          border-bottom: 3px solid #007bff;
          margin-bottom: 20px;
          padding-bottom: 15px;
        }
        .invoice-title {
          font-size: 28px;
          font-weight: bold;
          color: #007bff;
          margin-bottom: 10px;
        }
        .company-info, .client-info {
          background: #f8f9fa;
          border-radius: 8px;
          padding: 20px;
          border-left: 4px solid #007bff;
        }
        .company-info h5, .client-info h5 {
          color: #007bff;
          font-weight: bold;
          margin-bottom: 15px;
          font-size: 16px;
        }
        .subscription-details {
          border: 1px solid #dee2e6;
          border-radius: 8px;
          overflow: hidden;
        }
        .subscription-details thead th {
          background: linear-gradient(135deg, #007bff, #0056b3);
          color: white;
          font-weight: 600;
          text-align: center;
          padding: 15px 10px;
          border: none;
        }
        .subscription-details tbody td {
          text-align: center;
          padding: 15px 10px;
          vertical-align: middle;
          font-weight: 500;
        }
        .subscription-details tbody tr:nth-child(even) {
          background-color: #f8f9fa;
        }
        .subscription-details tbody tr:hover {
          background-color: #e3f2fd;
        }
        .info-table {
          border: 1px solid #dee2e6;
          border-radius: 8px;
          overflow: hidden;
        }
        .info-table th {
          background: #f8f9fa;
          font-weight: 600;
          color: #495057;
          padding: 12px;
        }
        .info-table td {
          padding: 12px;
        }
        .info-table tr:nth-child(even) {
          background-color: #f8f9fa;
        }
        .amount-highlight {
          font-weight: bold;
          font-size: 16px;
          color: #007bff;
        }
        @media print {
          .invoice-content {
            font-size: 12px;
          }
          .invoice-title {
            font-size: 24px;
          }
        }
      </style>

      <div class="invoice-header text-center">
        <div class="invoice-title">SUBSCRIPTION INVOICE</div>
        <p class="text-muted">Invoice Date: {{ date('d/m/Y') }}</p>
      </div>
      <div class="row" style="margin-bottom: 30px;">
        <div class="col-md-6">
          <div class="company-info">
            <h5><i class="fa fa-building"></i> SERVICE PROVIDER</h5>
            <div><strong>{{$system["invoice_business_name"]}}</strong></div>
            <div><strong>TIN:</strong> {{$system["superadmin_business_tin"] ?? 'Not Set'}}</div>
            <div><strong>Email:</strong> {{$system["email"]}}</div>
            <div><strong>Address:</strong> {{$system["invoice_business_landmark"]}}</div>
            <div><strong>City:</strong> {{$system["invoice_business_city"]}}, <strong>Zip:</strong> {{$system["invoice_business_zip"]}}</div>
            <div><strong>State:</strong> {{$system["invoice_business_state"]}}, <strong>Country:</strong> {{$system["invoice_business_country"]}}</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="client-info">
            <h5><i class="fa fa-user"></i> BILL TO</h5>
            <div><strong>{{$subscription->business->name}}</strong></div>
            <div><strong>Tenant TIN:</strong> {{$subscription->business->tax_number_1 ?? 'Not Set'}}</div>
            @if(!empty($subscription->business->tax_number_2) && !empty($subscription->business->tax_label_2))
              <div><strong>{{$subscription->business->tax_label_2}}:</strong> {{$subscription->business->tax_number_2}}</div>
            @endif
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-12">
          <table class="table subscription-details">
            <thead>
              <tr>
                <th>Package</th>
                <th>Quantity</th>
                <th>Price (MVR)</th>
                <th>Tax Amount (MVR)</th>
                <th>Total (MVR)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>{{$subscription->package->name}}</strong></td>
                <td>1</td>
                @php
                  use Modules\Superadmin\Utils\CurrencyUtil;
                  $exchangeRate = CurrencyUtil::getUsdToMvrRate();
                  
                  // Try to get price from multiple sources
                  $packagePrice = floatval($subscription->package_price ?? 0);
                  
                  // If subscription package_price is 0, try to get from package
                  if ($packagePrice <= 0 && isset($subscription->package->price)) {
                      $packagePrice = floatval($subscription->package->price);
                  }
                  
                  $mvrAmount = $packagePrice * $exchangeRate;
                  
                  // Price is after tax, calculate tax amount using configured GST percentage
                  $gstPercentage = floatval($system["superadmin_gst_percentage"] ?? 8);
                  $taxRate = $gstPercentage / 100;
                  
                  if ($mvrAmount > 0) {
                      $priceBeforeTax = $mvrAmount / (1 + $taxRate);
                      $taxAmount = $mvrAmount - $priceBeforeTax;
                  } else {
                      $priceBeforeTax = 0;
                      $taxAmount = 0;
                  }
                @endphp
                <td><span class="amount-highlight">{{ number_format($priceBeforeTax, 2) }}</span></td>
                <td><span class="amount-highlight">{{ number_format($taxAmount, 2) }}</span></td>
                <td><span class="amount-highlight">{{ number_format($mvrAmount, 2) }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="row" style="margin-top: 30px;">
        <div class="col-md-12">
          <h5 style="color: #007bff; margin-bottom: 15px;">
            <i class="fa fa-info-circle"></i> Subscription Details
          </h5>
          <table class="table info-table">
            <tr>
              <th><i class="fa fa-calendar"></i> Created At</th>
              <td>{{@format_date($subscription->created_at)}}</td>
              <th><i class="fa fa-credit-card"></i> Transaction ID</th>
              <td>{{$subscription->payment_transaction_id ?: 'N/A'}}</td>
            </tr>
            <tr>
              <th><i class="fa fa-user"></i> Created By</th>
              <td>{{$subscription->created_user->user_full_name ?? 'System'}}</td>
              <th><i class="fa fa-payment"></i> Payment Method</th>
              <td><span class="badge badge-info">{{ $subscription->paid_via == 'offline' ? 'Bank Transfer' : ucfirst($subscription->paid_via) }}</span></td>
            </tr>
          </table>
        </div>
      </div>
    </div>

    <div class="modal-footer no-print">
      <button type="button" class="btn btn-primary" aria-label="Print" 
      onclick="$(this).closest('div.modal-content').printThis();"><i class="fa fa-print"></i> @lang( 'messages.print' )
      </button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<script type="text/javascript">
  $(document).ready(function(){
    __currency_convert_recursively($('.subscription-details'));
  })
</script>