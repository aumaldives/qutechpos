@extends('layouts.app')
@section('title', 'API Key Usage - ' . $api_key->name)

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>API Key Usage Statistics
        <small>{{ $api_key->name }} ({{ $api_key->display_key }})</small>
    </h1>
    <hr class="header-row"/>
</section>

<!-- Main content -->
<section class="content">
    <!-- API Key Info -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-key"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">API Key</span>
                    <span class="info-box-number" style="font-size: 14px;">{{ $api_key->display_key }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon {{ $api_key->is_active && !$api_key->isExpired() ? 'bg-green' : 'bg-red' }}">
                    <i class="fa {{ $api_key->is_active && !$api_key->isExpired() ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text">Status</span>
                    <span class="info-box-number">
                        @if($api_key->is_active && !$api_key->isExpired())
                            Active
                        @elseif($api_key->isExpired())
                            Expired
                        @else
                            Revoked
                        @endif
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-tachometer-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Rate Limit</span>
                    <span class="info-box-number">{{ $api_key->rate_limit_per_minute }}/min</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Last Used</span>
                    <span class="info-box-number" style="font-size: 14px;">
                        {{ $api_key->last_used_at ? $api_key->last_used_at->diffForHumans() : 'Never' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Statistics -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-info', 'title' => 'Usage Statistics'])
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab_24h" data-toggle="tab">Last 24 Hours</a></li>
                        <li><a href="#tab_7d" data-toggle="tab">Last 7 Days</a></li>
                        <li><a href="#tab_30d" data-toggle="tab">Last 30 Days</a></li>
                        <li><a href="#tab_all" data-toggle="tab">All Time</a></li>
                    </ul>
                    <div class="tab-content">
                        @foreach($stats as $period => $stat)
                            <div class="tab-pane {{ $period == 'last_24h' ? 'active' : '' }}" id="tab_{{ str_replace(['last_', 'd', 'h'], ['', 'd', 'h'], $period) }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="description-block border-right">
                                            <h5 class="description-header text-blue">{{ number_format($stat['total_requests']) }}</h5>
                                            <span class="description-text">TOTAL REQUESTS</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="description-block border-right">
                                            <h5 class="description-header text-green">{{ number_format($stat['successful_requests']) }}</h5>
                                            <span class="description-text">SUCCESSFUL</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="description-block border-right">
                                            <h5 class="description-header text-red">{{ number_format($stat['failed_requests']) }}</h5>
                                            <span class="description-text">FAILED</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="description-block">
                                            <h5 class="description-header text-yellow">{{ $stat['avg_response_time'] ? number_format($stat['avg_response_time'], 0) . 'ms' : 'N/A' }}</h5>
                                            <span class="description-text">AVG RESPONSE TIME</span>
                                        </div>
                                    </div>
                                </div>

                                @if($stat['total_requests'] > 0)
                                    <div class="row" style="margin-top: 20px;">
                                        <div class="col-md-4">
                                            <h4>Response Status Breakdown</h4>
                                            @foreach($stat['status_breakdown'] as $status => $count)
                                                @php
                                                    $class = $status < 400 ? 'success' : 'danger';
                                                @endphp
                                                <span class="label label-{{ $class }}">{{ $status }}: {{ $count }}</span>
                                            @endforeach
                                        </div>
                                        <div class="col-md-4">
                                            <h4>Top Endpoints</h4>
                                            @foreach($stat['endpoint_breakdown']->take(5) as $endpoint => $count)
                                                <div><code>{{ $endpoint }}</code> <span class="label label-default">{{ $count }}</span></div>
                                            @endforeach
                                        </div>
                                        <div class="col-md-4">
                                            <h4>HTTP Methods</h4>
                                            @foreach($stat['method_breakdown'] as $method => $count)
                                                <span class="label label-info">{{ $method }}: {{ $count }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No API usage recorded for this time period.
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- API Key Details -->
    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'API Key Details'])
                <table class="table table-striped">
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td>{{ $api_key->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Key Prefix:</strong></td>
                        <td><code>{{ $api_key->key_prefix }}</code></td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td>{{ $api_key->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Created By:</strong></td>
                        <td>{{ $api_key->user ? $api_key->user->first_name . ' ' . $api_key->user->last_name : 'System' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Expires:</strong></td>
                        <td>
                            @if($api_key->expires_at)
                                {{ $api_key->expires_at->format('M d, Y H:i') }}
                                @if($api_key->isExpired())
                                    <span class="label label-danger">Expired</span>
                                @endif
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Rate Limit:</strong></td>
                        <td>{{ $api_key->rate_limit_per_minute }} requests per minute</td>
                    </tr>
                </table>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-success', 'title' => 'Permissions'])
                @if($api_key->abilities)
                    <div class="row">
                        @foreach($api_key->abilities as $ability)
                            <div class="col-xs-6 col-sm-4" style="margin-bottom: 10px;">
                                <span class="label label-info">{{ ucfirst($ability) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">No specific permissions assigned.</p>
                @endif

                <hr>

                <div class="btn-group">
                    @if($api_key->is_active)
                        <button type="button" class="btn btn-warning revoke-api-key" data-href="{{ route('api-keys.revoke', $api_key->id) }}">
                            <i class="fa fa-ban"></i> Revoke Key
                        </button>
                    @else
                        <button type="button" class="btn btn-success activate-api-key" data-href="{{ route('api-keys.activate', $api_key->id) }}">
                            <i class="fa fa-check"></i> Activate Key
                        </button>
                    @endif
                    <button type="button" class="btn btn-danger delete-api-key" data-href="{{ route('api-keys.destroy', $api_key->id) }}">
                        <i class="fa fa-trash"></i> Delete Key
                    </button>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Recent Usage Logs -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-warning', 'title' => 'Recent API Calls (Last 100)'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="usage_logs_table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Endpoint</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Back Button -->
    <div class="row">
        <div class="col-md-12">
            <a href="{{ route('api-keys.index') }}" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Back to API Keys
            </a>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize usage logs DataTable
        var usage_logs_table = $('#usage_logs_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("api-keys.show", $api_key->id) }}?logs=1',
            columns: [
                {data: 'created_at', name: 'created_at'},
                {data: 'endpoint', name: 'endpoint'},
                {data: 'response_status', name: 'response_status'},
                {data: 'response_time_ms', name: 'response_time_ms'},
                {data: 'ip_address', name: 'ip_address'},
                {data: 'user_agent', name: 'user_agent', orderable: false}
            ],
            order: [[0, 'desc']],
            pageLength: 25
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
                                location.reload();
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
                        location.reload();
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
                                window.location.href = '{{ route("api-keys.index") }}';
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection