@extends('layouts.app')

@section('title', __('QuickBooks Integration'))

@section('content')
<section class="content-header">
    <h1>@lang('QuickBooks Integration')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('QuickBooks Location Settings')</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">
                        Configure QuickBooks integration separately for each business location. 
                        Each location can have its own QuickBooks company connection.
                    </p>

                    <div class="row">
                        @foreach($locations as $location)
                        <div class="col-md-6 col-lg-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <i class="fas fa-map-marker-alt"></i>
                                        {{ $location->name }}
                                    </h4>
                                </div>
                                <div class="panel-body">
                                    @php
                                        $locationSettings = $settings->get($location->id);
                                        $isConfigured = $locationSettings && $locationSettings->isConfigured();
                                        $isConnected = $isConfigured && $locationSettings->isTokenValid();
                                        $syncStatus = $locationSettings->sync_status ?? 'not_configured';
                                    @endphp

                                    <div class="row">
                                        <div class="col-xs-12">
                                            <div class="status-indicator mb-3">
                                                @if($isConnected)
                                                    <span class="label label-success">
                                                        <i class="fas fa-check-circle"></i> Connected
                                                    </span>
                                                @elseif($isConfigured)
                                                    <span class="label label-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Token Expired
                                                    </span>
                                                @else
                                                    <span class="label label-default">
                                                        <i class="fas fa-times-circle"></i> Not Configured
                                                    </span>
                                                @endif
                                            </div>

                                            @if($locationSettings)
                                            <div class="sync-stats mb-3">
                                                <small class="text-muted">
                                                    <strong>Last Sync:</strong> 
                                                    {{ $locationSettings->last_successful_sync_at ? $locationSettings->last_successful_sync_at->diffForHumans() : 'Never' }}
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <strong>Synced Items:</strong>
                                                    C:{{ $locationSettings->total_customers_synced }}
                                                    S:{{ $locationSettings->total_suppliers_synced }}
                                                    P:{{ $locationSettings->total_products_synced }}
                                                    I:{{ $locationSettings->total_invoices_synced }}
                                                </small>
                                            </div>
                                            @endif

                                            <div class="btn-group-vertical" role="group" style="width: 100%;">
                                                <a href="{{ route('quickbooks.location.settings', $location->id) }}" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-cog"></i> Configure
                                                </a>

                                                @if($isConfigured && !$isConnected)
                                                <a href="{{ route('quickbooks.location.connect', $location->id) }}" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-link"></i> Connect to QuickBooks
                                                </a>
                                                @endif

                                                @if($isConnected)
                                                <button type="button" 
                                                        class="btn btn-info btn-sm test-connection-btn"
                                                        data-location-id="{{ $location->id }}">
                                                    <i class="fas fa-check"></i> Test Connection
                                                </button>

                                                <div class="btn-group" role="group">
                                                    <button type="button" 
                                                            class="btn btn-warning btn-sm dropdown-toggle" 
                                                            data-toggle="dropdown">
                                                        <i class="fas fa-sync"></i> Sync <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a href="#" class="sync-data" data-location-id="{{ $location->id }}" data-sync-type="all">
                                                            <i class="fas fa-sync-alt"></i> Sync All</a></li>
                                                        <li role="separator" class="divider"></li>
                                                        <li><a href="#" class="sync-data" data-location-id="{{ $location->id }}" data-sync-type="customers">
                                                            <i class="fas fa-users"></i> Customers</a></li>
                                                        <li><a href="#" class="sync-data" data-location-id="{{ $location->id }}" data-sync-type="suppliers">
                                                            <i class="fas fa-truck"></i> Suppliers</a></li>
                                                        <li><a href="#" class="sync-data" data-location-id="{{ $location->id }}" data-sync-type="products">
                                                            <i class="fas fa-box"></i> Products</a></li>
                                                        <li><a href="#" class="sync-data" data-location-id="{{ $location->id }}" data-sync-type="invoices">
                                                            <i class="fas fa-file-invoice"></i> Invoices</a></li>
                                                        <li><a href="#" class="sync-data" data-location-id="{{ $location->id }}" data-sync-type="payments">
                                                            <i class="fas fa-credit-card"></i> Payments</a></li>
                                                        <li><a href="#" class="sync-data" data-location-id="{{ $location->id }}" data-sync-type="purchases">
                                                            <i class="fas fa-shopping-cart"></i> Purchases</a></li>
                                                    </ul>
                                                </div>

                                                <a href="{{ route('quickbooks.location.disconnect', $location->id) }}" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Are you sure you want to disconnect QuickBooks for this location?')">
                                                    <i class="fas fa-unlink"></i> Disconnect
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    @if(count($locations) == 0)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        No business locations found. Please create at least one business location to configure QuickBooks integration.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Progress Modal -->
    <div class="modal fade" id="syncProgressModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fas fa-sync fa-spin"></i> Synchronizing with QuickBooks
                    </h4>
                </div>
                <div class="modal-body">
                    <div id="sync-progress-content">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped active" 
                                 style="width: 100%">
                                Syncing...
                            </div>
                        </div>
                        <p class="text-center">
                            <small id="sync-status-text">Starting synchronization...</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Test connection functionality
    $('.test-connection-btn').click(function() {
        var locationId = $(this).data('location-id');
        var btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        
        $.get('/quickbooks/location/' + locationId + '/test-connection')
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Connection successful!');
                } else {
                    toastr.error(response.message || 'Connection failed');
                }
            })
            .fail(function() {
                toastr.error('Failed to test connection');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Test Connection');
            });
    });

    // Sync data functionality
    $('.sync-data').click(function(e) {
        e.preventDefault();
        var locationId = $(this).data('location-id');
        var syncType = $(this).data('sync-type');
        
        $('#syncProgressModal').modal('show');
        $('#sync-status-text').text('Starting ' + syncType + ' synchronization...');
        
        $.post('/quickbooks/location/' + locationId + '/sync', {
            sync_type: syncType,
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            $('#syncProgressModal').modal('hide');
            
            if (response.success) {
                var message = 'Synchronization completed successfully!';
                if (response.results) {
                    var details = [];
                    Object.keys(response.results).forEach(function(key) {
                        var result = response.results[key];
                        details.push(key + ': ' + result.synced + '/' + result.total + ' synced');
                    });
                    message += '<br><small>' + details.join(', ') + '</small>';
                }
                toastr.success(message);
                
                // Refresh the page to show updated stats
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                toastr.error(response.message || 'Synchronization failed');
            }
        })
        .fail(function(xhr) {
            $('#syncProgressModal').modal('hide');
            var message = 'Synchronization failed';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message += ': ' + xhr.responseJSON.message;
            }
            toastr.error(message);
        });
    });
});
</script>
@endsection

@section('css')
<style>
.panel {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 1px 1px rgba(0,0,0,.05);
}

.panel-heading {
    padding: 10px 15px;
    border-bottom: 1px solid #ddd;
    background-color: #f5f5f5;
    border-top-left-radius: 3px;
    border-top-right-radius: 3px;
}

.panel-body {
    padding: 15px;
}

.status-indicator {
    text-align: center;
    margin-bottom: 15px;
}

.sync-stats {
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 3px;
    margin-bottom: 15px;
}

.btn-group-vertical .btn {
    margin-bottom: 5px;
}

.dropdown-menu {
    width: 100%;
}
</style>
@endsection
