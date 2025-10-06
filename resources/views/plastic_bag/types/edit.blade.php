<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action([\App\Http\Controllers\PlasticBagController::class, 'updateType'], [$type->id]), 'method' => 'put', 'id' => 'plastic_bag_type_edit_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang('Edit Plastic Bag Type')</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('name', __('product.product_name') . ':*') !!}
        {!! Form::text('name', $type->name, ['class' => 'form-control', 'required', 'placeholder' => __('product.product_name')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('description', __('product.description') . ':') !!}
        {!! Form::textarea('description', $type->description, ['class' => 'form-control', 'placeholder' => __('product.description'), 'rows' => 3]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('price', __('sale.unit_price') . ':*') !!}
        {!! Form::text('price', @num_format($type->price), ['class' => 'form-control input_number', 'required', 'placeholder' => __('sale.unit_price')]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!}
        {!! Form::text('alert_quantity', @num_format($type->alert_quantity), ['class' => 'form-control input_number', 'placeholder' => __('product.alert_quantity')]); !!}
        <span class="help-block">@lang('product.alert_quantity_help')</span>
      </div>

      <div class="form-group">
        <div class="checkbox">
          <label>
            {!! Form::checkbox('is_active', 1, $type->is_active, [ 'class' => 'input-icheck']); !!} @lang('sale.is_active')
          </label>
        </div>
      </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.update' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->