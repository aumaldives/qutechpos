<div class="pos-tab-content">
    <!-- Location Selection -->
    <div class="row">
        <div class="col-xs-12">
            <div class="callout callout-info">
                <h4><i class="fa fa-info-circle"></i> Multi-Location WooCommerce Settings</h4>
                <p>Configure WooCommerce integration separately for each business location. Select a location below to manage its WooCommerce settings.</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('current_location_id', __('business.business_locations') . ' *:') !!}
                {!! Form::select('current_location_id', $locations, $current_location_id, ['class' => 'form-control', 'id' => 'location_selector', 'placeholder' => 'Select Location...']); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <br>
                <button type="button" class="btn btn-info btn-sm" id="load_location_settings">
                    <i class="fa fa-refresh"></i> Load Settings for Selected Location
                </button>
                <span class="help-block">
                    <small>Select a location and click "Load Settings" to configure WooCommerce for that specific location.</small>
                </span>
            </div>
        </div>
    </div>

    <hr>

    <!-- API Configuration Form -->
    <div id="api_settings_form" style="display: none;">
        <div class="row">
            <div class="col-xs-12">
                <h4><i class="fa fa-cogs"></i> API Configuration for: <span id="selected_location_name">-</span></h4>
            </div>
        </div>
        
        <div class="row">
        	<div class="col-xs-4">
                <div class="form-group">
                	{!! Form::label('woocommerce_app_url',  __('woocommerce::lang.woocommerce_app_url') . ':') !!}
                	{!! Form::text('woocommerce_app_url', '', ['class' => 'form-control','placeholder' => 'https://your-store.com', 'id' => 'woocommerce_app_url']); !!}
                </div>
            </div>
            <div class="col-xs-4">
                <div class="form-group">
                    {!! Form::label('woocommerce_consumer_key',  __('woocommerce::lang.woocommerce_consumer_key') . ':') !!}
                    {!! Form::text('woocommerce_consumer_key', '', ['class' => 'form-control','placeholder' => 'ck_...', 'id' => 'woocommerce_consumer_key']); !!}
                </div>
            </div>
            <div class="col-xs-4">
                <div class="form-group">
                	{!! Form::label('woocommerce_consumer_secret', __('woocommerce::lang.woocommerce_consumer_secret') . ':') !!}
                    <input type="password" name="woocommerce_consumer_secret" value="" id="woocommerce_consumer_secret" class="form-control" placeholder="cs_...">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-4">
                <div class="form-group">
                    {!! Form::label('sync_interval_minutes', __('woocommerce::lang.sync_interval_minutes') . ':') !!}
                    {!! Form::number('sync_interval_minutes', 15, ['class' => 'form-control', 'min' => 5, 'max' => 1440, 'id' => 'sync_interval_minutes']); !!}
                </div>
            </div>
            <div class="col-xs-4">
                <div class="checkbox">
                    <label>
                        <br/>
                        {!! Form::checkbox('enable_auto_sync', 1, true, ['class' => 'input-icheck', 'id' => 'enable_auto_sync'] ); !!} @lang('woocommerce::lang.enable_auto_sync')
                    </label>
                </div>
            </div>
            <div class="col-xs-4">
                <div class="checkbox">
                    <label>
                        <br/>
                        {!! Form::checkbox('is_active', 1, true, ['class' => 'input-icheck', 'id' => 'is_active'] ); !!} Enable for this Location
                    </label>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <h5><i class="fa fa-check-square-o"></i> Sync Options</h5>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-3">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('sync_products', 1, true, ['class' => 'input-icheck', 'id' => 'sync_products'] ); !!} Products
                    </label>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('sync_orders', 1, true, ['class' => 'input-icheck', 'id' => 'sync_orders'] ); !!} Orders
                    </label>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('sync_inventory', 1, true, ['class' => 'input-icheck', 'id' => 'sync_inventory'] ); !!} Inventory
                    </label>
                </div>
            </div>
            <div class="col-xs-3">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('sync_customers', 1, true, ['class' => 'input-icheck', 'id' => 'sync_customers'] ); !!} Customers
                    </label>
                </div>
            </div>
        </div>

        <hr>
        
        <div class="row">
            <div class="col-xs-6">
                <div class="form-group">
                    <label>Webhook URL (Copy this to WooCommerce):</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="webhook_url_display" readonly>
                        <div class="input-group-btn">
                            <button type="button" class="btn btn-default" id="copy_webhook_url">
                                <i class="fa fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <small class="help-block">Add this URL to your WooCommerce webhook settings.</small>
                </div>
            </div>
            <div class="col-xs-6">
                <div class="form-group">
                    <br>
                    <button type="button" class="btn btn-info" id="test_connection">
                        <i class="fa fa-plug"></i> Test Connection
                    </button>
                    <button type="button" class="btn btn-success" id="save_location_settings">
                        <i class="fa fa-save"></i> Save Settings
                    </button>
                    <button type="button" class="btn btn-warning" id="trigger_sync">
                        <i class="fa fa-refresh"></i> Sync Now
                    </button>
                </div>
            </div>
        </div>

        <div id="connection_test_results" class="alert" style="display:none; margin-top: 15px;"></div>
    </div>
