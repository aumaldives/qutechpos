@extends('layouts.app')
@section('title', 'Webhooks')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>Webhooks
        <small>Manage real-time event notifications</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{route('integrations')}}">Integrations</a></li>
        <li class="active">Webhooks</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Webhook Management'])
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group pull-right" style="margin-bottom: 15px;">
                            <a href="{{route('webhooks.create')}}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Create Webhook
                            </a>
                            <button type="button" class="btn btn-info" id="refresh-table">
                                <i class="fa fa-refresh"></i> Refresh
                            </button>
                        </div>
                        <div class="clearfix"></div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="webhooks_table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>URL</th>
                                        <th>Events</th>
                                        <th>Status</th>
                                        <th>Success Rate</th>
                                        <th>Last Triggered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Info Cards -->
    <div class="row">
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-send"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Available Events</span>
                    <span class="info-box-number">{{ count(\App\Models\Webhook::AVAILABLE_EVENTS) }}</span>
                    <span class="info-box-more">Different event types you can subscribe to</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Active Webhooks</span>
                    <span class="info-box-number" id="active-webhooks-count">-</span>
                    <span class="info-box-more">Currently receiving events</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Deliveries</span>
                    <span class="info-box-number" id="total-deliveries-count">-</span>
                    <span class="info-box-more">Events delivered this month</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Available Events Info -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-info collapsed-box', 'title' => 'Available Webhook Events'])
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <h4><i class="fa fa-cube"></i> Product Events</h4>
                        <ul class="list-unstyled">
                            <li><code>product.created</code> - New product added</li>
                            <li><code>product.updated</code> - Product modified</li>
                            <li><code>product.deleted</code> - Product removed</li>
                            <li><code>product.stock_updated</code> - Stock levels changed</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-3">
                        <h4><i class="fa fa-users"></i> Contact Events</h4>
                        <ul class="list-unstyled">
                            <li><code>contact.created</code> - New customer/supplier</li>
                            <li><code>contact.updated</code> - Contact modified</li>
                            <li><code>contact.deleted</code> - Contact removed</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-3">
                        <h4><i class="fa fa-exchange"></i> Transaction Events</h4>
                        <ul class="list-unstyled">
                            <li><code>sale.created</code> - New sale transaction</li>
                            <li><code>sale.completed</code> - Sale finalized</li>
                            <li><code>sale.cancelled</code> - Sale cancelled</li>
                            <li><code>purchase.created</code> - New purchase</li>
                            <li><code>transaction.payment_added</code> - Payment received</li>
                        </ul>
                    </div>
                    
                    <div class="col-md-3">
                        <h4><i class="fa fa-cubes"></i> Stock Events</h4>
                        <ul class="list-unstyled">
                            <li><code>stock.low_alert</code> - Low stock warning</li>
                            <li><code>stock.adjustment</code> - Stock adjusted</li>
                            <li><code>stock.transfer</code> - Stock moved</li>
                        </ul>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<!-- Test Webhook Modal -->
