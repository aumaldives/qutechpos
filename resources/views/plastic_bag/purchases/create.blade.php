@extends('layouts.app')
@section('title', __('Add Plastic Bag Purchase'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Add Plastic Bag Purchase')</h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li><a href="{{action([\App\Http\Controllers\PlasticBagController::class, 'purchases'])}}">@lang('Plastic Bag Purchases')</a></li>
        <li class="active">@lang('messages.add')</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Add Plastic Bag Purchase')])
        {!! Form::open(['url' => action([\App\Http\Controllers\PlasticBagController::class, 'storePurchase']), 'method' => 'post', 'id' => 'plastic_bag_purchase_form', 'enctype' => 'multipart/form-data']) !!}
        
        <div class="row">
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('invoice_number', 'Invoice Number' . ':*') !!}
                    {!! Form::text('invoice_number', null, ['class' => 'form-control', 'required', 'placeholder' => 'Invoice Number']); !!}
                </div>
            </div>
            
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('purchase_date', 'Purchase Date' . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('purchase_date', date('d/m/Y'), ['class' => 'form-control', 'required', 'readonly']); !!}
                    </div>
                </div>
            </div>
            
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('supplier_id', 'Supplier' . ':') !!}
                    {!! Form::select('supplier_id', $suppliers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width:100%']); !!}
                </div>
            </div>
            
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('invoice_file', 'Invoice File' . ':') !!}
                    {!! Form::file('invoice_file', ['class' => 'form-control', 'accept' => '.pdf,.jpg,.jpeg,.png']); !!}
                    <p class="help-block">@lang('PDF, JPG, JPEG, PNG files allowed')</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <h4>@lang('Plastic Bag Purchase Lines')</h4>
                <div class="table-responsive">
                    <table class="table table-condensed table-bordered table-striped" id="plastic_bag_purchase_table">
                        <thead>
                            <tr>
                                <th>@lang('Plastic Bag Type') *</th>
                                <th>@lang('Quantity') *</th>
                                <th>@lang('Price per Bag') *</th>
                                <th>@lang('Total')</th>
                                <th><i class="fa fa-trash" aria-hidden="true"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="purchase_line">
                                <td>
                                    {!! Form::select('plastic_bag_types[0][type_id]', $plastic_bag_types->pluck('name', 'id')->prepend('Please Select', ''), null, ['class' => 'form-control plastic_bag_type select2', 'required', 'style' => 'width:100%']) !!}
                                </td>
                                <td>
                                    {!! Form::text('plastic_bag_types[0][quantity]', null, ['class' => 'form-control input_number purchase_quantity', 'required']); !!}
                                </td>
                                <td>
                                    {!! Form::text('plastic_bag_types[0][price_per_bag]', null, ['class' => 'form-control input_number price_per_bag', 'required']); !!}
                                </td>
                                <td>
                                    <span class="line_total">0.00</span>
                                </td>
                                <td>
                                    <i class="fa fa-trash remove_purchase_line text-danger" title="Remove" style="cursor:pointer;"></i>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">@lang('sale.total_amount'):</th>
                                <th>
                                    <span id="total_purchase_amount">0.00</span>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="row">
                        <div class="col-sm-12">
                            <button type="button" class="btn btn-primary btn-xs" id="add_purchase_line">
                                @lang('Add Line') <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                    {!! Form::textarea('notes', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.notes'), 'rows' => 3]); !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <button type="submit" class="btn btn-primary pull-right" id="submit_purchase_btn">@lang( 'messages.save' )</button>
            </div>
        </div>

        {!! Form::close() !!}
    @endcomponent

</section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function(){
            var purchase_line_index = 0;
            
            //Date picker
            $('#purchase_date').datepicker({
                autoclose: true,
                format: datepicker_date_format
            });

            function get_purchase_line_row() {
                purchase_line_index++;
                var options = '';
                @foreach($plastic_bag_types as $type)
                    options += '<option value="{{$type->id}}">{{$type->name}}</option>';
                @endforeach

                var html = '<tr class="purchase_line">';
                html += '<td>';
                html += '<select class="form-control plastic_bag_type select2" name="plastic_bag_types[' + purchase_line_index + '][type_id]" required style="width:100%">';
                html += '<option value="">Please Select</option>';
                html += options;
                html += '</select>';
                html += '</td>';
                html += '<td>';
                html += '<input type="text" class="form-control input_number purchase_quantity" name="plastic_bag_types[' + purchase_line_index + '][quantity]" required>';
                html += '</td>';
                html += '<td>';
                html += '<input type="text" class="form-control input_number price_per_bag" name="plastic_bag_types[' + purchase_line_index + '][price_per_bag]" required>';
                html += '</td>';
                html += '<td>';
                html += '<span class="line_total">0.00</span>';
                html += '</td>';
                html += '<td>';
                html += '<i class="fa fa-trash remove_purchase_line text-danger" title="Remove" style="cursor:pointer;"></i>';
                html += '</td>';
                html += '</tr>';
                
                return $(html);
            }

            $(document).on('click', '#add_purchase_line', function() {
                var row = get_purchase_line_row();
                $('#plastic_bag_purchase_table tbody').append(row);
                row.find('.select2').select2();
            });

            $(document).on('click', '.remove_purchase_line', function() {
                $(this).closest('tr').remove();
                calculate_total();
            });

            function calculate_total() {
                var total = 0;
                $('#plastic_bag_purchase_table .purchase_line').each(function() {
                    var quantity = parseFloat($(this).find('.purchase_quantity').val()) || 0;
                    var price = parseFloat($(this).find('.price_per_bag').val()) || 0;
                    var line_total = quantity * price;
                    
                    $(this).find('.line_total').text(__number_f(line_total));
                    total += line_total;
                });
                
                $('#total_purchase_amount').text(__number_f(total));
            }

            $(document).on('input', '.purchase_quantity, .price_per_bag', function() {
                calculate_total();
            });

            $(document).on('submit', 'form#plastic_bag_purchase_form', function(e) {
                e.preventDefault();
                
                var form_data = new FormData(this);
                $('#submit_purchase_btn').attr('disabled', true);
                
                $.ajax({
                    method: "POST",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: form_data,
                    processData: false,
                    contentType: false,
                    success: function(result) {
                        if (result.success == 1) {
                            toastr.success(result.msg);
                            window.location = "{{action([\App\Http\Controllers\PlasticBagController::class, 'purchases'])}}";
                        } else {
                            toastr.error(result.msg);
                            $('#submit_purchase_btn').attr('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Error:', xhr.responseText);
                        if(xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            var errorMsg = '';
                            $.each(errors, function(key, value) {
                                errorMsg += value[0] + '<br>';
                            });
                            toastr.error(errorMsg);
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                        $('#submit_purchase_btn').attr('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection