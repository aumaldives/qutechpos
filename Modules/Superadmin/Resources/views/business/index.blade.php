@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Business')

@section('content')
@include('superadmin::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'superadmin::lang.all_business' )
        <small>@lang( 'superadmin::lang.manage_business' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('package_id',  __('superadmin::lang.packages') . ':') !!}
                {!! Form::select('package_id', $packages, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('subscription_status',  __('superadmin::lang.subscription_status') . ':') !!}
                {!! Form::select('subscription_status', $subscription_statuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('is_active',  __('sale.status') . ':') !!}
                {!! Form::select('is_active', ['active' => __('business.is_active'), 'inactive' => __('lang_v1.inactive')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('last_transaction_date',  __('superadmin::lang.last_transaction_date') . ':') !!}
                {!! Form::select('last_transaction_date', $last_transaction_date, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('no_transaction_since',  __('superadmin::lang.no_transaction_since') . ':') !!}
                {!! Form::select('no_transaction_since', $last_transaction_date, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
    @endcomponent
	<div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">&nbsp;</h3>
        	<div class="box-tools">
                <a href="{{action([\Modules\Superadmin\Http\Controllers\BusinessController::class, 'create'])}}" 
                    class="btn btn-block btn-primary">
                	<i class="fa fa-plus"></i> @lang( 'messages.add' )</a>
            </div>
        </div>

        <div class="box-body" style="padding: 0; overflow: hidden;">
            @can('superadmin')
                <div class="table-container" style="overflow-x: auto; overflow-y: hidden; max-width: 100%; padding: 15px;">
                    <table class="table table-bordered table-striped table-hover" id="superadmin_business_table" style="min-width: 1400px; margin-bottom: 0; font-size: 13px;">
                        <thead style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                            <tr>
                                <th style="min-width: 120px; white-space: nowrap;">
                                    @lang('superadmin::lang.registered_on')
                                </th>
                                <th style="min-width: 150px; white-space: nowrap;">@lang( 'superadmin::lang.business_name' )</th>
                                <th style="min-width: 120px; white-space: nowrap;">@lang('business.owner')</th>
                                <th style="min-width: 180px; white-space: nowrap;">@lang('business.email')</th>
                                <th style="min-width: 120px; white-space: nowrap;">@lang('superadmin::lang.owner_number')</th>
                                <th style="min-width: 150px; white-space: nowrap;">@lang( 'superadmin::lang.business_contact_number' )</th>
                                <th style="min-width: 200px; white-space: nowrap;">@lang('business.address')</th>
                                <th style="min-width: 100px; white-space: nowrap;">@lang( 'sale.status' )</th>
                                <th style="min-width: 150px; white-space: nowrap;">@lang( 'superadmin::lang.current_subscription' )</th>
                                <th style="min-width: 120px; white-space: nowrap;">Verify Status</th>
                                <th style="min-width: 120px; white-space: nowrap;">@lang( 'superadmin::lang.action' )</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            @endcan
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')

<script type="text/javascript">
    $(document).ready( function(){
        superadmin_business_table = $('#superadmin_business_table').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            scrollCollapse: true,
            responsive: false,
            ajax: {
                url: "{{action([\Modules\Superadmin\Http\Controllers\BusinessController::class, 'index'])}}",
                data: function(d) {
                    d.package_id = $('#package_id').val();
                    d.subscription_status = $('#subscription_status').val();
                    d.is_active = $('#is_active').val();
                    d.last_transaction_date = $('#last_transaction_date').val();
                    d.no_transaction_since = $('#no_transaction_since').val();
                },
            },
            aaSorting: [[0, 'desc']],
            language: {
                processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                emptyTable: 'No businesses found',
                info: 'Showing _START_ to _END_ of _TOTAL_ businesses',
                infoEmpty: 'Showing 0 to 0 of 0 businesses',
                lengthMenu: 'Show _MENU_ businesses per page'
            },
            columns: [
                { data: 'created_at', name: 'business.created_at', className: 'text-center' },
                { data: 'name', name: 'business.name', className: 'font-weight-bold' },
                { data: 'owner_name', name: 'owner_name', searchable: false, className: 'text-center'},
                { data: 'owner_email', name: 'u.email' },
                { data: 'contact_number', name: 'u.contact_number', className: 'text-center' },
                { data: 'business_contact_number', name: 'business_contact_number', className: 'text-center' },
                { data: 'address', name: 'address' },
                { data: 'is_active', name: 'is_active', searchable: false, className: 'text-center' },
                { data: 'current_subscription', name: 'p.name', className: 'text-center' },
                { data: 'is_email_verified', name: 'u.is_email_verified', className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
            ],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });

        $('#package_id, #subscription_status, #is_active, #last_transaction_date, #no_transaction_since').change( function(){
            superadmin_business_table.ajax.reload();
        });
    });
    $(document).on('click', 'a.delete_business_confirmation', function(e){
        e.preventDefault();
        swal({
            title: LANG.sure,
            text: "Once deleted, you will not be able to recover this business!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((confirmed) => {
            if (confirmed) {
                window.location.href = $(this).attr('href');
            }
        });
    });
</script>

@endsection

@section('css')
<style>
/* Modern DataTable Styling */
.table-container {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
}

#superadmin_business_table {
    border-collapse: separate;
    border-spacing: 0;
}

#superadmin_business_table thead th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding: 12px 8px;
}

#superadmin_business_table tbody td {
    padding: 10px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
}

#superadmin_business_table tbody tr:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

/* Custom scrollbar */
.table-container::-webkit-scrollbar {
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f3f4;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #c1c9d0;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #9aa5b1;
}

/* DataTables custom styling */
.dataTables_wrapper .dataTables_length select {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 4px 8px;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 6px 12px;
}

.dataTables_wrapper .dataTables_info {
    color: #6c757d;
    font-size: 14px;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin: 0 2px;
    padding: 6px 12px;
    background: white;
    color: #495057;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #007bff;
    border-color: #007bff;
    color: white;
}

/* Processing indicator */
.dataTables_processing {
    background: rgba(255,255,255,0.9);
    border: 1px solid #dee2e6;
    border-radius: 4px;
    color: #495057;
    font-size: 14px;
    margin: -1px;
    padding: 20px;
}

/* Fix for horizontal scroll */
.dataTables_scroll {
    width: 100% !important;
}

.dataTables_scrollBody {
    width: 100% !important;
}

.table-container {
    width: 100%;
    overflow-x: auto !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-container {
        padding: 10px;
    }

    #superadmin_business_table {
        font-size: 12px;
    }

    #superadmin_business_table thead th,
    #superadmin_business_table tbody td {
        padding: 8px 6px;
    }
}
</style>
@endsection