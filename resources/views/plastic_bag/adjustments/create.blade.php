<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\PlasticBagController::class, 'storeAdjustment']), 'method' => 'post', 'id' => 'plastic_bag_adjustment_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('Add Stock Adjustment')</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('plastic_bag_type_id', __('Plastic Bag Type') . ':*') !!}
        {!! Form::select('plastic_bag_type_id', $plastic_bag_types->pluck('name', 'id')->prepend('Please Select', ''), null, ['class' => 'form-control select2', 'required', 'style' => 'width:100%']) !!}
      </div>

      <div class="form-group">
        {!! Form::label('location_id', __('business.business_location') . ':') !!}
        {!! Form::select('location_id', $locations->pluck('name', 'id')->prepend('All Locations', ''), null, ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
      </div>

      <div class="form-group">
        {!! Form::label('adjustment_type', __('messages.type') . ':*') !!}
        {!! Form::select('adjustment_type', ['increase' => 'Increase', 'decrease' => 'Decrease'], null, ['class' => 'form-control', 'required', 'placeholder' => 'Please Select']) !!}
      </div>

      <div class="form-group">
        {!! Form::label('quantity', __('report.qty') . ':*') !!}
        {!! Form::text('quantity', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('report.qty')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('reason', __('lang_v1.reason') . ':*') !!}
        {!! Form::text('reason', null, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.reason')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('adjustment_date', __('messages.date') . ':*') !!}
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-calendar"></i>
          </span>
          {!! Form::text('adjustment_date', @format_date('now'), ['class' => 'form-control', 'required', 'readonly']); !!}
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
        
        //Date picker
        $('#adjustment_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });
    });
</script>