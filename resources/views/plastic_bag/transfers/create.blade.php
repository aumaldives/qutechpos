<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\PlasticBagController::class, 'storeTransfer']), 'method' => 'post', 'id' => 'plastic_bag_transfer_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('Add Stock Transfer')</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('plastic_bag_type_id', __('Plastic Bag Type') . ':*') !!}
        {!! Form::select('plastic_bag_type_id', $plastic_bag_types->pluck('name', 'id')->prepend('Please Select', ''), null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%', 'id' => 'plastic_bag_type_select']) !!}
        <small class="text-muted" id="stock_info"></small>
      </div>

      <div class="form-group">
        {!! Form::label('from_location_id', __('From Location') . ':*') !!}
        {!! Form::select('from_location_id', $locations->pluck('name', 'id')->prepend('Please Select', ''), null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']) !!}
      </div>

      <div class="form-group">
        {!! Form::label('to_location_id', __('To Location') . ':*') !!}
        {!! Form::select('to_location_id', $locations->pluck('name', 'id')->prepend('Please Select', ''), null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']) !!}
      </div>

      <div class="form-group">
        {!! Form::label('quantity', __('Quantity') . ':*') !!}
        {!! Form::text('quantity', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('Quantity')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('transfer_date', __('Transfer Date') . ':*') !!}
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-calendar"></i>
          </span>
          {!! Form::text('transfer_date', date('d/m/Y'), ['class' => 'form-control', 'required', 'readonly']); !!}
        </div>
      </div>

      <div class="form-group">
        {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
        {!! Form::textarea('notes', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.notes'), 'rows' => 3]); !!}
      </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
    $(document).ready(function(){
        $('.select2').select2();
        
        // Date picker
        $('#transfer_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });

        // Show stock info when plastic bag type is selected
        $('#plastic_bag_type_select').on('change', function() {
            var typeId = $(this).val();
            if (typeId) {
                $.ajax({
                    url: '/plastic-bag/get-plastic-bag-stock/' + typeId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            $('#stock_info').html('Available Stock: ' + result.stock_quantity + ' bags');
                        }
                    }
                });
            } else {
                $('#stock_info').html('');
            }
        });
    });
</script>