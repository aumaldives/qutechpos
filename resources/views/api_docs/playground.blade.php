@extends('layouts.app')
@section('title', 'API Playground')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>API Playground
        <small>Test API endpoints with your own data</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{route('api-keys.index')}}">API Management</a></li>
        <li class="active">API Playground</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    <!-- API Key Selection -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-key"></i> Select API Key for Testing
                    </h3>
                </div>
                <div class="box-body">
                    @if($apiKeys->count() > 0)
                        <div class="form-group">
                            <label for="api-key-select">Choose API Key:</label>
                            <select id="api-key-select" class="form-control select2" style="width: 100%;">
                                <option value="">Select an API key...</option>
                                @foreach($apiKeys as $apiKey)
                                    <option value="{{ $apiKey->key }}" 
                                            data-abilities="{{ implode(', ', $apiKey->abilities ?? []) }}"
                                            data-name="{{ $apiKey->name }}">
                                        {{ $apiKey->name }} ({{ $apiKey->display_key }})
                                        @if($apiKey->expires_at) 
                                            - Expires: {{ $apiKey->expires_at->format('M d, Y') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="api-key-info" class="alert alert-info" style="display: none;">
                            <h4><i class="fa fa-info-circle"></i> Selected API Key Information</h4>
                            <p><strong>Name:</strong> <span id="key-name"></span></p>
                            <p><strong>Abilities:</strong> <span id="key-abilities"></span></p>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h4><i class="fa fa-warning"></i> No API Keys Found</h4>
                            <p>You need to create an API key first. <a href="{{ route('api-keys.create') }}">Create one now</a>.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- API Testing Interface -->
    <div class="row">
        <div class="col-md-6">
            <!-- Request Builder -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-send"></i> Build Request
                    </h3>
                </div>
                <div class="box-body">
                    <form id="api-test-form">
                        <!-- Method and Endpoint -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Method</label>
                                    <select id="request-method" class="form-control">
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Endpoint</label>
                                    <div class="input-group">
                                        <div class="input-group-addon">{{ url('/api/v1') }}/</div>
                                        <input type="text" id="request-endpoint" class="form-control" placeholder="products" value="products">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Endpoint Buttons -->
                        <div class="form-group">
                            <label>Quick Select:</label>
                            <div class="btn-group" style="width: 100%;">
                                <button type="button" class="btn btn-default quick-endpoint" data-endpoint="products">Products</button>
                                <button type="button" class="btn btn-default quick-endpoint" data-endpoint="contacts">Contacts</button>
                                <button type="button" class="btn btn-default quick-endpoint" data-endpoint="transactions">Transactions</button>
                                <button type="button" class="btn btn-default quick-endpoint" data-endpoint="reports/dashboard">Dashboard</button>
                                <button type="button" class="btn btn-default quick-endpoint" data-endpoint="reports/sales-analytics">Sales Analytics</button>
                            </div>
                        </div>

                        <!-- Headers -->
                        <div class="form-group">
                            <label>Additional Headers (JSON)</label>
                            <textarea id="request-headers" class="form-control" rows="3" placeholder='{"Content-Type": "application/json"}'></textarea>
                        </div>

                        <!-- Request Body -->
                        <div class="form-group" id="request-body-group" style="display: none;">
                            <label>Request Body (JSON)</label>
                            <textarea id="request-body" class="form-control" rows="6" placeholder='{"name": "Product Name", "type": "single"}'></textarea>
                        </div>

                        <!-- Query Parameters -->
                        <div class="form-group">
                            <label>Query Parameters</label>
                            <div id="query-params">
                                <div class="row query-param-row">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control param-key" placeholder="Key">
                                    </div>
                                    <div class="col-md-7">
                                        <input type="text" class="form-control param-value" placeholder="Value">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-success btn-sm add-param">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Send Button -->
                        <div class="form-group">
                            <button type="submit" id="send-request" class="btn btn-primary btn-lg" disabled>
                                <i class="fa fa-send"></i> Send Request
                            </button>
                            <button type="button" id="clear-request" class="btn btn-default btn-lg">
                                <i class="fa fa-refresh"></i> Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Response Display -->
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-code"></i> Response
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" id="copy-response" class="btn btn-xs btn-default" disabled>
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div id="response-loading" style="display: none; text-align: center; padding: 20px;">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <p>Sending request...</p>
                    </div>
                    
                    <div id="response-container" style="display: none;">
                        <!-- Response Status -->
                        <div class="form-group">
                            <label>Status:</label>
                            <span id="response-status" class="label"></span>
                            <span id="response-time" class="pull-right text-muted"></span>
                        </div>

                        <!-- Response Headers -->
                        <div class="form-group">
                            <label>Headers:</label>
                            <pre id="response-headers" style="max-height: 100px; overflow-y: auto; font-size: 11px;"></pre>
                        </div>

                        <!-- Response Body -->
                        <div class="form-group">
                            <label>Body:</label>
                            <pre id="response-body" style="max-height: 300px; overflow-y: auto;"></pre>
                        </div>
                    </div>

                    <div id="response-placeholder" class="text-center text-muted" style="padding: 40px;">
                        <i class="fa fa-code fa-3x"></i>
                        <p>Response will appear here after sending a request</p>
                    </div>
                </div>
            </div>

            <!-- cURL Command -->
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-terminal"></i> cURL Command
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" id="copy-curl" class="btn btn-xs btn-default" disabled>
                            <i class="fa fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <pre id="curl-command" style="background: #f4f4f4; border: 1px solid #ddd; padding: 10px; font-size: 12px; white-space: pre-wrap; word-wrap: break-word;">Build a request to see the cURL command</pre>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    let selectedApiKey = '';

    // API Key selection
    $('#api-key-select').on('change', function() {
        selectedApiKey = $(this).val();
        const selectedOption = $(this).find(':selected');
        
        if (selectedApiKey) {
            $('#api-key-info').show();
            $('#key-name').text(selectedOption.data('name'));
            $('#key-abilities').text(selectedOption.data('abilities'));
            $('#send-request').prop('disabled', false);
            updateCurlCommand();
        } else {
            $('#api-key-info').hide();
            $('#send-request').prop('disabled', true);
        }
    });

    // Method change handler
    $('#request-method').on('change', function() {
        const method = $(this).val();
        if (['POST', 'PUT', 'PATCH'].includes(method)) {
            $('#request-body-group').show();
        } else {
            $('#request-body-group').hide();
        }
        updateCurlCommand();
    });

    // Endpoint and other changes
    $('#request-endpoint, #request-headers, #request-body').on('input', updateCurlCommand);

    // Quick endpoint buttons
    $('.quick-endpoint').on('click', function() {
        $('#request-endpoint').val($(this).data('endpoint'));
        updateCurlCommand();
    });

    // Query parameters
    $(document).on('click', '.add-param', function() {
        const newRow = `
            <div class="row query-param-row" style="margin-top: 5px;">
                <div class="col-md-4">
                    <input type="text" class="form-control param-key" placeholder="Key">
                </div>
                <div class="col-md-7">
                    <input type="text" class="form-control param-value" placeholder="Value">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-param">-</button>
                </div>
            </div>
        `;
        $('#query-params').append(newRow);
    });

    $(document).on('click', '.remove-param', function() {
        $(this).closest('.query-param-row').remove();
        updateCurlCommand();
    });

    $(document).on('input', '.param-key, .param-value', updateCurlCommand);

    // Form submission
    $('#api-test-form').on('submit', function(e) {
        e.preventDefault();
        if (!selectedApiKey) {
            toastr.error('Please select an API key first');
            return;
        }
        sendApiRequest();
    });

    // Clear request
    $('#clear-request').on('click', function() {
        $('#request-endpoint').val('products');
        $('#request-method').val('GET');
        $('#request-headers').val('');
        $('#request-body').val('');
        $('.param-key, .param-value').val('');
        $('#request-body-group').hide();
        clearResponse();
        updateCurlCommand();
    });

    // Copy buttons
    $('#copy-curl').on('click', function() {
        navigator.clipboard.writeText($('#curl-command').text()).then(function() {
            toastr.success('cURL command copied to clipboard');
        });
    });

    $('#copy-response').on('click', function() {
        navigator.clipboard.writeText($('#response-body').text()).then(function() {
            toastr.success('Response copied to clipboard');
        });
    });

    function buildApiUrl() {
        const endpoint = $('#request-endpoint').val().replace(/^\/+/, '');
        let url = '{{ url("/api/v1") }}/' + endpoint;
        
        // Add query parameters
        const params = [];
        $('.query-param-row').each(function() {
            const key = $(this).find('.param-key').val();
            const value = $(this).find('.param-value').val();
            if (key && value) {
                params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
            }
        });
        
        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        
        return url;
    }

    function buildHeaders() {
        const headers = {
            'Accept': 'application/json',
            'X-API-Key': selectedApiKey
        };
        
        // Add custom headers
        const customHeaders = $('#request-headers').val();
        if (customHeaders) {
            try {
                const parsed = JSON.parse(customHeaders);
                Object.assign(headers, parsed);
            } catch (e) {
                console.warn('Invalid JSON in headers');
            }
        }
        
        return headers;
    }

    function updateCurlCommand() {
        if (!selectedApiKey) {
            $('#curl-command').text('Build a request to see the cURL command');
            return;
        }

        const method = $('#request-method').val();
        const url = buildApiUrl();
        const headers = buildHeaders();
        
        let curl = `curl -X ${method} \\\n  '${url}'`;
        
        // Add headers
        for (const [key, value] of Object.entries(headers)) {
            curl += ` \\\n  -H '${key}: ${value}'`;
        }
        
        // Add body for POST/PUT requests
        if (['POST', 'PUT', 'PATCH'].includes(method)) {
            const body = $('#request-body').val();
            if (body) {
                curl += ` \\\n  -d '${body}'`;
            }
        }
        
        $('#curl-command').text(curl);
        $('#copy-curl').prop('disabled', false);
    }

    function sendApiRequest() {
        const method = $('#request-method').val();
        const url = buildApiUrl();
        const headers = buildHeaders();
        
        $('#response-loading').show();
        $('#response-container').hide();
        $('#response-placeholder').hide();
        
        const startTime = Date.now();
        
        const requestConfig = {
            method: method,
            headers: headers
        };
        
        // Add body for POST/PUT requests
        if (['POST', 'PUT', 'PATCH'].includes(method)) {
            const body = $('#request-body').val();
            if (body) {
                try {
                    JSON.parse(body); // Validate JSON
                    requestConfig.body = body;
                    headers['Content-Type'] = 'application/json';
                } catch (e) {
                    toastr.error('Invalid JSON in request body');
                    $('#response-loading').hide();
                    $('#response-placeholder').show();
                    return;
                }
            }
        }
        
        fetch(url, requestConfig)
            .then(response => {
                const responseTime = Date.now() - startTime;
                return response.text().then(text => ({
                    status: response.status,
                    statusText: response.statusText,
                    headers: response.headers,
                    body: text,
                    responseTime: responseTime
                }));
            })
            .then(result => {
                displayResponse(result);
            })
            .catch(error => {
                $('#response-loading').hide();
                $('#response-placeholder').hide();
                $('#response-container').show();
                
                $('#response-status').removeClass().addClass('label label-danger').text('Error');
                $('#response-time').text('');
                $('#response-headers').text('');
                $('#response-body').text('Network Error: ' + error.message);
                $('#copy-response').prop('disabled', false);
            });
    }

    function displayResponse(result) {
        $('#response-loading').hide();
        $('#response-placeholder').hide();
        $('#response-container').show();
        
        // Status
        const statusClass = result.status < 400 ? 'label-success' : 'label-danger';
        $('#response-status').removeClass().addClass('label ' + statusClass)
                            .text(result.status + ' ' + result.statusText);
        $('#response-time').text(result.responseTime + ' ms');
        
        // Headers
        const headersObj = {};
        for (let [key, value] of result.headers.entries()) {
            headersObj[key] = value;
        }
        $('#response-headers').text(JSON.stringify(headersObj, null, 2));
        
        // Body
        try {
            const jsonBody = JSON.parse(result.body);
            $('#response-body').text(JSON.stringify(jsonBody, null, 2));
        } catch (e) {
            $('#response-body').text(result.body);
        }
        
        $('#copy-response').prop('disabled', false);
    }

    function clearResponse() {
        $('#response-container').hide();
        $('#response-loading').hide();
        $('#response-placeholder').show();
        $('#copy-response').prop('disabled', true);
    }

    // Initialize
    updateCurlCommand();
});
</script>
@endsection