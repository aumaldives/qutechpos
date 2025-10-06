@extends('layouts.app')
@section('title', __('Plastic Bag Stock Adjustments'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Plastic Bag Stock Adjustments')
        <small>@lang('Manage plastic bag stock adjustments')</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Stock Adjustments')])
        @can('plastic_bag.create')
            @slot('tool')
                <div class="box-tools">
                    <button type="button" class="btn btn-block btn-primary btn-modal" 
                        data-href="{{action([\App\Http\Controllers\PlasticBagController::class, 'createAdjustment'])}}"
                        data-container=".plastic_bag_adjustment_modal">
                        <i class="fa fa-plus"></i> @lang('messages.add')</button>
                </div>
            @endslot
        @endcan
        
        @can('plastic_bag.access')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="plastic_bag_adjustments_table">
                    <thead>
                        <tr>
                            <th>@lang('Plastic Bag Type')</th>
                            <th>@lang('business.business_location')</th>
                            <th>@lang('messages.type')</th>
                            <th>@lang('report.qty')</th>
                            <th>@lang('lang_v1.reason')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade plastic_bag_adjustment_modal" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function(){
            //plastic bag adjustments table
            plastic_bag_adjustments_table = $('#plastic_bag_adjustments_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/plastic-bag/stock-adjustments',
                columnDefs: [ {
                    "targets": [6],
                    "orderable": false,
                    "searchable": false
                } ],
                columns: [
                    { data: 'plastic_bag_type_id', name: 'plastic_bag_type_id'},
                    { data: 'location_id', name: 'location_id'},
                    { data: 'adjustment_type', name: 'adjustment_type'},
                    { data: 'quantity', name: 'quantity'},
                    { data: 'reason', name: 'reason'},
                    { data: 'adjustment_date', name: 'adjustment_date'},
                    { data: 'action', name: 'action'}
                ]
            });

            $(document).on('submit', 'form#plastic_bag_adjustment_add_form', function(e) {
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
                            $('div.plastic_bag_adjustment_modal').modal('hide');
                            toastr.success(result.msg);
                            plastic_bag_adjustments_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                            $(this).find('button[type="submit"]').attr('disabled', false);
                        }
                    }
                });
            });

            $(document).on('click', '.delete-adjustment', function(e) {
                e.preventDefault();
                swal({
                  title: LANG.sure,
                  text: LANG.confirm_delete_adjustment,
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
                                    plastic_bag_adjustments_table.ajax.reload();
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