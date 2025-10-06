@extends('layouts.app')
@section('title', __('woocommerce::lang.location_woocommerce_settings'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('woocommerce::lang.location_woocommerce_settings')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('woocommerce::lang.location_woocommerce_settings')])
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('location_filter', __('business.business_locations') . ':') !!}
                            {!! Form::select('location_filter', $locations, null, ['class' => 'form-control', 'id' => 'location_filter', 'placeholder' => __('lang_v1.all')]) !!}
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="pull-right">
                            <button type="button" class="btn btn-primary" id="add_location_config">
                                <i class="fa fa-plus"></i> @lang('woocommerce::lang.add_location_configuration')
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped" id="location_woocommerce_table">
                            <thead>
                                <tr>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('woocommerce::lang.woocommerce_app_url')</th>
                                    <th>@lang('woocommerce::lang.sync_status')</th>
                                    <th>@lang('woocommerce::lang.last_sync')</th>
                                    <th>@lang('woocommerce::lang.sync_stats')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Location Configuration Modal -->
    <div class="modal fade" id="location_config_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="location_config_form">
                    <div class="modal-header">
                        <h4 class="modal-title">@lang('woocommerce::lang.location_woocommerce_configuration')</h4>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('location_id', __('business.business_locations') . ':*') !!}
                                    {!! Form::select('location_id', $locations, null, ['class' => 'form-control', 'required', 'id' => 'modal_location_id']) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        {!! Form::checkbox('is_active', 1, true, ['class' => 'input-icheck']) !!}
                                        @lang('woocommerce::lang.enable_location_sync')
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h4>@lang('woocommerce::lang.api_configuration')</h4>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    {!! Form::label('woocommerce_app_url', __('woocommerce::lang.woocommerce_app_url') . ':*') !!}
                                    {!! Form::url('woocommerce_app_url', null, ['class' => 'form-control', 'required', 'placeholder' => 'https://your-store.com']) !!}
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('woocommerce_consumer_key', __('woocommerce::lang.woocommerce_consumer_key') . ':*') !!}
                                    {!! Form::text('woocommerce_consumer_key', null, ['class' => 'form-control', 'required']) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('woocommerce_consumer_secret', __('woocommerce::lang.woocommerce_consumer_secret') . ':*') !!}
                                    <input type="password" name="woocommerce_consumer_secret" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h4>@lang('woocommerce::lang.sync_settings')</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('sync_interval_minutes', __('woocommerce::lang.sync_interval_minutes') . ':') !!}
                                    {!! Form::number('sync_interval_minutes', 15, ['class' => 'form-control', 'min' => 5, 'max' => 1440]) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        {!! Form::checkbox('enable_auto_sync', 1, true, ['class' => 'input-icheck']) !!}
                                        @lang('woocommerce::lang.enable_auto_sync')
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>
                                        {!! Form::checkbox('sync_products', 1, true, ['class' => 'input-icheck']) !!}
                                        @lang('woocommerce::lang.sync_products')
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>
                                        {!! Form::checkbox('sync_orders', 1, true, ['class' => 'input-icheck']) !!}
                                        @lang('woocommerce::lang.sync_orders')
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>
                                        {!! Form::checkbox('sync_inventory', 1, true, ['class' => 'input-icheck']) !!}
                                        @lang('woocommerce::lang.sync_inventory')
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>
                                        {!! Form::checkbox('sync_customers', 1, true, ['class' => 'input-icheck']) !!}
                                        @lang('woocommerce::lang.sync_customers')
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h4>@lang('woocommerce::lang.webhook_configuration')</h4>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    {!! Form::label('webhook_url', __('woocommerce::lang.webhook_url') . ':') !!}
                                    {!! Form::text('webhook_url', null, ['class' => 'form-control', 'readonly' => true, 'id' => 'webhook_url_display']) !!}
                                    <small class="help-block">@lang('woocommerce::lang.webhook_url_help')</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <br>
                                    <button type="button" class="btn btn-info btn-sm" id="copy_webhook_url">
                                        <i class="fa fa-copy"></i> @lang('woocommerce::lang.copy_webhook_url')
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="connection_test_results" class="alert" style="display:none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                        <button type="button" class="btn btn-info" id="test_connection">
                            <i class="fa fa-plug"></i> @lang('woocommerce::lang.test_connection')
                        </button>
                        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</section>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var location_woocommerce_table = $('#location_woocommerce_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('woocommerce.location-settings.index') }}",
            data: function(d) {
                d.location_id = $('#location_filter').val();
            }
        },
        columnDefs: [
            {
                targets: [2, 3, 4, 5],
                orderable: false,
                searchable: false
            }
        ],
        columns: [
            {data: 'location_name', name: 'business_locations.name'},
            {data: 'woocommerce_app_url', name: 'woocommerce_app_url'},
            {data: 'sync_status', name: 'sync_status'},
            {data: 'last_sync', name: 'last_successful_sync_at'},
            {data: 'sync_stats', name: 'sync_stats'},
            {data: 'action', name: 'action'}
        ]
    });

    // Location filter change
    $('#location_filter').change(function() {
        location_woocommerce_table.ajax.reload();
    });

    // Add new location configuration
    $('#add_location_config').click(function() {
        $('#location_config_form')[0].reset();
        $('.modal-title').text("{{ __('woocommerce::lang.add_location_configuration') }}");
        $('#location_config_modal').modal('show');
        updateWebhookUrl();
    });

    // Update webhook URL when location changes
    $('#modal_location_id').change(function() {
        updateWebhookUrl();
    });

    function updateWebhookUrl() {
        var locationId = $('#modal_location_id').val();
        if (locationId) {
            var webhookUrl = "{{ url('modules/woocommerce/webhook') }}/" + locationId;
            $('#webhook_url_display').val(webhookUrl);
        }
    }

    // Copy webhook URL
    $('#copy_webhook_url').click(function() {
        $('#webhook_url_display').select();
        document.execCommand('copy');
        toastr.success("{{ __('woocommerce::lang.webhook_url_copied') }}");
    });

    // Test connection
    $('#test_connection').click(function() {
        var formData = $('#location_config_form').serialize();
        var button = $(this);
        var originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');
        $('#connection_test_results').hide();

        $.ajax({
            url: "{{ route('woocommerce.location-settings.test-connection') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#connection_test_results')
                    .removeClass('alert-danger')
                    .addClass('alert-success')
                    .html('<strong>Success!</strong> ' + response.message)
                    .show();
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Connection failed';
                $('#connection_test_results')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .html('<strong>Error!</strong> ' + message)
                    .show();
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Save configuration
    $('#location_config_form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: "{{ route('woocommerce.location-settings.store') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#location_config_modal').modal('hide');
                toastr.success(response.message);
                location_woocommerce_table.ajax.reload();
            },
            error: function(xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    var errorMsg = Object.values(errors).flat().join('<br>');
                    toastr.error(errorMsg);
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred');
                }
            }
        });
    });

    // Edit configuration
    $(document).on('click', '.edit-config', function() {
        var configId = $(this).data('id');
        
        $.ajax({
            url: "{{ url('modules/woocommerce/location-settings') }}/" + configId + '/edit',
            method: 'GET',
            success: function(response) {
                // Populate form with existing data
                var config = response.config;
                $('#modal_location_id').val(config.location_id);
                $('input[name="woocommerce_app_url"]').val(config.woocommerce_app_url);
                $('input[name="woocommerce_consumer_key"]').val(config.woocommerce_consumer_key);
                $('input[name="sync_interval_minutes"]').val(config.sync_interval_minutes);
                
                // Set checkboxes
                $('input[name="is_active"]').prop('checked', config.is_active).iCheck('update');
                $('input[name="enable_auto_sync"]').prop('checked', config.enable_auto_sync).iCheck('update');
                $('input[name="sync_products"]').prop('checked', config.sync_products).iCheck('update');
                $('input[name="sync_orders"]').prop('checked', config.sync_orders).iCheck('update');
                $('input[name="sync_inventory"]').prop('checked', config.sync_inventory).iCheck('update');
                $('input[name="sync_customers"]').prop('checked', config.sync_customers).iCheck('update');
                
                // Set form action for update
                $('#location_config_form').attr('action', "{{ url('modules/woocommerce/location-settings') }}/" + configId);
                $('#location_config_form').append('<input type="hidden" name="_method" value="PUT">');
                
                $('.modal-title').text("{{ __('woocommerce::lang.edit_location_configuration') }}");
                $('#location_config_modal').modal('show');
                updateWebhookUrl();
            }
        });
    });

    // Delete configuration
    $(document).on('click', '.delete-config', function() {
        var configId = $(this).data('id');
        
        swal({
            title: "{{ __('messages.sure') }}",
            text: "{{ __('woocommerce::lang.location_config_delete_confirm') }}",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: "{{ __('messages.yes') }}",
            cancelButtonText: "{{ __('messages.no') }}"
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: "{{ url('modules/woocommerce/location-settings') }}/" + configId,
                    method: 'DELETE',
                    success: function(response) {
                        toastr.success(response.message);
                        location_woocommerce_table.ajax.reload();
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'An error occurred');
                    }
                });
            }
        });
    });
});
</script>

@endsection