@extends('layouts.app')

@section('title', __('QuickBooks Integration'))

@section('content')
<section class="content-header">
    <h1>@lang('quickbooks::lang.quickbooks_integration')</h1>
</section>

<section class="content">
    @if(!$appConfigExists)
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle"></i> Setup Required</h4>
                <p>QuickBooks integration is being configured by your administrator. This feature will be available soon.</p>
                <p>If you're an administrator, please contact support to complete the QuickBooks app configuration.</p>
            </div>
        </div>
    </div>
    @else
    
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('quickbooks::lang.connection_settings')</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-toggle="modal" data-target="#howItWorksModal">
                            <i class="fas fa-question-circle"></i> How it Works
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="alert alert-success">
                        <i class="fas fa-shield-alt"></i>
                        <strong>Simplified Connection:</strong> 
                        No need to create QuickBooks apps or manage API keys. Just click "Connect" and authorize access to your QuickBooks company!
                    </div>

                    <div class="row">
                        @foreach($locations as $location)
                        <div class="col-md-6 col-lg-4">
                            <div class="panel panel-default location-panel">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <i class="fas fa-map-marker-alt"></i>
                                        {{ $location->name }}
                                    </h4>
                                </div>
                                <div class="panel-body">
                                    @php
                                        $locationSettings = $settings->get($location->id);
                                        $connectionStatus = $locationSettings->connection_status ?? 'disconnected';
                                        $isConnected = $connectionStatus === 'connected';
                                        $companyName = $locationSettings->quickbooks_company_name ?? null;
                                    @endphp

                                    <div class="connection-status mb-3">
                                        @if($isConnected)
                                            <div class="alert alert-success" style="margin-bottom: 10px; padding: 10px;">
                                                <i class="fas fa-check-circle"></i>
                                                <strong>Connected</strong>
                                                @if($companyName)
                                                    <br><small>{{ $companyName }}</small>
                                                @endif
                                            </div>
                                        @elseif($connectionStatus === 'token_expired')
                                            <div class="alert alert-warning" style="margin-bottom: 10px; padding: 10px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Connection Expired</strong>
                                                <br><small>Please reconnect to continue syncing</small>
                                            </div>
                                        @else
                                            <div class="alert alert-info" style="margin-bottom: 10px; padding: 10px;">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>Not Connected</strong>
                                                <br><small>Ready to connect to QuickBooks</small>
                                            </div>
                                        @endif
                                    </div>

                                    @if($isConnected && $locationSettings)
                                        <div class="sync-stats mb-3">
                                            <h5><i class="fas fa-sync"></i> Sync Statistics</h5>
                                            <div class="row">
                                                <div class="col-xs-6">
                                                    <div class="text-center">
                                                        <div class="stat-number">{{ $locationSettings->total_customers_synced }}</div>
                                                        <div class="stat-label">Customers</div>
                                                    </div>
                                                </div>
                                                <div class="col-xs-6">
                                                    <div class="text-center">
                                                        <div class="stat-number">{{ $locationSettings->total_invoices_synced }}</div>
                                                        <div class="stat-label">Invoices</div>
                                                    </div>
                                                </div>
                                            </div>
                                            @if($locationSettings->last_successful_sync_at)
                                                <div class="text-muted text-center mt-2">
                                                    <small>Last sync: {{ $locationSettings->last_successful_sync_at->diffForHumans() }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="action-buttons">
                                        @if($isConnected)
                                            <div class="btn-group btn-group-justified" role="group">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('quickbooks.location.settings', $location->id) }}" 
                                                       class="btn btn-primary">
                                                        <i class="fas fa-cog"></i> Settings
                                                    </a>
                                                </div>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-success" onclick="syncLocation({{ $location->id }})">
                                                        <i class="fas fa-sync"></i> Sync Now
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <button type="button" class="btn btn-danger btn-sm btn-block" 
                                                        onclick="disconnectLocation({{ $location->id }})">
                                                    <i class="fas fa-unlink"></i> Disconnect
                                                </button>
                                            </div>
                                        @else
                                            <div class="environment-selector mb-3">
                                                <label><strong>Environment:</strong></label>
                                                <div class="radio-group">
                                                    <label class="radio-inline">
                                                        <input type="radio" name="env_{{ $location->id }}" value="sandbox" checked>
                                                        <i class="fas fa-vial"></i> Sandbox (Testing)
                                                    </label>
                                                    <label class="radio-inline">
                                                        <input type="radio" name="env_{{ $location->id }}" value="production">
                                                        <i class="fas fa-building"></i> Production (Live)
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-success btn-block" 
                                                    onclick="connectToQuickBooks({{ $location->id }})">
                                                <i class="fas fa-plug"></i> Connect to QuickBooks
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>

