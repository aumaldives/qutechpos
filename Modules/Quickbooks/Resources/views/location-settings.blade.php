@extends('layouts.app')

@section('title', __('QuickBooks Location Settings'))

@section('content')
<section class="content-header">
    <h1>@lang('QuickBooks Integration - :location', ['location' => $location->name])</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('Connection Settings')</h3>
                </div>
                <div class="box-body">
                    <form action="{{ route('quickbooks.location.settings.save', $location->id) }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_id">@lang('Client ID') <span class="text-red">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="client_id" 
                                           name="client_id" 
                                           value="{{ old('client_id', $settings->client_id) }}" 
                                           required>
                                    <small class="text-muted">
                                        Get this from your QuickBooks App Dashboard
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="client_secret">@lang('Client Secret') <span class="text-red">*</span></label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="client_secret" 
                                           name="client_secret" 
                                           value="{{ old('client_secret', $settings->client_secret ? '••••••••••••' : '') }}" 
                                           {{ $settings->client_secret ? '' : 'required' }}>
                                    <small class="text-muted">
                                        Leave blank to keep existing secret
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sandbox_mode">@lang('Environment') <span class="text-red">*</span></label>
                                    <select class="form-control" id="sandbox_mode" name="sandbox_mode" required>
                                        <option value="sandbox" {{ old('sandbox_mode', $settings->sandbox_mode) == 'sandbox' ? 'selected' : '' }}>
                                            Sandbox (Testing)
                                        </option>
                                        <option value="production" {{ old('sandbox_mode', $settings->sandbox_mode) == 'production' ? 'selected' : '' }}>
                                            Production (Live)
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sync_interval_minutes">@lang('Auto-Sync Interval (minutes)')</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="sync_interval_minutes" 
                                           name="sync_interval_minutes" 
                                           value="{{ old('sync_interval_minutes', $settings->sync_interval_minutes ?? 60) }}" 
                                           min="15" 
                                           max="1440">
                                    <small class="text-muted">
                                        Minimum 15 minutes, Maximum 24 hours (1440 minutes)
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h4>@lang('Synchronization Options')</h4>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="sync_customers" 
                                               value="1" 
                                               {{ old('sync_customers', $settings->sync_customers) ? 'checked' : '' }}>
                                        @lang('Sync Customers')
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="sync_suppliers" 
                                               value="1" 
                                               {{ old('sync_suppliers', $settings->sync_suppliers) ? 'checked' : '' }}>
                                        @lang('Sync Suppliers')
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="sync_products" 
                                               value="1" 
                                               {{ old('sync_products', $settings->sync_products) ? 'checked' : '' }}>
                                        @lang('Sync Products')
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="sync_invoices" 
                                               value="1" 
                                               {{ old('sync_invoices', $settings->sync_invoices) ? 'checked' : '' }}>
                                        @lang('Sync Invoices')
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="sync_payments" 
                                               value="1" 
                                               {{ old('sync_payments', $settings->sync_payments) ? 'checked' : '' }}>
                                        @lang('Sync Payments')
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="sync_purchases" 
                                               value="1" 
                                               {{ old('sync_purchases', $settings->sync_purchases) ? 'checked' : '' }}>
                                        @lang('Sync Purchases')
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="sync_inventory" 
                                               value="1" 
                                               {{ old('sync_inventory', $settings->sync_inventory) ? 'checked' : '' }}>
                                        @lang('Sync Inventory')
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" 
                                               name="enable_auto_sync" 
                                               value="1" 
                                               {{ old('enable_auto_sync', $settings->enable_auto_sync) ? 'checked' : '' }}>
                                        @lang('Enable Auto-Sync')
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> @lang('Save Settings')
                            </button>
                            <a href="{{ route('quickbooks.index') }}" class="btn btn-default">
                                <i class="fas fa-arrow-left"></i> @lang('Back')
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('Connection Status')</h3>
                </div>
                <div class="box-body">
                    @if($settings->isConfigured())
                        @if($settings->isTokenValid())
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Connected</strong><br>
                                Connected to QuickBooks Company: {{ $settings->company_id }}
                            </div>

                            <div class="btn-group-vertical btn-block">
                                <button type="button" class="btn btn-info test-connection-btn" data-location-id="{{ $location->id }}">
                                    <i class="fas fa-check"></i> Test Connection
                                </button>

                                <a href="{{ route('quickbooks.location.disconnect', $location->id) }}" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to disconnect QuickBooks?')">
                                    <i class="fas fa-unlink"></i> Disconnect
                                </a>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Token Expired</strong><br>
                                Please reconnect to QuickBooks
                            </div>

                            <a href="{{ route('quickbooks.location.connect', $location->id) }}" 
                               class="btn btn-success btn-block">
                                <i class="fas fa-link"></i> Connect to QuickBooks
                            </a>
                        @endif
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Not Connected</strong><br>
                            Configure your API credentials and connect to QuickBooks
                        </div>

                        @if($settings->client_id && $settings->client_secret)
                            <a href="{{ route('quickbooks.location.connect', $location->id) }}" 
                               class="btn btn-success btn-block">
                                <i class="fas fa-link"></i> Connect to QuickBooks
                            </a>
                        @else
                            <p class="text-muted">
                                Save your API credentials first, then connect to QuickBooks.
                            </p>
                        @endif
                    @endif
                </div>
            </div>

            @if($settings->last_successful_sync_at)
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('Sync Statistics')</h3>
                </div>
                <div class="box-body">
                    <table class="table table-condensed">
                        <tr>
                            <td><strong>Last Sync:</strong></td>
                            <td>{{ $settings->last_successful_sync_at->diffForHumans() }}</td>
                        </tr>
                        <tr>
                            <td><strong>Customers:</strong></td>
                            <td>{{ number_format($settings->total_customers_synced) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Suppliers:</strong></td>
                            <td>{{ number_format($settings->total_suppliers_synced) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Products:</strong></td>
                            <td>{{ number_format($settings->total_products_synced) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Invoices:</strong></td>
                            <td>{{ number_format($settings->total_invoices_synced) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Payments:</strong></td>
                            <td>{{ number_format($settings->total_payments_synced) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Purchases:</strong></td>
                            <td>{{ number_format($settings->total_purchases_synced) }}</td>
                        </tr>
                        @if($settings->failed_syncs_count > 0)
                        <tr>
                            <td><strong>Failed Syncs:</strong></td>
                            <td><span class="text-red">{{ number_format($settings->failed_syncs_count) }}</span></td>
                        </tr>
                        @endif
                    </table>

                    @if($settings->next_sync_time)
                    <p class="text-muted">
                        <i class="fas fa-clock"></i>
                        Next auto-sync: {{ $settings->next_sync_time->diffForHumans() }}
                    </p>
                    @endif
                </div>
            </div>
            @endif

            @if($settings->last_sync_error)
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('Last Error')</h3>
                </div>
                <div class="box-body">
                    <p class="text-red">{{ $settings->last_sync_error }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    $('.test-connection-btn').click(function() {
        var locationId = $(this).data('location-id');
        var btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        
        $.get('/quickbooks/location/' + locationId + '/test-connection')
            .done(function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Connection successful!');
                    if (response.company_info) {
                        toastr.info('Connected to: ' + response.company_info.Name);
                    }
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
});
</script>
@endsection