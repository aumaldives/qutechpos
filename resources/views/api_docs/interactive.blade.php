@extends('layouts.app')
@section('title', 'Interactive API Documentation')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>Interactive API Documentation
        <small>Explore and test IsleBooks API endpoints</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{route('api-keys.index')}}">API Management</a></li>
        <li class="active">Interactive Documentation</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-code"></i> Swagger API Explorer
                    </h3>
                    <div class="box-tools pull-right">
                        <a href="{{ route('api-docs.playground') }}" class="btn btn-success btn-sm">
                            <i class="fa fa-play"></i> API Playground
                        </a>
                        <a href="{{ route('api-docs.code-examples') }}" class="btn btn-info btn-sm">
                            <i class="fa fa-code"></i> Code Examples
                        </a>
                        <a href="{{ route('api-docs') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-book"></i> Static Documentation
                        </a>
                    </div>
                </div>
                <div class="box-body" style="padding: 0;">
                    <!-- Swagger UI Container -->
                    <div id="swagger-ui"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Instructions -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info collapsed-box">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-info-circle"></i> How to Use Interactive Documentation
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="fa fa-key"></i> Authentication</h4>
                            <ol>
                                <li>Click the <strong>"Authorize"</strong> button at the top</li>
                                <li>Enter your API key in the <code>X-API-Key</code> field</li>
                                <li>Click <strong>"Authorize"</strong> to authenticate</li>
                            </ol>

                            <h4><i class="fa fa-play"></i> Testing Endpoints</h4>
                            <ol>
                                <li>Browse endpoints by category (Products, Contacts, etc.)</li>
                                <li>Click <strong>"Try it out"</strong> on any endpoint</li>
                                <li>Fill in required parameters and request body</li>
                                <li>Click <strong>"Execute"</strong> to test the API</li>
                                <li>View the response and copy the curl command</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h4><i class="fa fa-shield"></i> API Key Management</h4>
                            <p>You can create and manage API keys from the <a href="{{ route('api-keys.index') }}">API Keys page</a>.</p>
                            <p>Make sure your API key has the required permissions for the endpoints you want to test.</p>

                            <h4><i class="fa fa-book"></i> Additional Resources</h4>
                            <ul>
                                <li><a href="{{ route('api-docs.playground') }}">API Playground</a> - Test with your own data</li>
                                <li><a href="{{ route('api-docs.code-examples') }}">Code Examples</a> - Implementation guides</li>
                                <li><a href="{{ route('api-docs') }}">Static Documentation</a> - Complete API reference</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<!-- Swagger UI CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
<script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>

<script>
$(document).ready(function() {
    // Initialize Swagger UI
    const ui = SwaggerUIBundle({
        url: '{{ $openApiSpecUrl }}',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout",
        tryItOutEnabled: true,
        requestInterceptor: function(request) {
            // Add custom headers or modify requests if needed
            console.log('Making request:', request);
            return request;
        },
        responseInterceptor: function(response) {
            // Log responses for debugging
            console.log('Received response:', response);
            return response;
        },
        onComplete: function() {
            console.log('Swagger UI loaded successfully');
        },
        onFailure: function(error) {
            console.error('Failed to load Swagger UI:', error);
        },
        docExpansion: 'list', // Can be 'none', 'list', or 'full'
        filter: true, // Enable filtering
        showRequestDuration: true,
        showCommonExtensions: true,
        showExtensions: true
    });

    // Custom styling for Swagger UI
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            #swagger-ui {
                font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            }
            
            #swagger-ui .topbar { 
                background-color: #3c8dbc; 
                border-bottom: 1px solid #357ca5;
            }
            
            #swagger-ui .topbar .link {
                color: #fff;
            }
            
            #swagger-ui .swagger-ui .btn.authorize {
                background-color: #00a65a;
                border-color: #008d4c;
            }
            
            #swagger-ui .swagger-ui .btn.authorize:hover {
                background-color: #008d4c;
            }
            
            #swagger-ui .opblock.opblock-get .opblock-summary {
                border-left: 4px solid #3c8dbc;
            }
            
            #swagger-ui .opblock.opblock-post .opblock-summary {
                border-left: 4px solid #00a65a;
            }
            
            #swagger-ui .opblock.opblock-put .opblock-summary {
                border-left: 4px solid #f39c12;
            }
            
            #swagger-ui .opblock.opblock-delete .opblock-summary {
                border-left: 4px solid #dd4b39;
            }
            
            /* Fix readability issues */
            #swagger-ui .swagger-ui .info .title {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .scheme-container {
                background: #fafafa !important;
                border: 1px solid #ddd !important;
            }
            
            #swagger-ui .swagger-ui .scheme-container .schemes > label {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .opblock .opblock-summary-description {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .opblock .opblock-summary-path {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .opblock-description-wrapper p,
            #swagger-ui .swagger-ui .opblock-description-wrapper h4,
            #swagger-ui .swagger-ui .opblock-external-docs-wrapper h4 {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .parameter__name {
                color: #333 !important;
                font-weight: bold !important;
            }
            
            #swagger-ui .swagger-ui .parameter__type {
                color: #666 !important;
            }
            
            #swagger-ui .swagger-ui .parameter__deprecated {
                color: #999 !important;
            }
            
            #swagger-ui .swagger-ui .parameter__in {
                color: #666 !important;
            }
            
            #swagger-ui .swagger-ui .tab li {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .response-col_status {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .response-col_description {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .responses-inner h4,
            #swagger-ui .swagger-ui .responses-inner h5 {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .model-box {
                background: rgba(0,0,0,.05) !important;
            }
            
            #swagger-ui .swagger-ui .model .property {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .model .property.primitive {
                color: #666 !important;
            }
            
            #swagger-ui .swagger-ui .btn {
                background: #fff !important;
                color: #333 !important;
                border: 1px solid #ccc !important;
            }
            
            #swagger-ui .swagger-ui .btn.execute {
                background: #3c8dbc !important;
                color: #fff !important;
                border-color: #357ca5 !important;
            }
            
            #swagger-ui .swagger-ui .btn.try-out {
                background: #00a65a !important;
                color: #fff !important;
                border-color: #008d4c !important;
            }
            
            #swagger-ui .swagger-ui .btn.cancel {
                background: #dd4b39 !important;
                color: #fff !important;
                border-color: #d73925 !important;
            }
            
            /* Fix input and textarea contrast */
            #swagger-ui .swagger-ui input[type=text],
            #swagger-ui .swagger-ui input[type=password],
            #swagger-ui .swagger-ui input[type=search],
            #swagger-ui .swagger-ui input[type=email],
            #swagger-ui .swagger-ui input[type=url],
            #swagger-ui .swagger-ui input[type=number],
            #swagger-ui .swagger-ui textarea,
            #swagger-ui .swagger-ui select {
                background: #fff !important;
                color: #333 !important;
                border: 1px solid #ccc !important;
            }
            
            /* Fix code blocks */
            #swagger-ui .swagger-ui .highlight-code {
                background: #f8f8f8 !important;
                color: #333 !important;
            }
            
            /* Fix microlight syntax highlighting */
            #swagger-ui .microlight {
                background: #f8f8f8 !important;
                color: #333 !important;
            }
            
            /* Headers and general text */
            #swagger-ui .swagger-ui h1,
            #swagger-ui .swagger-ui h2,
            #swagger-ui .swagger-ui h3,
            #swagger-ui .swagger-ui h4,
            #swagger-ui .swagger-ui h5,
            #swagger-ui .swagger-ui p,
            #swagger-ui .swagger-ui span,
            #swagger-ui .swagger-ui div,
            #swagger-ui .swagger-ui td,
            #swagger-ui .swagger-ui th,
            #swagger-ui .swagger-ui label {
                color: #333 !important;
            }
            
            /* Make sure dark text shows on light backgrounds */
            #swagger-ui .swagger-ui .opblock .opblock-section-header {
                background: rgba(255,255,255,.8) !important;
            }
            
            #swagger-ui .swagger-ui .opblock .opblock-section-header h4 {
                color: #333 !important;
            }
            
            /* Additional comprehensive text fixes */
            #swagger-ui .swagger-ui .renderedMarkdown p,
            #swagger-ui .swagger-ui .renderedMarkdown li,
            #swagger-ui .swagger-ui .renderedMarkdown code,
            #swagger-ui .swagger-ui .markdown p,
            #swagger-ui .swagger-ui .markdown li,
            #swagger-ui .swagger-ui .markdown code {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .opblock-summary {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .opblock-summary-method {
                color: #fff !important;
            }
            
            #swagger-ui .swagger-ui .opblock-summary-path a {
                color: #333 !important;
            }
            
            #swagger-ui .swagger-ui .opblock-summary .opblock-summary-description {
                color: #666 !important;
            }
            
            /* Force all text in opblock to be dark */
            #swagger-ui .swagger-ui .opblock * {
                color: #333 !important;
            }
            
            /* But keep method labels white on colored background */
            #swagger-ui .swagger-ui .opblock .opblock-summary-method {
                color: #fff !important;
            }
            
            /* Parameters section */
            #swagger-ui .swagger-ui .parameters-col_description p,
            #swagger-ui .swagger-ui .parameters-col_description div {
                color: #333 !important;
            }
            
            /* Response section */
            #swagger-ui .swagger-ui .responses-table td {
                color: #333 !important;
            }
            
            /* JSON/YAML examples */
            #swagger-ui .swagger-ui .highlight pre,
            #swagger-ui .swagger-ui .highlight code {
                background: #f8f8f8 !important;
                color: #333 !important;
            }
            
            /* Info section at top */
            #swagger-ui .swagger-ui .info .description,
            #swagger-ui .swagger-ui .info .description p,
            #swagger-ui .swagger-ui .info .description div,
            #swagger-ui .swagger-ui .info .version {
                color: #333 !important;
            }
            
            /* Operations list */
            #swagger-ui .swagger-ui .operation-tag-content {
                color: #333 !important;
            }
            
            /* Global override for any remaining white text */
            #swagger-ui .swagger-ui * {
                color: #333 !important;
            }
            
            /* Restore white text only where needed (method badges, buttons) */
            #swagger-ui .swagger-ui .opblock-summary-method,
            #swagger-ui .swagger-ui .btn.execute,
            #swagger-ui .swagger-ui .btn.try-out,
            #swagger-ui .swagger-ui .btn.cancel,
            #swagger-ui .swagger-ui .btn.authorize {
                color: #fff !important;
            }
            
            /* Ensure table headers are visible */
            #swagger-ui .swagger-ui table thead th {
                color: #333 !important;
                background: #f5f5f5 !important;
            }
            
            /* Make sure all content sections have proper background */
            #swagger-ui .swagger-ui .wrapper {
                background: #fff !important;
            }
            
            #swagger-ui .swagger-ui .opblock {
                background: #fff !important;
                border: 1px solid #e3e3e3 !important;
            }
            
            /* Fix any remaining visibility issues */
            #swagger-ui .swagger-ui .no-margin,
            #swagger-ui .swagger-ui .opblock-body {
                background: #fff !important;
            }
        `)
        .appendTo('head');
});
</script>
@endsection