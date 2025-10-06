<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('App\Http\Controllers\CashRegisterController@postCashAdjustment'), 'method' => 'post', 'id' => 'cash_adjustment_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('cash_register.cash_adjustment')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('adjustment_type', __('cash_register.adjustment_type') . ':*') !!}
                        {!! Form::select('adjustment_type', [
                            'add' => __('cash_register.add_cash'),
                            'remove' => __('cash_register.remove_cash')
                        ], null, [
                            'class' => 'form-control select2',
                            'required',
                            'placeholder' => __('messages.please_select')
                        ]) !!}
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('amount', __('sale.amount') . ':*') !!}
                        {!! Form::text('amount', null, [
                            'class' => 'form-control input_number',
                            'required',
                            'placeholder' => __('sale.amount')
                        ]) !!}
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('reason', __('cash_register.adjustment_reason') . ':*') !!}
                        {!! Form::textarea('reason', null, [
                            'class' => 'form-control',
                            'required',
                            'rows' => 3,
                            'placeholder' => __('cash_register.adjustment_reason_placeholder')
                        ]) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
$(document).ready(function(){
    $('.select2').select2();
    
    $('#cash_adjustment_form').submit(function(e){
        e.preventDefault();
        
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();
        
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(result){
                if(result.success == 1){
                    toastr.success(result.msg);
                    $('#cash_adjustment_modal').modal('hide');
                    
                    // Refresh the page or update cash register details
                    if(typeof refreshRegisterDetails === 'function'){
                        refreshRegisterDetails();
                    } else {
                        location.reload();
                    }
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(){
                toastr.error('@lang("messages.something_went_wrong")');
            }
        });
    });
});
</script>