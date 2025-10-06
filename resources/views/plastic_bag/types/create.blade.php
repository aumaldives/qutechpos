<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\PlasticBagController::class, 'storeType']), 'method' => 'post', 'id' => 'plastic_bag_type_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('Add Plastic Bag Type')</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('product.product_name') . ':*') !!}
        {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('product.product_name')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __('product.description') . ':') !!}
        {!! Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => __('product.description'), 'rows' => 3]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('price', __('sale.unit_price') . ':*') !!}
        {!! Form::text('price', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('sale.unit_price')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!}
        {!! Form::text('alert_quantity', null, ['class' => 'form-control input_number', 'placeholder' => __('product.alert_quantity')]); !!}
        <span class="help-block">@lang('product.alert_quantity_help')</span>
      </div>

      <div class="form-group">
        <div class="checkbox">
          <label>
            {!! Form::checkbox('is_active', 1, true, [ 'class' => 'input-icheck']); !!} @lang('sale.is_active')
          </label>
        </div>
      </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->