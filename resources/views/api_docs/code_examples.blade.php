@extends('layouts.app')
@section('title', 'API Code Examples')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>API Code Examples
        <small>Implementation guides for different programming languages</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{route('api-keys.index')}}">API Management</a></li>
        <li class="active">Code Examples</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    <!-- Endpoint Selector -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-cog"></i> Select Endpoint
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>HTTP Method</label>
                                <select id="method-select" class="form-control">
                                    <option value="GET" {{ $method == 'GET' ? 'selected' : '' }}>GET</option>
                                    <option value="POST" {{ $method == 'POST' ? 'selected' : '' }}>POST</option>
                                    <option value="PUT" {{ $method == 'PUT' ? 'selected' : '' }}>PUT</option>
                                    <option value="DELETE" {{ $method == 'DELETE' ? 'selected' : '' }}>DELETE</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>API Endpoint</label>
                                <div class="input-group">
                                    <div class="input-group-addon">{{ url('/api/v1') }}/</div>
                                    <input type="text" id="endpoint-input" class="form-control" value="{{ ltrim($endpoint, '/') }}" placeholder="products">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label>Quick Examples:</label>
                            <div class="btn-group" style="margin-left: 10px;">
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="GET" data-endpoint="products">List Products</button>
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="POST" data-endpoint="products">Create Product</button>
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="GET" data-endpoint="contacts">List Contacts</button>
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="GET" data-endpoint="transactions">List Transactions</button>
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="POST" data-endpoint="transactions/1/payments">Add Payment</button>
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="GET" data-endpoint="reports/dashboard">Dashboard Report</button>
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="POST" data-endpoint="hrm/check-in">Employee Check-In</button>
                                <button type="button" class="btn btn-sm btn-default quick-example" data-method="GET" data-endpoint="hrm/attendance">View Attendance</button>
                                <button type="button" class="btn btn-sm btn-success quick-example" data-method="GET" data-endpoint="pos/product-suggestions">POS Product Search</button>
                                <button type="button" class="btn btn-sm btn-success quick-example" data-method="GET" data-endpoint="pos/plastic-bags">POS Plastic Bags</button>
                                <button type="button" class="btn btn-sm btn-success quick-example" data-method="POST" data-endpoint="pos/sale">Create POS Sale</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Code Examples -->
    <div class="row">
        <!-- cURL -->
        <div class="col-md-6">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-terminal"></i> cURL
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default copy-code" data-target="curl-code">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <pre id="curl-code" class="code-block">{{ $examples['curl'] ?? 'Loading...' }}</pre>
                </div>
            </div>
        </div>

        <!-- JavaScript -->
        <div class="col-md-6">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-code"></i> JavaScript (Fetch API)
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default copy-code" data-target="js-code">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <pre id="js-code" class="code-block">{{ $examples['javascript'] ?? 'Loading...' }}</pre>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- PHP -->
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fab fa-php"></i> PHP (cURL)
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default copy-code" data-target="php-code">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <pre id="php-code" class="code-block">{{ $examples['php'] ?? 'Loading...' }}</pre>
                </div>
            </div>
        </div>

        <!-- Python -->
        <div class="col-md-6">
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fab fa-python"></i> Python (Requests)
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-xs btn-default copy-code" data-target="python-code">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <pre id="python-code" class="code-block">{{ $examples['python'] ?? 'Loading...' }}</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Guidelines -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-lightbulb-o"></i> Usage Guidelines
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4><i class="fa fa-key"></i> Authentication</h4>
                            <ul>
                                <li>Replace <code>YOUR_API_KEY</code> with your actual API key</li>
                                <li>Get your API key from <a href="{{ route('api-keys.index') }}">API Keys Management</a></li>
                                <li>Ensure your API key has the required permissions for the endpoint</li>
                                <li>You can use either <code>X-API-Key</code> header or <code>Authorization: Bearer</code></li>
                            </ul>

                            <h4><i class="fa fa-shield"></i> Security Best Practices</h4>
                            <ul>
                                <li>Never expose API keys in client-side code</li>
                                <li>Store API keys securely in environment variables</li>
                                <li>Use HTTPS for all API requests</li>
                                <li>Implement proper error handling</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4><i class="fa fa-exclamation-triangle"></i> Error Handling</h4>
                            <ul>
                                <li>Check HTTP status codes (200-299 for success)</li>
                                <li>Handle rate limiting (429 status code)</li>
                                <li>Parse error messages from response body</li>
                                <li>Implement retry logic for temporary failures</li>
                            </ul>

                            <h4><i class="fa fa-tachometer"></i> Rate Limits</h4>
                            <ul>
                                <li>Respect rate limits defined in your API key</li>
                                <li>Check <code>X-RateLimit-*</code> response headers</li>
                                <li>Implement exponential backoff for retries</li>
                                <li>Consider caching responses when appropriate</li>
                            </ul>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h4><i class="fa fa-book"></i> Common Request Patterns</h4>
                            
                            <div class="panel-group" id="request-patterns">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#request-patterns" href="#pagination">
                                                Pagination
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="pagination" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <p>Most list endpoints support pagination:</p>
                                            <code>GET /api/v1/products?page=2&per_page=25</code>
                                            <p>Response includes pagination metadata in <code>meta</code> object.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#request-patterns" href="#filtering">
                                                Filtering & Searching
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="filtering" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <p>Use query parameters for filtering:</p>
                                            <code>GET /api/v1/products?search=laptop&is_active=true&category_id=5</code>
                                            <p>Date ranges: <code>?date_from=2024-01-01&date_to=2024-01-31</code></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a data-toggle="collapse" data-parent="#request-patterns" href="#validation">
                                                Validation Errors
                                            </a>
                                        </h4>
                                    </div>
                                    <div id="validation" class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <p>422 status code with validation errors:</p>
                                            <pre>{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field must be valid."]
  }
}</pre>
                                        </div>
                                    </div>
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
$(document).ready(function() {
    // Copy code functionality
    $('.copy-code').on('click', function() {
        const targetId = $(this).data('target');
        const codeText = $('#' + targetId).text();
        
        navigator.clipboard.writeText(codeText).then(function() {
            toastr.success('Code copied to clipboard');
        }).catch(function() {
            toastr.error('Failed to copy code');
        });
    });

    // Quick example buttons
    $('.quick-example').on('click', function() {
        const method = $(this).data('method');
        const endpoint = $(this).data('endpoint');
        
        $('#method-select').val(method);
        $('#endpoint-input').val(endpoint);
        
        updateExamples();
    });

    // Update examples when method or endpoint changes
    $('#method-select, #endpoint-input').on('change input', function() {
        updateExamples();
    });

    function updateExamples() {
        const method = $('#method-select').val();
        const endpoint = $('#endpoint-input').val().replace(/^\/+/, '');
        
        // Show loading state
        $('.code-block').text('Loading...');
        
        // Update URL and fetch new examples
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('method', method);
        currentUrl.searchParams.set('endpoint', endpoint);
        
        // Fetch new examples via AJAX
        $.get('{{ route("api-docs.code-examples") }}', {
            method: method,
            endpoint: endpoint
        }).done(function(response) {
            // Update the page content
            const $tempDiv = $('<div>').html(response);
            
            $('#curl-code').text($tempDiv.find('#curl-code').text());
            $('#js-code').text($tempDiv.find('#js-code').text());
            $('#php-code').text($tempDiv.find('#php-code').text());
            $('#python-code').text($tempDiv.find('#python-code').text());
            
            // Update browser URL without refresh
            window.history.pushState({}, '', currentUrl);
        }).fail(function() {
            toastr.error('Failed to load examples');
            $('.code-block').text('Error loading examples');
        });
    }

    // Syntax highlighting for code blocks (if you want to add it later)
    $('.code-block').each(function() {
        $(this).addClass('hljs');
    });
});
</script>

<style>
.code-block {
    background-color: #f8f8f8;
    border: 1px solid #e7e7e7;
    border-radius: 4px;
    padding: 15px;
    font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 400px;
    overflow-y: auto;
}

.copy-code {
    cursor: pointer;
}

.quick-example {
    margin: 2px;
}

.panel-title a {
    text-decoration: none;
}

.panel-title a:hover {
    text-decoration: none;
}
</style>
@endsection