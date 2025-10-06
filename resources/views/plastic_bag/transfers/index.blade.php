@extends('layouts.app')
@section('title', __('Plastic Bag Stock Transfers'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Plastic Bag Stock Transfers')
        <small>@lang('Manage plastic bag stock transfers')</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">@lang('Plastic Bag Stock Transfers')</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('All Plastic Bag Stock Transfers')])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action([\App\Http\Controllers\PlasticBagController::class, 'createTransfer'])}}" 
                    data-container=".plastic_bag_transfer_modal">
                    <i class="fa fa-plus"></i> @lang('Add Transfer')</button>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="plastic_bag_transfers_table">
                <thead>
                    <tr>
                        <th>@lang('Transfer Number')</th>
                        <th>@lang('Date')</th>
                        <th>@lang('Plastic Bag Type')</th>
                        <th>@lang('From Location')</th>
                        <th>@lang('To Location')</th>
                        <th>@lang('Quantity')</th>
                        <th>@lang('Status')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade plastic_bag_transfer_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        //plastic_bag_transfers_table
        var plastic_bag_transfers_table = $('#plastic_bag_transfers_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/plastic-bag/stock-transfers',
            columnDefs: [ {
                "targets": [7],
                "orderable": false,
                "searchable": false
            } ],
        });

        $(document).on('submit', 'form#plastic_bag_transfer_add_form', function(e) {
            e.preventDefault();
            $(this).find('button[type="submit"]').attr('disabled', true);
            var data = $(this).serialize();

            $.ajax({
                method: "POST",
                url: $(this).attr("action"),
                dataType: "json",
                data: data,
                success: function(result) {
                    if (result.success == true) {
                        $('div.plastic_bag_transfer_modal').modal('hide');
                        toastr.success(result.msg);
                        plastic_bag_transfers_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        $(document).on('click', '.receive-transfer', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    var url = $(this).data('href');
                    $.ajax({
                        method: "PUT",
                        url: url,
                        dataType: "json",
                        data: {"_token": "{{ csrf_token() }}"},
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                plastic_bag_transfers_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        $(document).on('click', '.cancel-transfer', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                text: "This will cancel the transfer and restore stock",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    var url = $(this).data('href');
                    $.ajax({
                        method: "PUT",
                        url: url,
                        dataType: "json",
                        data: {"_token": "{{ csrf_token() }}"},
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                plastic_bag_transfers_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        $(document).on('click', '.delete-transfer', function(e) {
            e.preventDefault();
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    var url = $(this).data('href');
                    $.ajax({
                        method: "DELETE",
                        url: url,
                        dataType: "json",
                        data: {"_token": "{{ csrf_token() }}"},
                        success: function(result) {
                            if (result.success == true) {
                                toastr.success(result.msg);
                                plastic_bag_transfers_table.ajax.reload();
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