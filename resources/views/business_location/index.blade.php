@extends('layouts.app')
@section('title', __('business.business_locations'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'business.business_locations' )
        <small>@lang( 'business.manage_your_business_locations' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'business.all_your_business_locations' )])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary btn-modal" 
                    data-href="{{action([\App\Http\Controllers\BusinessLocationController::class, 'create'])}}" 
                    data-container=".location_add_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
            </div>
        @endslot
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="business_location_table">
                <thead>
                    <tr>
                        <th>@lang( 'invoice.name' )</th>
                        <th>@lang( 'lang_v1.location_id' )</th>
                        <th>@lang( 'business.landmark' )</th>
                        <th>@lang( 'business.city' )</th>
                        <th>@lang( 'business.zip_code' )</th>
                        <th>@lang( 'business.state' )</th>
                        <th>@lang( 'business.country' )</th>
                        <th>@lang( 'lang_v1.price_group' )</th>
                        <th>@lang( 'invoice.invoice_scheme' )</th>
                        <th>@lang('lang_v1.invoice_layout_for_pos')</th>
                        <th>@lang('lang_v1.invoice_layout_for_sale')</th>
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade location_add_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade location_edit_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function(){
    
    // Override the default location activation handler to include subscription validation
    $(document).off('click', 'button.activate-deactivate-location');
    
    // Handle location activation with subscription validation
    $(document).on('click', 'button.activate-deactivate-location', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        var url = $(this).data('href');
        var btn = $(this);
        
        // Show confirmation dialog first (like the original behavior)
        swal({
            title: "@lang('lang_v1.are_you_sure')",
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                // Proceed with the action
                $.ajax({
                    method: "GET",
                    url: url,
                    dataType: "json",
                    success: function(result){
                        console.log('Location activation result:', result);
                        
                        if(result.success == true){
                            toastr.success(result.msg);
                            // Use the global business_locations table variable from app.js
                            if (typeof business_locations !== 'undefined') {
                                business_locations.ajax.reload();
                            }
                        } else if (result.requires_upgrade) {
                            console.log('Showing upgrade dialog');
                            // Show upgrade dialog - using SweetAlert v1/v2 compatible format
                            swal({
                                title: "@lang('superadmin::lang.location_limit_exceeded')",
                                text: result.msg,
                                icon: "warning",
                                buttons: {
                                    cancel: {
                                        text: "@lang('messages.cancel')",
                                        value: false,
                                        visible: true
                                    },
                                    confirm: {
                                        text: "@lang('superadmin::lang.upgrade_subscription')",
                                        value: true
                                    }
                                },
                                dangerMode: true,
                            }).then(function(isConfirm){
                                if (isConfirm) {
                                    // Redirect to subscription page
                                    window.location.href = "{{action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index'])}}";
                                }
                            });
                        } else {
                            console.log('Error result:', result);
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', xhr.responseText);
                        toastr.error('An error occurred: ' + error);
                    }
                });
            }
        });
    });
});
</script>
@endsection
