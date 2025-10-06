<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('Plastic Bag Purchase Details')</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="col-md-6">
          <table class="table">
            <tr>
              <th>@lang('Invoice Number'):</th>
              <td>{{ $purchase->invoice_number }}</td>
            </tr>
            <tr>
              <th>@lang('Purchase Date'):</th>
              <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
              <th>@lang('Supplier'):</th>
              <td>{{ $purchase->supplier ? $purchase->supplier->name : '-' }}</td>
            </tr>
          </table>
        </div>
        <div class="col-md-6">
          <table class="table">
            <tr>
              <th>@lang('Created By'):</th>
              <td>{{ $purchase->createdBy->first_name }} {{ $purchase->createdBy->last_name }}</td>
            </tr>
            <tr>
              <th>@lang('Created At'):</th>
              <td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @if($purchase->invoice_file)
            <tr>
              <th>@lang('Invoice File'):</th>
              <td>
                <a href="{{ asset('storage/' . $purchase->invoice_file) }}" target="_blank" class="btn btn-xs btn-primary">
                  <i class="fa fa-download"></i> @lang('Download')
                </a>
              </td>
            </tr>
            @endif
          </table>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <h4>@lang('Purchase Lines')</h4>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>@lang('Plastic Bag Type')</th>
                  <th>@lang('Quantity')</th>
                  <th>@lang('Price per Bag')</th>
                  <th>@lang('Line Total')</th>
                </tr>
              </thead>
              <tbody>
                @foreach($purchase->purchaseLines as $line)
                <tr>
                  <td>{{ $line->plasticBagType->name }}</td>
                  <td>{{ number_format($line->quantity, 0) }}</td>
                  <td><span class="display_currency" data-currency_symbol="true">{{ $line->price_per_bag }}</span></td>
                  <td><span class="display_currency" data-currency_symbol="true">{{ $line->line_total }}</span></td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="3">@lang('Total Amount'):</th>
                  <th><span class="display_currency" data-currency_symbol="true">{{ $purchase->total_amount }}</span></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      @if($purchase->notes)
      <div class="row">
        <div class="col-md-12">
          <h4>@lang('Notes')</h4>
          <p>{{ $purchase->notes }}</p>
        </div>
      </div>
      @endif
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    </div>
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->