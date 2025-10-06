@extends('layouts.app')
@section('title', __('Plastic Bag Types'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Plastic Bag Types')
        <small>@lang('Manage plastic bag types')</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Plastic Bag Types')])
        @can('plastic_bag.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{action([\App\Http\Controllers\PlasticBagController::class, 'createType'])}}"
                        data-container=".plastic_bag_type_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')</button>
                </div>
            @endslot
        @endcan
        
        @can('plastic_bag.access')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="plastic_bag_types_table">
                    <thead>
                        <tr>
                            <th>@lang('product.product_name')</th>
                            <th>@lang('product.description')</th>
                            <th>@lang('sale.unit_price')</th>
                            <th>@lang('report.stock')</th>
                            <th>@lang('product.alert_quantity')</th>
                            <th>@lang('messages.status')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade plastic_bag_type_modal" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function(){
            //plastic bag types table
            plastic_bag_types_table = $('#plastic_bag_types_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/plastic-bag/types',
                columnDefs: [ {
                    "targets": [6],
                    "orderable": false,
                    "searchable": false
                } ],
                columns: [
                    { data: 'name', name: 'name'},
                    { data: 'description', name: 'description'},
                    { data: 'price', name: 'price'},
                    { data: 'stock_quantity', name: 'stock_quantity'},
                    { data: 'alert_quantity', name: 'alert_quantity'},
                    { data: 'is_active', name: 'is_active'},
                    { data: 'action', name: 'action'}
                ]
            });

            $(document).on('submit', 'form#plastic_bag_type_add_form', function(e) {
                e.preventDefault();
                $(this).find('button[type="submit"]').attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: "POST",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == 1) {
                            $('div.plastic_bag_type_modal').modal('hide');
                            toastr.success(result.msg);
                            plastic_bag_types_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('click', '.edit-type', function(e) {
                e.preventDefault();
                $('div.plastic_bag_type_modal').load($(this).data('href'), function() {
                    $('div.plastic_bag_type_modal').modal('show');
                });
            });

            $(document).on('submit', 'form#plastic_bag_type_edit_form', function(e) {
                e.preventDefault();
                $(this).find('button[type="submit"]').attr('disabled', true);
                var data = $(this).serialize();

                $.ajax({
                    method: "PUT",
                    url: $(this).attr("action"),
                    dataType: "json",
                    data: data,
                    success: function(result) {
                        if (result.success == 1) {
                            $('div.plastic_bag_type_modal').modal('hide');
                            toastr.success(result.msg);
                            plastic_bag_types_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $(document).on('click', '.delete-type', function(e) {
                e.preventDefault();
                swal({
                  title: LANG.sure,
                  text: LANG.confirm_delete_type,
                  icon: "warning",
                  buttons: true,
                  dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        var data = {};
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                            data: data,
                            success: function(result) {
                                if (result.success == 1) {
                                    toastr.success(result.msg);
                                    plastic_bag_types_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection