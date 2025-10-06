@extends('layouts.app')

@section('title', 'WooCommerce Configuration')

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <i class="fab fa-wordpress"></i>
                    WooCommerce Configuration
                    <small>Configure and monitor your WooCommerce synchronization</small>
                </h1>
            </div>
            <div class="col-sm-6">
                <div class="float-sm-right">
                    <a href="{{ route('woocommerce.sync-health') }}" class="btn btn-primary">
                        <i class="fas fa-heartbeat"></i> Sync Health Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('Statistics Overview')])
        <div class="row">
            <div class="col-lg-3 col-xs-6">
                <div class="info-box info-box-new-style">
                    <span class="info-box-icon bg-aqua"><i class="fas fa-box"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Products Synced</span>
                        <span class="info-box-number" id="products-count">0</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-xs-6">
                <div class="info-box info-box-new-style">
                    <span class="info-box-icon bg-green"><i class="fas fa-shopping-cart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Orders Imported</span>
                        <span class="info-box-number" id="orders-count">0</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-xs-6">
                <div class="info-box info-box-new-style">
                    <span class="info-box-icon bg-yellow"><i class="fas fa-tags"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Categories</span>
                        <span class="info-box-number" id="categories-count">0</span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-xs-6">
                <div class="info-box info-box-new-style">
                    <span class="info-box-icon bg-red"><i class="fas fa-sync-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Last Sync</span>
                        <span class="info-box-number" id="last-sync" style="font-size: 14px;">Never</span>
                    </div>
                </div>
            </div>
        </div>
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#api_configuration" data-toggle="tab">
                            <i class="fas fa-plug"></i> <strong>API Configuration</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#webhook_setup" data-toggle="tab">
                            <i class="fas fa-bell"></i> <strong>Webhook Setup</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#sync_operations" data-toggle="tab">
                            <i class="fas fa-sync-alt"></i> <strong>Synchronization</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#order_status_mapping" data-toggle="tab">
                            <i class="fas fa-exchange-alt"></i> <strong>Order Status Mapping</strong>
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <!-- API Configuration Tab -->
                    <div class="tab-pane active" id="api_configuration">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h4><i class="icon fa fa-info"></i> Multi-Location WooCommerce Configuration</h4>
                                    Configure your WooCommerce REST API credentials separately for each business location to enable synchronization between your WooCommerce stores and IsleBooks POS.
                                </div>
                                
                                <!-- Location Selection -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="location_selector">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                Business Location <span class="text-red">*</span>
                                            </label>
                                            <select class="form-control" id="location_selector" name="location_id">
                                                <option value="">Select Location...</option>
                                                @if(isset($business_locations))
                                                    @foreach($business_locations as $location)
                                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <span class="help-block">Select a location to configure its WooCommerce integration</span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
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
                                
                                <!-- Configuration Form (shown after location selection) -->
                                <div id="location-config-form" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h4><i class="fa fa-cogs"></i> API Configuration for: <span id="selected_location_name">-</span></h4>
                                        </div>
                                    </div>
                                    
                                    <form id="woocommerce-config-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="store-url">
                                                    <i class="fas fa-store mr-1"></i>
                                                    Store URL <span class="text-red">*</span>
                                                </label>
                                                <input type="url" 
                                                       class="form-control" 
                                                       id="store-url" 
                                                       name="store_url"
                                                       placeholder="https://your-store.com" 
                                                       required>
                                                <span class="help-block">Full URL of your WooCommerce store</span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="consumer-key">
                                                    <i class="fas fa-key mr-1"></i>
                                                    Consumer Key <span class="text-red">*</span>
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="consumer-key" 
                                                       name="consumer_key"
                                                       placeholder="ck_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" 
                                                       required>
                                                <span class="help-block">WooCommerce REST API Consumer Key</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="consumer-secret">
                                                    <i class="fas fa-lock mr-1"></i>
                                                    Consumer Secret <span class="text-red">*</span>
                                                </label>
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="consumer-secret" 
                                                       name="consumer_secret"
                                                       placeholder="cs_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" 
                                                       required>
                                                <span class="help-block">WooCommerce REST API Consumer Secret</span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="api-version">
                                                    <i class="fas fa-code-branch mr-1"></i>
                                                    API Version
                                                </label>
                                                <select class="form-control" id="api-version" name="api_version">
                                                    <option value="wc/v3">WC/v3 (Latest)</option>
                                                    <option value="wc/v2">WC/v2</option>
                                                    <option value="wc/v1">WC/v1</option>
                                                </select>
                                                <span class="help-block">WooCommerce API version to use</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="sync-interval">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Sync Interval (Minutes)
                                                </label>
                                                <input type="number" class="form-control" id="sync-interval" name="sync_interval_minutes" 
                                                       value="15" min="5" max="1440">
                                                <span class="help-block">How often to sync automatically</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <br>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" id="auto-sync" name="auto_sync" value="1" class="input-icheck">
                                                        Enable automatic synchronization
                                                    </label>
                                                </div>
                                                <span class="help-block">Automatically sync data when enabled</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <br>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" id="is-active" name="is_active" value="1" class="input-icheck">
                                                        Enable for this Location
                                                    </label>
                                                </div>
                                                <span class="help-block">Activate WooCommerce for this location</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h5><i class="fa fa-check-square-o"></i> Sync Options</h5>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="sync-products" name="sync_products" value="1" class="input-icheck"> Products
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="sync-orders" name="sync_orders" value="1" class="input-icheck"> Orders
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="sync-inventory" name="sync_inventory" value="1" class="input-icheck"> Inventory
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="sync-customers" name="sync_customers" value="1" class="input-icheck"> Customers
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <!-- Webhook URL -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Webhook URL (Copy this to WooCommerce):</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="webhook-url-display" readonly>
                                                    <div class="input-group-btn">
                                                        <button type="button" class="btn btn-default" id="copy-webhook-url">
                                                            <i class="fa fa-copy"></i> Copy
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="help-block">Add this URL to your WooCommerce webhook settings.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <br>
                                            <!-- Empty space for alignment -->
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="button" class="btn btn-primary" id="test-connection">
                                            <i class="fas fa-plug mr-1"></i>
                                            Test Connection
                                        </button>
                                        <button type="button" class="btn btn-success" id="save-config">
                                            <i class="fas fa-save mr-1"></i>
                                            Save Configuration
                                        </button>
                                        <button type="button" class="btn btn-warning" id="trigger-sync">
                                            <i class="fas fa-sync mr-1"></i>
                                            Sync Now
                                        </button>
                                    </div>
                                </form>
                                
                                <div id="connection-status" class="callout callout-warning" style="display: none;">
                                    <h4>Connection Status</h4>
                                    <p id="connection-message">Testing connection...</p>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Webhook Setup Tab -->
                    <div class="tab-pane" id="webhook_setup">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h4><i class="icon fa fa-info"></i> Location-Specific Webhook Configuration</h4>
                                    <p>Configure webhooks separately for each business location. First select a location from the API Configuration tab, then use these location-specific webhook URLs.</p>
                                    <p><strong>Note:</strong> Webhooks work independently of API configuration - you can set them up without API credentials.</p>
                                </div>
                                
                                <!-- Location-specific webhook section (shown only when location selected) -->
                                <div id="webhook-location-section" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h4><i class="fa fa-map-marker-alt"></i> Webhook URLs for: <span id="webhook-selected-location-name">-</span></h4>
                                            <p class="text-muted">Use these URLs in your WooCommerce webhook settings for this specific location.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Modern Location-Specific Webhook</h5>
                                            <div class="form-group">
                                                <label>All Events (Recommended):</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="location-webhook-url" readonly>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default copy-webhook" type="button" title="Copy URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                                <small class="help-block">Single webhook URL that handles all order events for this location</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h5>Legacy Event-Specific Webhooks</h5>
                                            <div class="form-group">
                                                <label>Order Created:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control webhook-url" id="webhook-order-created" readonly>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default copy-webhook" type="button" title="Copy URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Order Updated:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control webhook-url" id="webhook-order-updated" readonly>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default copy-webhook" type="button" title="Copy URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Order Deleted:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control webhook-url" id="webhook-order-deleted" readonly>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default copy-webhook" type="button" title="Copy URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label>Order Restored:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control webhook-url" id="webhook-order-restored" readonly>
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-default copy-webhook" type="button" title="Copy URL">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <!-- Location-specific webhook security -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h5>Webhook Security for This Location</h5>
                                            <div class="form-group">
                                                <label for="webhook-secret">Location Webhook Secret:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" id="webhook-secret" name="webhook_secret" 
                                                           placeholder="Enter or generate secret key">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-warning" type="button" id="generate-secret">
                                                            <i class="fas fa-random"></i> Generate
                                                        </button>
                                                    </span>
                                                </div>
                                                <span class="help-block">Location-specific webhook secret for secure communication</span>
                                            </div>
                                            
                                            <div class="form-group">
                                                <button type="button" class="btn btn-success" id="save-webhook-secret">
                                                    <i class="fas fa-save mr-1"></i>
                                                    Save Webhook Secret
                                                </button>
                                                <button type="button" class="btn btn-info" id="test-webhooks">
                                                    <i class="fas fa-flask mr-1"></i>
                                                    Test Webhooks
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <h5>Setup Instructions</h5>
                                            <div class="callout callout-info">
                                                <ol>
                                                    <li>Select a location from API Configuration tab</li>
                                                    <li>Copy the location-specific webhook URLs</li>
                                                    <li>Go to WooCommerce → Settings → Advanced → Webhooks</li>
                                                    <li>Create new webhooks for each event type</li>
                                                    <li>Paste the respective URLs and secret key</li>
                                                    <li>Set delivery URL format to JSON</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Message when no location selected -->
                                <div id="webhook-no-location" class="alert alert-warning">
                                    <h4><i class="icon fa fa-warning"></i> No Location Selected</h4>
                                    <p>Please go to the <strong>API Configuration</strong> tab and select a location first to see location-specific webhook URLs.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Synchronization Operations Tab -->
                    <div class="tab-pane" id="sync_operations">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h4><i class="icon fa fa-info"></i> Location-Based Synchronization</h4>
                                    <p>Sync data between WooCommerce and IsleBooks POS for specific business locations. Select a location from the API Configuration tab first.</p>
                                    <p><strong>Note:</strong> Each location syncs independently with its configured WooCommerce store.</p>
                                </div>
                                
                                <!-- Location-specific sync section -->
                                <div id="sync-location-section" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h4><i class="fa fa-map-marker-alt"></i> Synchronization for: <span id="sync-selected-location-name">-</span></h4>
                                            <p class="text-muted">Perform sync operations for this specific location's WooCommerce integration.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="small-box bg-aqua">
                                                <div class="inner text-center">
                                                    <h4><i class="fas fa-box fa-2x"></i></h4>
                                                    <h4>Product Sync</h4>
                                                    <p>Import products, variations, categories, and pricing for this location</p>
                                                </div>
                                                <div class="small-box-footer">
                                                    <button class="btn btn-block btn-default" id="location-sync-products">
                                                        <i class="fas fa-download"></i> Start Product Sync
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-4">
                                            <div class="small-box bg-green">
                                                <div class="inner text-center">
                                                    <h4><i class="fas fa-shopping-cart fa-2x"></i></h4>
                                                    <h4>Order Sync</h4>
                                                    <p>Import orders, customers, and payment details for this location</p>
                                                </div>
                                                <div class="small-box-footer">
                                                    <button class="btn btn-block btn-default" id="location-sync-orders">
                                                        <i class="fas fa-download"></i> Start Order Sync
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-4">
                                            <div class="small-box bg-red">
                                                <div class="inner text-center">
                                                    <h4><i class="fas fa-warehouse fa-2x"></i></h4>
                                                    <h4>Inventory Sync</h4>
                                                    <p>Sync stock levels and inventory data for this location</p>
                                                </div>
                                                <div class="small-box-footer">
                                                    <button class="btn btn-block btn-default" id="location-sync-inventory">
                                                        <i class="fas fa-download"></i> Start Inventory Sync
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="small-box bg-purple">
                                                <div class="inner text-center">
                                                    <h4><i class="fas fa-users fa-2x"></i></h4>
                                                    <h4>Customer Sync</h4>
                                                    <p>Import customers and contact details for this location</p>
                                                </div>
                                                <div class="small-box-footer">
                                                    <button class="btn btn-block btn-default" id="location-sync-customers">
                                                        <i class="fas fa-download"></i> Start Customer Sync
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-4">
                                            <div class="small-box bg-yellow">
                                                <div class="inner text-center">
                                                    <h4><i class="fas fa-sync-alt fa-2x"></i></h4>
                                                    <h4>Full Sync</h4>
                                                    <p>Complete synchronization of all enabled data types</p>
                                                </div>
                                                <div class="small-box-footer">
                                                    <button class="btn btn-block btn-default" id="location-full-sync">
                                                        <i class="fas fa-sync-alt"></i> Start Full Sync
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-4">
                                            <div class="small-box bg-gray">
                                                <div class="inner text-center">
                                                    <h4><i class="fas fa-history fa-2x"></i></h4>
                                                    <h4>Sync Status</h4>
                                                    <p>View detailed sync history and statistics</p>
                                                </div>
                                                <div class="small-box-footer">
                                                    <button class="btn btn-block btn-default" id="view-sync-status">
                                                        <i class="fas fa-chart-bar"></i> View Status
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Message when no location selected -->
                                <div id="sync-no-location" class="alert alert-warning">
                                    <h4><i class="icon fa fa-warning"></i> No Location Selected</h4>
                                    <p>Please go to the <strong>API Configuration</strong> tab and select a location first to perform sync operations for that location.</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="box box-primary">
                                            <div class="box-header with-border">
                                                <h3 class="box-title">
                                                    <i class="fas fa-history"></i>
                                                    Synchronization Log
                                                </h3>
                                            </div>
                                            <div class="box-body">
                                                <div id="sync-log" style="height: 200px; overflow-y: auto; background: #f4f4f4; padding: 10px; border-radius: 3px; font-family: monospace; font-size: 12px;">
                                                    <p class="text-muted">No synchronization activities yet. Start a sync operation to see logs here.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Status Mapping Tab -->
                    <div class="tab-pane" id="order_status_mapping">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h4><i class="icon fa fa-info"></i> Bidirectional Order Status Mapping</h4>
                                    <p>Configure how WooCommerce order statuses map to POS invoice types for each business location. This enables automatic creation and synchronization of orders between WooCommerce and your POS system.</p>
                                    <p><strong>Key Features:</strong></p>
                                    <ul>
                                        <li><strong>Auto Draft Creation:</strong> Orders marked "on hold" in WooCommerce automatically create draft sales in POS</li>
                                        <li><strong>Auto Finalization:</strong> Orders marked "processing/completed" in WooCommerce automatically finalize POS sales</li>
                                        <li><strong>Bidirectional Sync:</strong> POS sale changes update WooCommerce order statuses</li>
                                        <li><strong>Real-time Webhooks:</strong> Instant synchronization via webhooks</li>
                                    </ul>
                                </div>
                                
                                <!-- Location-specific status mapping section -->
                                <div id="status-mapping-section" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h4><i class="fa fa-map-marker-alt"></i> Order Status Mapping for: <span id="status-mapping-location-name">-</span></h4>
                                            <p class="text-muted">Configure status mapping and bidirectional sync settings for this location.</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Status Mapping Configuration -->
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="box box-primary">
                                                <div class="box-header with-border">
                                                    <h3 class="box-title">
                                                        <i class="fas fa-exchange-alt"></i>
                                                        Status Mapping Configuration
                                                    </h3>
                                                </div>
                                                <div class="box-body">
                                                    <p class="help-block">Map each WooCommerce order status to the corresponding POS invoice type:</p>
                                                    
                                                    <form id="status-mapping-form">
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th width="40%">WooCommerce Status</th>
                                                                        <th width="40%">POS Invoice Type</th>
                                                                        <th width="20%">Description</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="status-mapping-table">
                                                                    <!-- Status mapping rows will be populated via JavaScript -->
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <button type="submit" class="btn btn-primary" id="save-status-mapping">
                                                                    <i class="fas fa-save"></i> Save Status Mapping
                                                                </button>
                                                                <button type="button" class="btn btn-default" id="test-status-mapping">
                                                                    <i class="fas fa-flask"></i> Test Mapping
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <!-- Preset Templates -->
                                            <div class="box box-info">
                                                <div class="box-header with-border">
                                                    <h3 class="box-title">
                                                        <i class="fas fa-templates"></i>
                                                        Quick Setup Presets
                                                    </h3>
                                                </div>
                                                <div class="box-body">
                                                    <p class="help-block">Choose a preset configuration:</p>
                                                    <div id="preset-templates">
                                                        <!-- Preset buttons will be populated via JavaScript -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Bidirectional Settings -->
                                            <div class="box box-warning">
                                                <div class="box-header with-border">
                                                    <h3 class="box-title">
                                                        <i class="fas fa-cogs"></i>
                                                        Bidirectional Sync Settings
                                                    </h3>
                                                </div>
                                                <div class="box-body">
                                                    <form id="bidirectional-settings-form">
                                                        <div class="form-group">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" id="enable_bidirectional_sync" checked>
                                                                    Enable bidirectional synchronization
                                                                </label>
                                                            </div>
                                                            <span class="help-block">Allow two-way sync between WooCommerce and POS</span>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" id="auto_finalize_pos_sales" checked>
                                                                    Auto-finalize POS sales
                                                                </label>
                                                            </div>
                                                            <span class="help-block">Automatically finalize POS sales when WooCommerce order is processing/completed</span>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" id="auto_update_woo_status" checked>
                                                                    Auto-update WooCommerce status
                                                                </label>
                                                            </div>
                                                            <span class="help-block">Update WooCommerce order status when POS sale changes</span>
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <div class="checkbox">
                                                                <label>
                                                                    <input type="checkbox" id="create_draft_on_webhook" checked>
                                                                    Auto-create draft sales from webhooks
                                                                </label>
                                                            </div>
                                                            <span class="help-block">Automatically create draft sales when WooCommerce orders are received</span>
                                                        </div>
                                                        
                                                        <button type="submit" class="btn btn-warning btn-sm">
                                                            <i class="fas fa-save"></i> Save Settings
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Webhook Event History -->
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="box box-success">
                                                <div class="box-header with-border">
                                                    <h3 class="box-title">
                                                        <i class="fas fa-history"></i>
                                                        Recent Webhook Events
                                                    </h3>
                                                    <div class="box-tools pull-right">
                                                        <button type="button" class="btn btn-success btn-sm" id="refresh-webhook-history">
                                                            <i class="fas fa-refresh"></i> Refresh
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="box-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-condensed">
                                                            <thead>
                                                                <tr>
                                                                    <th>Event Type</th>
                                                                    <th>WooCommerce Order ID</th>
                                                                    <th>POS Transaction ID</th>
                                                                    <th>Status</th>
                                                                    <th>Processed At</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="webhook-history-table">
                                                                <!-- Webhook history will be populated via JavaScript -->
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Location Not Selected Message -->
                                <div id="status-mapping-no-location" class="alert alert-warning">
                                    <h4><i class="icon fa fa-warning"></i> No Location Selected</h4>
                                    <p>Please select a business location from the <strong>API Configuration</strong> tab first to configure order status mapping.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