<div class="modal fade" id="test-webhook-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Test Webhook</h4>
            </div>
            <div class="modal-body">
                <div id="test-webhook-loading" class="text-center" style="padding: 40px;">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px;">Testing webhook endpoint...</p>
                </div>
                
                <div id="test-webhook-results" style="display: none;">
                    <div class="alert" id="test-result-alert">
                        <h4 id="test-result-title"></h4>
                        <p id="test-result-message"></p>
                    </div>
                    
                    <div class="row" id="test-result-details">
                        <div class="col-md-6">
                            <strong>Status Code:</strong> <span id="test-status-code"></span><br>
                            <strong>Response Time:</strong> <span id="test-response-time"></span><br>
                        </div>
                        <div class="col-md-6">
                            <strong>Error:</strong> <span id="test-error"></span>
                        </div>
                    </div>
                    
                    <div class="form-group" id="test-response-body-group" style="margin-top: 15px;">
                        <label>Response Body:</label>
                        <pre id="test-response-body" style="max-height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    
    // Initialize DataTable
    var webhooksTable = $('#webhooks_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('webhooks.index') }}",
            type: "GET"
        },
        columns: [
            { data: 'name', name: 'name' },
            { 
                data: 'url', 
                name: 'url',
                render: function(data, type, row) {
                    var displayUrl = data.length > 40 ? data.substring(0, 40) + '...' : data;
                    return '<a href="' + data + '" target="_blank" title="' + data + '">' + displayUrl + '</a>';
                }
            },
            { 
                data: 'events_count', 
                name: 'events_count',
                render: function(data, type, row) {
                    return '<span class="badge">' + data + '</span>';
                }
            },
            { data: 'health_status', name: 'health_status', orderable: false },
            { data: 'success_rate', name: 'success_rate' },
            { data: 'last_triggered', name: 'last_triggered' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        drawCallback: function() {
            updateStatsCounts();
        }
    });
    
    // Refresh table
    $('#refresh-table').on('click', function() {
        webhooksTable.ajax.reload();
    });
    
    // Test webhook
    $(document).on('click', '.test-webhook', function() {
        var webhookId = $(this).data('id');
        
        $('#test-webhook-modal').modal('show');
        $('#test-webhook-loading').show();
        $('#test-webhook-results').hide();
        
        $.ajax({
            url: '/webhooks/' + webhookId + '/test',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                displayTestResults(response.data);
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON?.message || 'Test failed';
                displayTestResults({
                    success: false,
                    error: errorMsg,
                    status_code: xhr.status,
                    response_time: null,
                    response_body: null
                });
            }
        });
    });
    
    // Toggle webhook status
    $(document).on('click', '.toggle-webhook', function() {
        var webhookId = $(this).data('id');
        var action = $(this).data('action');
        var button = $(this);
        
        $.ajax({
            url: '/webhooks/' + webhookId + '/toggle',
            type: 'POST',
            data: { action: action },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                toastr.success(response.message);
                webhooksTable.ajax.reload();
            },
            error: function(xhr) {
                toastr.error('Failed to ' + action + ' webhook');
            }
        });
    });
    
    // Delete webhook
    $(document).on('click', '.delete-webhook', function() {
        var webhookId = $(this).data('id');
        
        swal({
            title: "Are you sure?",
            text: "This will permanently delete the webhook and all its delivery history.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "No, cancel!"
        }, function(isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: '/webhooks/' + webhookId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        toastr.success(response.message);
                        webhooksTable.ajax.reload();
                    },
                    error: function(xhr) {
                        toastr.error('Failed to delete webhook');
                    }
                });
            }
        });
    });
    
    // Display test results
    function displayTestResults(data) {
        $('#test-webhook-loading').hide();
        $('#test-webhook-results').show();
        
        var alertClass = data.success ? 'alert-success' : 'alert-danger';
        var resultTitle = data.success ? 'Test Successful!' : 'Test Failed!';
        var resultIcon = data.success ? 'fa-check-circle' : 'fa-times-circle';
        
        $('#test-result-alert').removeClass('alert-success alert-danger').addClass(alertClass);
        $('#test-result-title').html('<i class="fa ' + resultIcon + '"></i> ' + resultTitle);
        $('#test-result-message').text(data.success ? 
            'Your webhook endpoint responded successfully.' : 
            'Your webhook endpoint failed to respond properly.'
        );
        
        $('#test-status-code').text(data.status_code || 'N/A');
        $('#test-response-time').text(data.response_time ? data.response_time + ' ms' : 'N/A');
        $('#test-error').text(data.error || 'None');
        
        if (data.response_body) {
            $('#test-response-body').text(data.response_body);
            $('#test-response-body-group').show();
        } else {
            $('#test-response-body-group').hide();
        }
    }
    
    // Update stats counts
    function updateStatsCounts() {
        // This would typically come from an AJAX endpoint
        // For now, we'll calculate from the table data
        var tableData = webhooksTable.rows().data();
        var activeCount = 0;
        
        tableData.each(function(value, index) {
            if (value.health_status && value.health_status.includes('Healthy')) {
                activeCount++;
            }
        });
        
        $('#active-webhooks-count').text(activeCount);
        $('#total-deliveries-count').text('...');
    }
    
    // Initial stats update
    webhooksTable.on('draw', function() {
        updateStatsCounts();
    });
});
</script>
@endsection