<!-- How It Works Modal -->
<div class="modal fade" id="howItWorksModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-info-circle"></i> How QuickBooks Integration Works
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5><i class="fas fa-shield-alt"></i> Secure & Simple</h5>
                        <p>Our QuickBooks integration uses industry-standard OAuth2 security. You don't need to create any apps or manage API keys.</p>
                        
                        <h5><i class="fas fa-list-ol"></i> Connection Process</h5>
                        <ol>
                            <li><strong>Click "Connect":</strong> Choose your environment (Sandbox for testing, Production for live data)</li>
                            <li><strong>Authorize Access:</strong> You'll be redirected to QuickBooks to log in and approve access</li>
                            <li><strong>Start Syncing:</strong> Once connected, you can sync customers, products, invoices, and more</li>
                        </ol>

                        <h5><i class="fas fa-sync"></i> What Gets Synced</h5>
                        <ul>
                            <li><i class="fas fa-users"></i> <strong>Customers:</strong> Contact information and purchase history</li>
                            <li><i class="fas fa-truck"></i> <strong>Suppliers:</strong> Vendor details and purchase records</li>
                            <li><i class="fas fa-box"></i> <strong>Products:</strong> Items, pricing, and inventory levels</li>
                            <li><i class="fas fa-file-invoice"></i> <strong>Sales:</strong> Invoices, payments, and receipts</li>
                            <li><i class="fas fa-shopping-cart"></i> <strong>Purchases:</strong> Bills and purchase orders</li>
                        </ul>

                        <h5><i class="fas fa-map-marker-alt"></i> Location-Specific Connections</h5>
                        <p>Each business location can connect to its own QuickBooks company, giving you complete flexibility for multi-location businesses.</p>
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
function connectToQuickBooks(locationId) {
    const environment = $(`input[name="env_${locationId}"]:checked`).val();
    
    // Show loading
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connecting...';
    btn.disabled = true;
    
    $.ajax({
        url: '{{ route("quickbooks.oauth.initiate") }}',
        type: 'POST',
        data: {
            location_id: locationId,
            environment: environment,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success && response.auth_url) {
                // Redirect to QuickBooks for authorization
                window.location.href = response.auth_url;
            } else {
                toastr.error(response.message || 'Failed to initiate connection');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            toastr.error(response.message || 'Connection failed. Please try again.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

function disconnectLocation(locationId) {
    if (!confirm('Are you sure you want to disconnect this location from QuickBooks? This will stop all synchronization.')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("quickbooks.oauth.disconnect") }}',
        type: 'POST',
        data: {
            location_id: locationId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Successfully disconnected from QuickBooks');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to disconnect');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            toastr.error(response.message || 'Disconnect failed. Please try again.');
        }
    });
}

function syncLocation(locationId) {
    toastr.info('Sync functionality will be implemented in the next phase');
    // TODO: Implement sync functionality
}
</script>

<style>
.location-panel {
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 20px;
    transition: box-shadow 0.3s ease;
}

.location-panel:hover {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #3c8dbc;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.radio-group {
    margin-top: 5px;
}

.radio-inline {
    margin-right: 15px;
    font-weight: normal;
}

.environment-selector label {
    font-weight: 600;
    margin-bottom: 5px;
}
</style>
@endsection