@extends('layouts.app')
@section('title', 'API Keys')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>API Keys Management
        <small>Manage your API keys for secure third-party integrations</small>
    </h1>
    <hr class="header-row"/>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => 'API Keys'])
        @slot('tool')
            <div class="box-tools">
                <a class="btn btn-block btn-primary" href="{{ route('api-keys.create') }}">
                    <i class="fa fa-plus"></i> @lang('messages.add') API Key
                </a>
            </div>
        @endslot
        
        <!-- Show new API key if just created -->
        @if(session('new_api_key'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-check"></i> API Key Created Successfully!</h4>
                <p><strong>Important:</strong> This is the only time you will see this API key. Copy it now and store it securely:</p>
                <div class="well well-sm" style="background: #f4f4f4; font-family: monospace; word-break: break-all;">
                    <strong>{{ session('new_api_key') }}</strong>
                    <button type="button" class="btn btn-xs btn-default pull-right" onclick="copyToClipboard('{{ session('new_api_key') }}')">
                        <i class="fa fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            {{ session()->forget('new_api_key') }}
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="api_keys_table">
                <thead>
                    <tr>
                        <th>@lang('messages.name')</th>
                        <th>API Key</th>
                        <th>Abilities</th>
                        <th>Status</th>
                        <th>Rate Limit</th>
                        <th>Last Used</th>
                        <th>Created By</th>
                        <th>Expires</th>
                        <th>@lang('messages.actions')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <!-- API Documentation Info Box -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-info-circle"></i> API Usage Information</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Authentication Methods</h4>
                            <p>Include your API key in requests using any of these methods:</p>
                            <ul>
                                <li><strong>Authorization Header:</strong> <code>Authorization: Bearer YOUR_API_KEY</code></li>
                                <li><strong>X-API-Key Header:</strong> <code>X-API-Key: YOUR_API_KEY</code></li>
                                <li><strong>Query Parameter:</strong> <code>?api_key=YOUR_API_KEY</code></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h4>Rate Limiting</h4>
                            <p>API requests are limited based on your key configuration. Headers will indicate:</p>
                            <ul>
                                <li><code>X-RateLimit-Limit:</code> Total requests allowed per minute</li>
                                <li><code>X-RateLimit-Remaining:</code> Requests remaining in current window</li>
                                <li><code>X-RateLimit-Reset:</code> When the rate limit resets</li>
                            </ul>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <a href="{{ route('api-docs') }}" class="btn btn-info" target="_blank">
                                <i class="fa fa-book"></i> View Complete API Documentation
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize DataTable
        var api_keys_table = $('#api_keys_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("api-keys.index") }}',
            columns: [
                {data: 'name', name: 'name'},
                {data: 'display_key', name: 'display_key', orderable: false, searchable: false},
                {data: 'abilities', name: 'abilities', orderable: false, searchable: false},
                {data: 'is_active', name: 'is_active'},
                {data: 'rate_limit_per_minute', name: 'rate_limit_per_minute'},
                {data: 'last_used_at', name: 'last_used_at'},
                {data: 'created_by', name: 'created_by', orderable: false},
                {data: 'expires_at', name: 'expires_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        // Handle revoke API key
        $(document).on('click', '.revoke-api-key', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            
            swal({
                title: "@lang('messages.sure')",
                text: "This will revoke the API key and prevent it from being used for API requests.",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willRevoke) => {
                if (willRevoke) {
                    $.ajax({
                        method: 'POST',
                        url: url,
                        dataType: 'json',
                        data: {
                            '_token': '{{ csrf_token() }}',
                            '_method': 'PUT'
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                api_keys_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });

        // Handle activate API key
        $(document).on('click', '.activate-api-key', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            
            $.ajax({
                method: 'POST',
                url: url,
                dataType: 'json',
                data: {
                    '_token': '{{ csrf_token() }}',
                    '_method': 'PUT'
                },
                success: function(result) {
                    if (result.success) {
                        toastr.success(result.msg);
                        api_keys_table.ajax.reload();
                    } else {
                        toastr.error(result.msg);
                    }
                }
            });
        });

        // Handle delete API key
        $(document).on('click', '.delete-api-key', function(e) {
            e.preventDefault();
            var url = $(this).data('href');
            
            swal({
                title: "@lang('messages.sure')",
                text: "This will permanently delete the API key and all its usage logs. This action cannot be undone.",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: url,
                        dataType: 'json',
                        data: {
                            '_token': '{{ csrf_token() }}'
                        },
                        success: function(result) {
                            if (result.success) {
                                toastr.success(result.msg);
                                api_keys_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });

    // Copy to clipboard function
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                toastr.success('API key copied to clipboard!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                toastr.success('API key copied to clipboard!');
            } catch (err) {
                console.error('Could not copy text: ', err);
                toastr.error('Could not copy to clipboard. Please copy manually.');
            }
            textArea.remove();
        }
    }
</script>
@endsection