</div>

<script>
$(document).ready(function() {
    var currentLocationId = null;
    
    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Load settings for selected location
    $('#load_location_settings').click(function() {
        var locationId = $('#location_selector').val();
        if (!locationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        loadLocationSettings(locationId);
    });
    
    function loadLocationSettings(locationId) {
        $.ajax({
            url: '/woocommerce/api/location-settings/' + locationId,
            method: 'GET',
            success: function(response) {
                currentLocationId = locationId;
                var locationName = $('#location_selector option:selected').text();
                $('#selected_location_name').text(locationName);
                
                if (response.settings) {
                    // Populate form with existing settings
                    $('#woocommerce_app_url').val(response.settings.woocommerce_app_url || '');
                    $('#woocommerce_consumer_key').val(response.settings.woocommerce_consumer_key || '');
                    $('#woocommerce_consumer_secret').val(''); // Don't show existing secret
                    $('#sync_interval_minutes').val(response.settings.sync_interval_minutes || 15);
                    
                    // Update checkboxes
                    $('#enable_auto_sync').prop('checked', response.settings.enable_auto_sync).iCheck('update');
                    $('#is_active').prop('checked', response.settings.is_active).iCheck('update');
                    $('#sync_products').prop('checked', response.settings.sync_products).iCheck('update');
                    $('#sync_orders').prop('checked', response.settings.sync_orders).iCheck('update');
                    $('#sync_inventory').prop('checked', response.settings.sync_inventory).iCheck('update');
                    $('#sync_customers').prop('checked', response.settings.sync_customers).iCheck('update');
                } else {
                    // Reset form for new location
                    $('#api_settings_form input[type="text"], #api_settings_form input[type="password"], #api_settings_form input[type="number"]').val('');
                    $('#sync_interval_minutes').val(15);
                    
                    // Set default checkboxes
                    $('#enable_auto_sync, #is_active, #sync_products, #sync_orders, #sync_inventory, #sync_customers').prop('checked', true).iCheck('update');
                }
                
                // Update webhook URL
                $('#webhook_url_display').val('{{ url("woocommerce/webhook") }}/' + locationId);
                
                // Show the form
                $('#api_settings_form').show();
            },
            error: function() {
                toastr.error('Failed to load location settings.');
            }
        });
    }
    
    // Save settings
    $('#save_location_settings').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        var formData = {
            location_id: currentLocationId,
            woocommerce_app_url: $('#woocommerce_app_url').val(),
            woocommerce_consumer_key: $('#woocommerce_consumer_key').val(),
            woocommerce_consumer_secret: $('#woocommerce_consumer_secret').val(),
            sync_interval_minutes: $('#sync_interval_minutes').val(),
            enable_auto_sync: $('#enable_auto_sync').is(':checked') ? 1 : 0,
            is_active: $('#is_active').is(':checked') ? 1 : 0,
            sync_products: $('#sync_products').is(':checked') ? 1 : 0,
            sync_orders: $('#sync_orders').is(':checked') ? 1 : 0,
            sync_inventory: $('#sync_inventory').is(':checked') ? 1 : 0,
            sync_customers: $('#sync_customers').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: '/woocommerce/api/location-settings',
            method: 'POST',
            data: formData,
            success: function(response) {
                toastr.success('Location settings saved successfully!');
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to save settings.';
                toastr.error(message);
            }
        });
    });
    
    // Test connection
    $('#test_connection').click(function() {
        var formData = {
            woocommerce_app_url: $('#woocommerce_app_url').val(),
            woocommerce_consumer_key: $('#woocommerce_consumer_key').val(),
            woocommerce_consumer_secret: $('#woocommerce_consumer_secret').val()
        };
        
        if (!formData.woocommerce_app_url || !formData.woocommerce_consumer_key || !formData.woocommerce_consumer_secret) {
            toastr.error('Please fill in all API credentials first.');
            return;
        }
        
        var button = $(this);
        var originalText = button.html();
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');
        $('#connection_test_results').hide();
        
        $.ajax({
            url: '/woocommerce/api/test-connection',
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
    
    // Copy webhook URL
    $('#copy_webhook_url').click(function() {
        $('#webhook_url_display').select();
        document.execCommand('copy');
        toastr.success('Webhook URL copied to clipboard!');
    });
    
    // Trigger sync
    $('#trigger_sync').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        $.ajax({
            url: '/woocommerce/api/location-settings/' + currentLocationId + '/sync',
            method: 'POST',
            success: function(response) {
                toastr.success('Sync started successfully for selected location!');
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to start sync.';
                toastr.error(message);
            }
        });
    });
});
</script>