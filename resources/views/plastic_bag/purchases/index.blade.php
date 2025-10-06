@extends('layouts.app')
@section('title', __('Plastic Bag Purchases'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Plastic Bag Purchases')
        <small>@lang('Manage plastic bag purchases')</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Plastic Bag Purchases')])
        @can('plastic_bag.create')
            @slot('tool')
                <div class="box-tools">
                    <a href="{{action([\App\Http\Controllers\PlasticBagController::class, 'createPurchase'])}}"
                        class="btn btn-block btn-primary">
                        <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        
        @can('plastic_bag.access')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="plastic_bag_purchases_table">
                    <thead>
                        <tr>
                            <th>@lang('purchase.invoice_no')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('purchase.supplier')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('Invoice File')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

</section>

<!-- View Purchase Modal -->
<div class="modal fade" id="view_purchase_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('Plastic Bag Purchase Details')</h4>
            </div>
            <div class="modal-body">
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
    <script type="text/javascript">
        $(document).ready(function(){
            //plastic bag purchases table
            plastic_bag_purchases_table = $('#plastic_bag_purchases_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/plastic-bag/purchases',
                columnDefs: [ {
                    "targets": [5],
                    "orderable": false,
                    "searchable": false
                } ],
                columns: [
                    { data: 'invoice_number', name: 'invoice_number'},
                    { data: 'purchase_date', name: 'purchase_date'},
                    { data: 'supplier_id', name: 'supplier_id'},
                    { data: 'total_amount', name: 'total_amount'},
                    { data: 'invoice_file', name: 'invoice_file'},
                    { data: 'action', name: 'action'}
                ]
            });

            // View purchase
            $(document).on('click', '.view-purchase', function(e) {
                e.preventDefault();
                var url = $(this).data('href');
                
                $.get(url, function(data) {
                    $('#view_purchase_modal .modal-body').html(data);
                    $('#view_purchase_modal').modal('show');
                }).fail(function() {
                    toastr.error('Error loading purchase details');
                });
            });

            // Edit purchase  
            $(document).on('click', '.edit-purchase', function(e) {
                e.preventDefault();
                var url = $(this).data('href');
                window.location.href = url;
            });

            // Delete purchase
            $(document).on('click', '.delete-purchase', function(e) {
                e.preventDefault();
                var url = $(this).data('href');
                
                swal({
                    title: LANG.sure,
                    text: 'This will permanently delete the plastic bag purchase.',
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            method: "DELETE",
                            url: url,
                            dataType: "json",
                            data: {_token: $('meta[name="csrf-token"]').attr('content')},
                            success: function(result) {
                                if(result.success == 1) {
                                    toastr.success(result.msg);
                                    plastic_bag_purchases_table.ajax.reload();
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