// WooCommerce Configuration JavaScript  
var currentLocationId = null; // Global scope for access by all functions

$(document).ready(function() {
    
    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    // Load initial statistics and settings
    loadStatistics();
    
    // Load settings for selected location
    $('#load_location_settings').click(function() {
        var locationId = $('#location_selector').val();
        console.log('Load button clicked, locationId:', locationId);
        
        if (!locationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        // Show loading indicator
        var btn = $(this);
        var originalText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        
        loadLocationSettings(locationId).always(function() {
            btn.html(originalText).prop('disabled', false);
        });
    });
    
    function loadLocationSettings(locationId) {
        console.log('Loading settings for location:', locationId);
        
        return $.ajax({
            url: '/woocommerce/api/location-settings/' + locationId,
            method: 'GET',
            success: function(response) {
                console.log('Location settings response:', response);
                
                // Set global location ID FIRST before calling other functions
                currentLocationId = locationId;
                var locationName = $('#location_selector option:selected').text();
                $('#selected_location_name').text(locationName);
                
                if (response.settings) {
                    // Populate form with existing settings
                    $('#store-url').val(response.settings.woocommerce_app_url || '');
                    $('#consumer-key').val(response.settings.woocommerce_consumer_key || '');
                    $('#consumer-secret').val(''); // Don't show existing secret
                    $('#sync-interval').val(response.settings.sync_interval_minutes || 15);
                    
                    // Update checkboxes
                    $('#auto-sync').prop('checked', response.settings.enable_auto_sync).iCheck('update');
                    $('#is-active').prop('checked', response.settings.is_active).iCheck('update');
                    $('#sync-products').prop('checked', response.settings.sync_products).iCheck('update');
                    $('#sync-orders').prop('checked', response.settings.sync_orders).iCheck('update');
                    $('#sync-inventory').prop('checked', response.settings.sync_inventory).iCheck('update');
                    $('#sync-customers').prop('checked', response.settings.sync_customers).iCheck('update');
                } else {
                    // Reset form for new location
                    $('#location-config-form input[type="text"], #location-config-form input[type="password"], #location-config-form input[type="number"]').val('');
                    $('#sync-interval').val(15);
                    
                    // Set default checkboxes
                    $('#auto-sync, #is-active, #sync-products, #sync-orders, #sync-inventory, #sync-customers').prop('checked', true).iCheck('update');
                }
                
                // Update webhook URL
                $('#webhook-url-display').val('{{ url("woocommerce/webhook") }}/' + locationId);
                
                // Update all webhook-related sections
                updateWebhookUrls(locationId, locationName);
                updateSyncLocation(locationName);
                updateOrderStatusMapping(locationName);
                
                // Show the form
                $('#location-config-form').show();
            },
            error: function(xhr, status, error) {
                console.error('Location settings error:', xhr.responseText);
                var errorMsg = 'Failed to load location settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 403) {
                    errorMsg = 'Access denied. Please check your permissions.';
                } else if (xhr.status === 404) {
                    errorMsg = 'Location settings endpoint not found.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error. Please try again or check logs.';
                }
                toastr.error(errorMsg);
            }
        });
    }
    
    // Save settings
    $('#save-config').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        var formData = {
            location_id: currentLocationId,
            woocommerce_app_url: $('#store-url').val(),
            woocommerce_consumer_key: $('#consumer-key').val(),
            woocommerce_consumer_secret: $('#consumer-secret').val(),
            sync_interval_minutes: $('#sync-interval').val(),
            enable_auto_sync: $('#auto-sync').is(':checked') ? 1 : 0,
            is_active: $('#is-active').is(':checked') ? 1 : 0,
            sync_products: $('#sync-products').is(':checked') ? 1 : 0,
            sync_orders: $('#sync-orders').is(':checked') ? 1 : 0,
            sync_inventory: $('#sync-inventory').is(':checked') ? 1 : 0,
            sync_customers: $('#sync-customers').is(':checked') ? 1 : 0
        };
        
        $.ajax({
            url: '/woocommerce/api/location-settings',
            method: 'POST',
            data: formData,
            success: function(response) {
                toastr.success('Location settings saved successfully!');
                loadStatistics(); // Refresh stats
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to save settings.';
                toastr.error(message);
            }
        });
    });
    
    // Test connection
    $('#test-connection').click(function() {
        var formData = {
            woocommerce_app_url: $('#store-url').val(),
            woocommerce_consumer_key: $('#consumer-key').val(),
            woocommerce_consumer_secret: $('#consumer-secret').val()
        };
        
        if (!formData.woocommerce_app_url || !formData.woocommerce_consumer_key || !formData.woocommerce_consumer_secret) {
            toastr.error('Please fill in all API credentials first.');
            return;
        }
        
        var button = $(this);
        var originalText = button.html();
        button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Testing...');
        $('#connection-status').hide();
        
        $.ajax({
            url: '/woocommerce/api/test-connection',
            method: 'POST',
            data: formData,
            success: function(response) {
                $('#connection-status')
                    .removeClass('callout-danger')
                    .addClass('callout-success')
                    .show();
                $('#connection-message').text('Connection successful! ' + response.message);
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Connection failed';
                $('#connection-status')
                    .removeClass('callout-success')
                    .addClass('callout-danger')
                    .show();
                $('#connection-message').text('Connection failed: ' + message);
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Trigger sync
    $('#trigger-sync').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        $.ajax({
            url: '/woocommerce/api/location-settings/' + currentLocationId + '/sync',
            method: 'POST',
            success: function(response) {
                toastr.success('Sync started successfully for selected location!');
                loadStatistics(); // Refresh stats
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to start sync.';
                toastr.error(message);
            }
        });
    });
    
    // Copy webhook URL
    $('#copy-webhook-url').click(function() {
        $('#webhook-url-display').select();
        document.execCommand('copy');
        toastr.success('Webhook URL copied to clipboard!');
    });
    
    // Location-based sync operations with real-time progress
    $('#location-sync-products, #location-sync-orders, #location-sync-inventory, #location-sync-customers, #location-full-sync').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        var syncType = $(this).attr('id').replace('location-sync-', '');
        if (syncType === 'full') syncType = 'all';
        
        var btn = $(this);
        var originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        btn.prop('disabled', true);
        
        // Get location name for display
        var locationName = $('#location_selector option:selected').text() || 'Selected Location';
        
        $.ajax({
            url: '/woocommerce/api/location-settings/' + currentLocationId + '/sync',
            method: 'POST',
            data: {
                sync_type: syncType
            },
            success: function(response) {
                if (response.success && response.sync_id) {
                    // Show real-time progress modal
                    syncProgressModal.show(response.sync_id, locationName, syncType);
                    
                    toastr.success('Sync started! View real-time progress in the modal.');
                    loadStatistics(); // Refresh stats
                    addSyncLog('Started ' + syncType + ' sync for location (ID: ' + response.sync_id + ')');
                } else {
                    toastr.success('Sync started successfully for selected location!');
                    loadStatistics();
                    addSyncLog('Started ' + syncType + ' sync for location');
                }
            },
            error: function(xhr) {
                var message = xhr.responseJSON?.message || 'Failed to start sync.';
                toastr.error(message);
                addSyncLog('Failed to start ' + syncType + ' sync: ' + message);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // View sync status
    $('#view-sync-status').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        $.ajax({
            url: '/woocommerce/api/location-settings/' + currentLocationId,
            method: 'GET',
            success: function(response) {
                if (response.settings) {
                    var stats = response.settings;
                    var statusHtml = '<h4>Sync Statistics for Location</h4>';
                    statusHtml += '<p><strong>Last Sync:</strong> ' + (stats.last_successful_sync_at || 'Never') + '</p>';
                    statusHtml += '<p><strong>Products Synced:</strong> ' + (stats.total_products_synced || 0) + '</p>';
                    statusHtml += '<p><strong>Orders Synced:</strong> ' + (stats.total_orders_synced || 0) + '</p>';
                    statusHtml += '<p><strong>Customers Synced:</strong> ' + (stats.total_customers_synced || 0) + '</p>';
                    statusHtml += '<p><strong>Inventory Items Synced:</strong> ' + (stats.total_inventory_synced || 0) + '</p>';
                    statusHtml += '<p><strong>Failed Syncs:</strong> ' + (stats.failed_syncs_count || 0) + '</p>';
                    if (stats.last_sync_error) {
                        statusHtml += '<p><strong>Last Error:</strong> <span class="text-danger">' + stats.last_sync_error + '</span></p>';
                    }
                    
                    // Show in a modal or alert
                    toastr.info(statusHtml, 'Sync Status', {
                        timeOut: 10000,
                        extendedTimeOut: 5000,
                        allowHtml: true
                    });
                }
            },
            error: function() {
                toastr.error('Failed to load sync status.');
            }
        });
    });
    
    // Copy webhook URLs
    $('.copy-webhook').click(function() {
        const input = $(this).closest('.input-group').find('input');
        input.select();
        document.execCommand('copy');
        toastr.info('Webhook URL copied to clipboard!');
    });
    
    // Generate and save location-specific webhook secret
    $('#generate-secret').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>');
        btn.prop('disabled', true);
        
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let result = '';
        for (let i = 0; i < 40; i++) {
            result += characters.charAt(Math.floor(Math.random() * characters.length));
        }
        
        // Show the generated secret immediately
        $('#webhook-secret').val(result);
        $('#webhook-secret').data('has-secret', false); // Mark as not saved yet
        
        toastr.success('New webhook secret generated! Click "Save Webhook Secret" to save it.');
        btn.html(originalText);
        btn.prop('disabled', false);
    });
    
    // Save location-specific webhook secret
    $('#save-webhook-secret').click(function() {
        if (!currentLocationId) {
            toastr.error('Please select a location first.');
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        const webhookSecret = $('#webhook-secret').val();
        
        // Skip validation if showing masked secret
        if (webhookSecret === '••••••••••••••••••••') {
            toastr.info('Webhook secret is already saved for this location.');
            return;
        }
        
        if (!webhookSecret || webhookSecret.length < 20) {
            toastr.error('Webhook secret must be at least 20 characters long');
            return;
        }
        
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
        btn.prop('disabled', true);
        
        // Save to location settings
        var formData = {
            location_id: currentLocationId,
            webhook_secret: webhookSecret
        };
        
        $.ajax({
            url: '/woocommerce/api/location-settings',
            method: 'POST',
            data: formData,
            success: function(response) {
                toastr.success('Webhook secret saved successfully for this location!');
                // Reload webhook secret to show masked value
                loadLocationWebhookSecret(currentLocationId);
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to save webhook secret';
                toastr.error(errorMsg);
            },
            complete: function() {
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });
    
    // Test webhooks
    $('#test-webhooks').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Testing...');
        btn.prop('disabled', true);
        
        setTimeout(function() {
            toastr.info('Webhook test completed. Check logs for details.');
            btn.html(originalText);
            btn.prop('disabled', false);
        }, 2000);
    });
    
    // Sync operations
    $('#sync-products, #sync-orders, #full-sync').click(function() {
        const btn = $(this);
        const originalText = btn.html();
        const syncType = btn.attr('id').replace('sync-', '').replace('-sync', '');
        
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Syncing...');
        btn.prop('disabled', true);
        
        // Add log entry
        addSyncLog('Starting ' + syncType + ' synchronization...');
        
        $.ajax({
            url: '/woocommerce/api/sync',
            method: 'POST',
            data: {
                sync_type: syncType,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    addSyncLog(response.message);
                    loadStatistics(); // Refresh stats
                    toastr.success(response.message);
                } else {
                    addSyncLog('Sync failed: ' + response.message);
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Synchronization failed';
                addSyncLog('Sync failed: ' + errorMsg);
                toastr.error(errorMsg);
            },
            complete: function() {
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });
    
    // View webhook logs
    $('#webhook-logs').click(function() {
        loadSyncLogs();
        toastr.info('Sync logs loaded');
    });
});

// Load statistics from API
function loadStatistics() {
    $.ajax({
        url: '/woocommerce/api/stats',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                $('#products-count').text(response.data.products_count);
                $('#orders-count').text(response.data.orders_count);
                $('#categories-count').text(response.data.categories_count);
                $('#last-sync').text(response.data.last_sync);
            }
        },
        error: function(xhr) {
            console.error('Failed to load statistics');
        }
    });
}

// Helper functions for location-based UI updates
function updateWebhookUrls(locationId, locationName) {
    // Update webhook section header
    $('#webhook-selected-location-name').text(locationName);
    
    // Update modern location-specific webhook URL
    $('#location-webhook-url').val('{{ url("woocommerce/webhook") }}/' + locationId);
    
    // Update legacy webhook URLs
    $('#webhook-order-created').val('{{ url("webhook/order-created") }}/' + locationId);
    $('#webhook-order-updated').val('{{ url("webhook/order-updated") }}/' + locationId);
    $('#webhook-order-deleted').val('{{ url("webhook/order-deleted") }}/' + locationId);
    $('#webhook-order-restored').val('{{ url("webhook/order-restored") }}/' + locationId);
    
    // Show webhook section and hide no-location message
    $('#webhook-location-section').show();
    $('#webhook-no-location').hide();
    
    // Load webhook secret for this location
    loadLocationWebhookSecret(locationId);
}

function updateSyncLocation(locationName) {
    // Update sync section header
    $('#sync-selected-location-name').text(locationName);
    
    // Show sync section and hide no-location message
    $('#sync-location-section').show();
    $('#sync-no-location').hide();
}

function updateOrderStatusMapping(locationName) {
    // Update order status mapping section header
    $('#status-mapping-location-name').text(locationName);
    
    // Show status mapping section and hide no-location message
    $('#status-mapping-section').show();
    $('#status-mapping-no-location').hide();
    
    // Load the actual settings for this location
    // currentLocationId should be set by now in loadLocationSettings()
    console.log('updateOrderStatusMapping called with currentLocationId:', currentLocationId);
    loadOrderStatusMapping();
}

function loadLocationWebhookSecret(locationId) {
    $.ajax({
        url: '/woocommerce/api/location-settings/' + locationId,
        method: 'GET',
        success: function(response) {
            if (response.settings && response.settings.webhook_secret) {
                // Show masked webhook secret
                $('#webhook-secret').val('••••••••••••••••••••');
                $('#webhook-secret').attr('placeholder', 'Current secret (hidden)');
                $('#webhook-secret').data('has-secret', true);
            } else {
                // No secret set
                $('#webhook-secret').val('');
                $('#webhook-secret').attr('placeholder', 'Enter or generate secret key');
                $('#webhook-secret').data('has-secret', false);
            }
        },
        error: function() {
            console.error('Failed to load webhook secret for location');
        }
    });
}

// Load webhook settings independently
function loadWebhookSettings() {
    $.ajax({
        url: '/woocommerce/api/webhook-secret',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                if (response.data.has_secret) {
                    $('#webhook-secret').val(response.data.webhook_secret);
                    $('#webhook-secret').attr('placeholder', 'Current secret (hidden)');
                } else {
                    $('#webhook-secret').attr('placeholder', 'Enter or generate secret key');
                }
            }
        },
        error: function(xhr) {
            console.error('Failed to load webhook settings');
        }
    });
}

// Load sync logs
function loadSyncLogs() {
    $.ajax({
        url: '/woocommerce/api/sync-logs',
        method: 'GET',
        success: function(response) {
            if (response.success && response.data.length > 0) {
                $('#sync-log').empty();
                response.data.forEach(function(log) {
                    addSyncLog('[' + log.time + '] ' + log.type + ' ' + log.operation);
                });
            }
        },
        error: function(xhr) {
            console.error('Failed to load sync logs');
        }
    });
}

// Add entry to sync log
function addSyncLog(message) {
    const timestamp = new Date().toLocaleTimeString();
    const logEntry = '<p class="mb-1">[' + timestamp + '] ' + message + '</p>';
    $('#sync-log').append(logEntry);
    
    // Scroll to bottom
    const syncLog = document.getElementById('sync-log');
    syncLog.scrollTop = syncLog.scrollHeight;
    
    // Remove "no activities" message
    $('#sync-log .text-muted').remove();
}

// Order Status Mapping JavaScript Functions
function loadOrderStatusMapping() {
    console.log('loadOrderStatusMapping called, currentLocationId:', currentLocationId);
    
    if (!currentLocationId) {
        console.log('No currentLocationId, showing no-location message');
        $('#status-mapping-no-location').show();
        $('#status-mapping-section').hide();
        return;
    }
    
    console.log('Loading order status mapping for location:', currentLocationId);
    $('#status-mapping-no-location').hide();
    $('#status-mapping-section').show();
    $('#status-mapping-location-name').text($('#location_selector option:selected').text());
    
    $.ajax({
        url: '/woocommerce/order-status-mapping/settings',
        method: 'GET',
        data: { location_id: currentLocationId },
        success: function(response) {
            console.log('Order status mapping response:', response);
            if (response.success) {
                // Update bidirectional sync settings
                $('#enable_bidirectional_sync').prop('checked', response.bidirectional_settings.enable_bidirectional_sync || true).iCheck('update');
                $('#auto_finalize_pos_sales').prop('checked', response.bidirectional_settings.auto_finalize_pos_sales || true).iCheck('update');
                $('#auto_update_woo_status').prop('checked', response.bidirectional_settings.auto_update_woo_status || true).iCheck('update');
                $('#create_draft_on_webhook').prop('checked', response.bidirectional_settings.create_draft_on_webhook || true).iCheck('update');
                
                // Populate status mapping table
                const mappingToUse = response.current_mapping || getDefaultStatusMapping();
                console.log('Using status mapping:', mappingToUse);
                populateStatusMappingTable(mappingToUse);
                
                // Load preset templates
                populatePresetTemplates(response.presets || []);
                
                // Load webhook history
                loadWebhookEventHistory();
            }
        },
        error: function(xhr) {
            console.error('Failed to load order status mapping settings');
            toastr.error('Failed to load status mapping settings');
        }
    });
}

function populateStatusMappingTable(statusMapping) {
    console.log('populateStatusMappingTable called with:', statusMapping);
    const tableBody = $('#status-mapping-table');
    console.log('Table body element found:', tableBody.length);
    tableBody.empty();
    
    const wooStatuses = [
        { key: 'pending', name: 'Pending Payment', color: 'warning' },
        { key: 'on-hold', name: 'On Hold', color: 'info' },
        { key: 'processing', name: 'Processing', color: 'primary' },
        { key: 'completed', name: 'Completed', color: 'success' },
        { key: 'cancelled', name: 'Cancelled', color: 'danger' },
        { key: 'refunded', name: 'Refunded', color: 'secondary' },
        { key: 'failed', name: 'Failed', color: 'danger' }
    ];
    
    const posInvoiceTypes = [
        { key: 'draft', name: 'Draft' },
        { key: 'proforma', name: 'Proforma' },
        { key: 'final', name: 'Final Invoice' },
        { key: 'quotation', name: 'Quotation' }
    ];
    
    wooStatuses.forEach(function(wooStatus) {
        const mappedType = statusMapping[wooStatus.key] || 'draft';
        
        const row = `
            <tr>
                <td>
                    <span class="badge badge-${wooStatus.color}">
                        ${wooStatus.name}
                    </span>
                </td>
                <td>
                    <select class="form-control status-mapping-select" 
                            data-woo-status="${wooStatus.key}">
                        ${posInvoiceTypes.map(type => 
                            `<option value="${type.key}" ${mappedType === type.key ? 'selected' : ''}>
                                ${type.name}
                            </option>`
                        ).join('')}
                    </select>
                </td>
                <td>
                    <small class="text-muted">
                        ${getStatusMappingDescription(wooStatus.key, mappedType)}
                    </small>
                </td>
            </tr>
        `;
        
        tableBody.append(row);
    });
    
    console.log('Added', wooStatuses.length, 'rows to status mapping table');
}

function getStatusMappingDescription(wooStatus, posType) {
    const descriptions = {
        'pending': {
            'draft': 'Creates draft sale when payment is pending',
            'proforma': 'Creates proforma when payment is pending',
            'final': 'Creates final invoice when payment is pending',
            'quotation': 'Creates quotation when payment is pending'
        },
        'on-hold': {
            'draft': 'Creates draft sale when order is on hold (Recommended)',
            'proforma': 'Creates proforma when order is on hold',
            'final': 'Creates final invoice when order is on hold',
            'quotation': 'Creates quotation when order is on hold'
        },
        'processing': {
            'draft': 'Creates draft sale when order is processing',
            'proforma': 'Creates proforma when processing (Recommended)',
            'final': 'Creates final invoice when processing',
            'quotation': 'Creates quotation when processing'
        },
        'completed': {
            'draft': 'Creates draft sale when order is completed',
            'proforma': 'Creates proforma when completed',
            'final': 'Creates final invoice when completed (Recommended)',
            'quotation': 'Creates quotation when completed'
        },
        'cancelled': {
            'draft': 'Creates draft sale when order is cancelled',
            'proforma': 'Creates proforma when cancelled',
            'final': 'Creates final invoice when cancelled',
            'quotation': 'Creates quotation when cancelled'
        },
        'refunded': {
            'draft': 'Creates draft sale when order is refunded',
            'proforma': 'Creates proforma when refunded',
            'final': 'Creates final invoice when refunded',
            'quotation': 'Creates quotation when refunded'
        },
        'failed': {
            'draft': 'Creates draft sale when order fails',
            'proforma': 'Creates proforma when order fails',
            'final': 'Creates final invoice when order fails',
            'quotation': 'Creates quotation when order fails'
        }
    };
    
    return descriptions[wooStatus]?.[posType] || 'Custom mapping configuration';
}

function populatePresetTemplates(presets) {
    const presetsContainer = $('#preset-templates');
    presetsContainer.empty();
    
    // Add default presets
    const defaultPresets = [
        { name: 'Conservative', description: 'Only completed orders become final invoices', preset: 'conservative' },
        { name: 'Standard', description: 'Processing orders become proforma, completed become final', preset: 'standard' },
        { name: 'Aggressive', description: 'Pending orders become proforma, processing become final', preset: 'aggressive' }
    ];
    
    defaultPresets.forEach(function(preset) {
        const button = `
            <button type="button" class="btn btn-sm btn-default apply-preset mb-2" 
                    data-preset="${preset.preset}" 
                    style="width: 100%;">
                <strong>${preset.name}</strong><br>
                <small>${preset.description}</small>
            </button>
        `;
        presetsContainer.append(button);
    });
    
    // Add custom presets from database
    if (presets && presets.length > 0) {
        presetsContainer.append('<hr><small class="text-muted">Custom Presets:</small>');
        presets.forEach(function(preset) {
            const button = `
                <button type="button" class="btn btn-sm btn-info apply-custom-preset mb-2" 
                        data-preset-id="${preset.id}" 
                        style="width: 100%;">
                    <strong>${preset.name}</strong><br>
                    <small>${preset.description}</small>
                </button>
            `;
            presetsContainer.append(button);
        });
    }
}

function getDefaultStatusMapping() {
    return {
        'pending': 'draft',
        'on-hold': 'draft',
        'processing': 'proforma',
        'completed': 'final',
        'cancelled': 'draft',
        'refunded': 'draft',
        'failed': 'draft'
    };
}

// Apply status mapping preset
$(document).on('click', '.apply-preset', function() {
    const preset = $(this).data('preset');
    let statusMapping = {};
    
    switch (preset) {
        case 'conservative':
            statusMapping = {
                'pending': 'draft',
                'on-hold': 'draft',
                'processing': 'draft',
                'completed': 'final',
                'cancelled': 'draft',
                'refunded': 'draft',
                'failed': 'draft'
            };
            break;
            
        case 'aggressive':
            statusMapping = {
                'pending': 'proforma',
                'on-hold': 'proforma',
                'processing': 'final',
                'completed': 'final',
                'cancelled': 'draft',
                'refunded': 'draft',
                'failed': 'draft'
            };
            break;
            
        case 'standard':
        default:
            statusMapping = getDefaultStatusMapping();
            break;
    }
    
    populateStatusMappingTable(statusMapping);
    toastr.success(preset.charAt(0).toUpperCase() + preset.slice(1) + ' preset applied successfully');
});

// Save status mapping configuration
$('#save-status-mapping').click(function(e) {
    e.preventDefault();
    if (!currentLocationId) {
        toastr.error('Please select a location first');
        return;
    }
    
    const button = $(this);
    const originalText = button.html();
    button.html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    button.prop('disabled', true);
    
    // Collect status mapping from table
    const statusMapping = {};
    $('.status-mapping-select').each(function() {
        const wooStatus = $(this).data('woo-status');
        const posType = $(this).val();
        statusMapping[wooStatus] = posType;
    });
    
    // Collect bidirectional settings
    const enableBidirectionalSync = $('#enable-bidirectional-sync').prop('checked');
    const autoFinalizePOSSales = $('#auto-finalize-pos-sales').prop('checked');
    
    $.ajax({
        url: '/woocommerce/order-status-mapping/settings',
        method: 'POST',
        data: {
            location_id: currentLocationId,
            status_mapping: statusMapping,
            enable_bidirectional_sync: enableBidirectionalSync,
            auto_finalize_pos_sales: autoFinalizePOSSales,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Status mapping configuration saved successfully');
                
                // Show save confirmation
                $('#status-mapping-last-saved').text('Last saved: ' + new Date().toLocaleString());
                
                // Refresh webhook history
                loadWebhookEventHistory();
            } else {
                toastr.error(response.message || 'Failed to save configuration');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to save status mapping configuration';
            toastr.error(errorMsg);
        },
        complete: function() {
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
});

function loadWebhookEventHistory() {
    if (!currentLocationId) {
        return;
    }
    
    $.ajax({
        url: '/woocommerce/order-status-mapping/webhook-history',
        method: 'GET',
        data: { location_id: currentLocationId },
        success: function(response) {
            if (response.success) {
                populateWebhookHistoryTable(response.data);
            }
        },
        error: function(xhr) {
            console.error('Failed to load webhook event history');
        }
    });
}

function populateWebhookHistoryTable(events) {
    const tableBody = $('#webhook-history-table');
    tableBody.empty();
    
    if (!events || events.length === 0) {
        tableBody.append(`
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <em>No webhook events recorded yet</em>
                </td>
            </tr>
        `);
        return;
    }
    
    events.forEach(function(event) {
        const statusBadge = getEventStatusBadge(event.status);
        const row = `
            <tr>
                <td>
                    <span class="badge badge-info">${event.event_type}</span>
                </td>
                <td>
                    <code>${event.order_id}</code>
                </td>
                <td>
                    <span class="badge badge-secondary">${event.woo_status}</span>
                    →
                    <span class="badge badge-primary">${event.pos_invoice_type}</span>
                </td>
                <td>
                    ${statusBadge}
                </td>
                <td>
                    <small class="text-muted">
                        ${new Date(event.processed_at).toLocaleString()}
                    </small>
                </td>
                <td>
                    ${event.error_message ? 
                        `<small class="text-danger">${event.error_message}</small>` : 
                        '<small class="text-success">Success</small>'
                    }
                </td>
            </tr>
        `;
        
        tableBody.append(row);
    });
}

function getEventStatusBadge(status) {
    const statusConfig = {
        'pending': { class: 'warning', text: 'Pending' },
        'processing': { class: 'info', text: 'Processing' },
        'completed': { class: 'success', text: 'Completed' },
        'failed': { class: 'danger', text: 'Failed' }
    };
    
    const config = statusConfig[status] || { class: 'secondary', text: status };
    return `<span class="badge badge-${config.class}">${config.text}</span>`;
}

// Tab switching handler for Order Status Mapping
$(document).on('shown.bs.tab', 'a[href="#order-status-mapping"]', function() {
    loadOrderStatusMapping();
});

// Test status mapping functionality
$('#test-status-mapping').click(function() {
    if (!currentLocationId) {
        toastr.error('Please select a location first');
        return;
    }
    
    const button = $(this);
    const originalText = button.html();
    button.html('<i class="fas fa-spinner fa-spin"></i> Testing...');
    button.prop('disabled', true);
    
    $.ajax({
        url: '/woocommerce/order-status-mapping/test',
        method: 'POST',
        data: {
            location_id: currentLocationId,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Status mapping test completed successfully');
                
                // Show test results
                const testResults = response.data;
                let resultHtml = '<h5>Test Results:</h5><ul>';
                testResults.forEach(function(result) {
                    resultHtml += `<li><strong>${result.woo_status}</strong> → <em>${result.pos_type}</em>: ${result.result}</li>`;
                });
                resultHtml += '</ul>';
                
                // You could show this in a modal or alert
                toastr.info('Check console for detailed test results');
                console.log('Status Mapping Test Results:', testResults);
            } else {
                toastr.error(response.message || 'Status mapping test failed');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to test status mapping';
            toastr.error(errorMsg);
        },
        complete: function() {
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
});

// Handle inventory sync button
$('#location-sync-inventory').click(function() {
    if (!currentLocationId) {
        toastr.error('Please select a location first');
        return;
    }
    
    const button = $(this);
    const originalText = button.html();
    button.html('<i class="fas fa-spinner fa-spin"></i> Syncing Stock...');
    button.prop('disabled', true);
    
    addSyncLog('Starting inventory sync for location...');
    
    $.ajax({
        url: `/woocommerce/location-settings/${currentLocationId}/sync-stock`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            sync_zero_stock: true,
            allow_backorders: false
        },
        success: function(response) {
            if (response.success) {
                addSyncLog(`✓ Stock sync completed successfully!`);
                addSyncLog(`  → Synced: ${response.synced_items} items`);
                addSyncLog(`  → Failed: ${response.failed_items} items`);
                addSyncLog(`  → Duration: ${response.duration_ms}ms`);
                
                if (response.errors && response.errors.length > 0) {
                    response.errors.forEach(function(error) {
                        addSyncLog(`  ✗ Error: ${error.product_name || 'Unknown Product'} - ${error.error}`);
                    });
                }
                
                toastr.success(`Stock sync completed! ${response.synced_items} items updated.`);
            } else {
                addSyncLog(`✗ Stock sync failed: ${response.message}`);
                toastr.error(response.message || 'Stock sync failed');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Stock sync failed';
            addSyncLog(`✗ Stock sync error: ${errorMsg}`);
            toastr.error(errorMsg);
        },
        complete: function() {
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
});
</script>

@include('woocommerce::components.sync_progress_modal')

@endsection