@extends('layouts.app')
@section('title', __('WooCommerce Sync Health Dashboard'))

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">🏥 WooCommerce Sync Health Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('home')}}">@lang('home.home')</a></li>
                    <li class="breadcrumb-item"><a href="{{route('woocommerce.index')}}">@lang('woocommerce::lang.woocommerce')</a></li>
                    <li class="breadcrumb-item active">Sync Health</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <!-- Health Overview Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card bg-gradient-primary">
                    <div class="card-body">
                        <div class="d-flex">
                            <div>
                                <h3 class="text-white">{{ $healthMetrics['health_score'] }}%</h3>
                                <p class="text-white-50 mb-0">Overall Health Score</p>
                            </div>
                            <div class="ml-auto align-self-center">
                                <i class="fas fa-heartbeat fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            <div class="progress-bar bg-white" style="width: {{ $healthMetrics['health_score'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card bg-gradient-success">
                    <div class="card-body">
                        <div class="d-flex">
                            <div>
                                <h3 class="text-white">{{ $healthMetrics['sync_success_rate_24h'] }}%</h3>
                                <p class="text-white-50 mb-0">Success Rate (24h)</p>
                            </div>
                            <div class="ml-auto align-self-center">
                                <i class="fas fa-check-circle fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <p class="text-white-50 mb-0 mt-2">{{ $healthMetrics['successful_syncs_24h'] }} / {{ $healthMetrics['total_syncs_24h'] }} syncs</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card bg-gradient-warning">
                    <div class="card-body">
                        <div class="d-flex">
                            <div>
                                <h3 class="text-white">{{ $healthMetrics['total_errors_24h'] }}</h3>
                                <p class="text-white-50 mb-0">Errors (24h)</p>
                            </div>
                            <div class="ml-auto align-self-center">
                                <i class="fas fa-exclamation-triangle fa-2x text-white-50"></i>
                            </div>
                        </div>
                        <p class="text-white-50 mb-0 mt-2">{{ $healthMetrics['critical_errors_24h'] }} critical</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card bg-gradient-info">
                    <div class="card-body">
                        <div class="d-flex">
                            <div>
                                <h3 class="text-white">{{ $healthMetrics['healthy_locations'] }} / {{ $healthMetrics['location_count'] }}</h3>
                                <p class="text-white-50 mb-0">Healthy Locations</p>
                            </div>
                            <div class="ml-auto align-self-center">
                                <i class="fas fa-map-marker-alt fa-2x text-white-50"></i>
                            </div>
                        </div>
                        @if($healthMetrics['locations_with_issues'] > 0)
                            <p class="text-white-50 mb-0 mt-2">{{ $healthMetrics['locations_with_issues'] }} with issues</p>
                        @else
                            <p class="text-white-50 mb-0 mt-2">All locations healthy</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Location Status -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📍 Location Status</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-sm btn-primary" onclick="refreshHealthMetrics()">
                                <i class="fas fa-sync"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($locations->isEmpty())
                            <div class="text-center py-4">
                                <i class="fas fa-store fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No WooCommerce locations configured yet.</p>
                                <a href="{{ route('woocommerce.index') }}" class="btn btn-primary">
                                    Configure WooCommerce
                                </a>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped" id="locations-table">
                                    <thead>
                                        <tr>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Last Sync</th>
                                            <th>Success Rate (24h)</th>
                                            <th>Errors</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($locations as $location)
                                            @php
                                                $locationData = app('Modules\Woocommerce\Http\Controllers\SyncHealthController')->healthMetricsApi(request()->merge(['location_id' => $location->id]))->getData(true);
                                            @endphp
                                            <tr data-location-id="{{ $location->id }}">
                                                <td>
                                                    <strong>{{ $location->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $location->woocommerceLocationSettings->woocommerce_app_url ?? 'Not configured' }}</small>
                                                </td>
                                                <td>
                                                    @if($locationData['unresolved_errors'] == 0 && $locationData['sync_success_rate_24h'] >= 90)
                                                        <span class="badge badge-success">✅ Healthy</span>
                                                    @elseif($locationData['sync_success_rate_24h'] >= 70)
                                                        <span class="badge badge-warning">⚠️ Issues</span>
                                                    @else
                                                        <span class="badge badge-danger">❌ Critical</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($locationData['last_sync_at'])
                                                        {{ \Carbon\Carbon::parse($locationData['last_sync_at'])->diffForHumans() }}
                                                    @else
                                                        <span class="text-muted">Never</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar 
                                                            @if($locationData['sync_success_rate_24h'] >= 90) bg-success
                                                            @elseif($locationData['sync_success_rate_24h'] >= 70) bg-warning
                                                            @else bg-danger @endif"
                                                             style="width: {{ $locationData['sync_success_rate_24h'] }}%">
                                                            {{ $locationData['sync_success_rate_24h'] }}%
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($locationData['unresolved_errors'] > 0)
                                                        <span class="badge badge-danger">{{ $locationData['unresolved_errors'] }} unresolved</span>
                                                    @else
                                                        <span class="badge badge-success">No errors</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route('woocommerce.sync-health.location', $location->id) }}" 
                                                           class="btn btn-outline-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-success" 
                                                                onclick="triggerLocationSync({{ $location->id }})" title="Sync Now">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📋 Recent Activity</h3>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <div id="activity-feed">
                            @forelse($recentActivities as $activity)
                                <div class="activity-item mb-3">
                                    <div class="d-flex">
                                        <div class="mr-3">
                                            <i class="{{ $activity['icon'] }} text-{{ $activity['color'] }}"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1">
                                                <strong>{{ $activity['action'] }}</strong>
                                                @if($activity['location_name'])
                                                    <br><small class="text-muted">{{ $activity['location_name'] }}</small>
                                                @endif
                                            </p>
                                            <p class="mb-1 text-sm">{{ $activity['message'] }}</p>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4">
                                    <i class="fas fa-history fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No recent activity</p>
                                </div>
                            @endforelse
                        </div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadMoreActivity()">
                                Load More
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Trends Chart -->
        @if(!empty($errorTrends))
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📈 Error Trends (Last 30 Days)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="errorTrendsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</section>

@push('js')
<script>
$(document).ready(function() {
    // Initialize auto-refresh
    setInterval(refreshHealthMetrics, 30000); // Refresh every 30 seconds
    
    // Initialize error trends chart
    @if(!empty($errorTrends))
        initErrorTrendsChart(@json($errorTrends));
    @endif
});

function refreshHealthMetrics() {
    $.get('{{ route("woocommerce.sync-health.metrics-api") }}', function(data) {
        // Update health score cards
        updateHealthCards(data);
        // Refresh location table
        location.reload();
    }).fail(function() {
        console.error('Failed to refresh health metrics');
    });
}

function updateHealthCards(metrics) {
    // Update the health cards with new data
    // This would update the card values dynamically
}

function triggerLocationSync(locationId) {
    if (!confirm('Start sync for this location now?')) return;
    
    $.post('{{ route("woocommerce.configuration.trigger-location-sync") }}', {
        location_id: locationId,
        sync_type: 'all',
        _token: '{{ csrf_token() }}'
    }, function(response) {
        toastr.success('Sync started successfully');
        setTimeout(refreshHealthMetrics, 2000);
    }).fail(function(xhr) {
        toastr.error('Failed to start sync: ' + (xhr.responseJSON?.message || 'Unknown error'));
    });
}

let activityOffset = {{ count($recentActivities) }};

function loadMoreActivity() {
    $.get('{{ route("woocommerce.sync-health.activity-api") }}', {
        offset: activityOffset,
        limit: 10
    }, function(data) {
        if (data.activities && data.activities.length > 0) {
            // Append new activities to the feed
            data.activities.forEach(function(activity) {
                $('#activity-feed').append(buildActivityItem(activity));
            });
            activityOffset += data.activities.length;
            
            if (!data.has_more) {
                $('.load-more-btn').hide();
            }
        }
    }).fail(function() {
        toastr.error('Failed to load more activities');
    });
}

function buildActivityItem(activity) {
    return `
        <div class="activity-item mb-3">
            <div class="d-flex">
                <div class="mr-3">
                    <i class="${activity.icon} text-${activity.color}"></i>
                </div>
                <div class="flex-grow-1">
                    <p class="mb-1">
                        <strong>${activity.action}</strong>
                        ${activity.location_name ? '<br><small class="text-muted">' + activity.location_name + '</small>' : ''}
                    </p>
                    <p class="mb-1 text-sm">${activity.message}</p>
                    <small class="text-muted">${moment(activity.created_at).fromNow()}</small>
                </div>
            </div>
        </div>
    `;
}

function initErrorTrendsChart(errorTrends) {
    const ctx = document.getElementById('errorTrendsChart').getContext('2d');
    
    // Process data for Chart.js
    const dates = Object.keys(errorTrends).sort();
    const datasets = [];
    
    // Get all error categories
    const categories = [...new Set(
        Object.values(errorTrends)
            .flat()
            .map(item => item.error_category)
    )];
    
    const colors = [
        '#dc3545', '#fd7e14', '#ffc107', '#28a745', 
        '#17a2b8', '#6f42c1', '#e83e8c', '#6c757d'
    ];
    
    categories.forEach((category, index) => {
        const data = dates.map(date => {
            const dayData = errorTrends[date] || [];
            const categoryData = dayData.find(item => item.error_category === category);
            return categoryData ? categoryData.error_count : 0;
        });
        
        datasets.push({
            label: category.replace('_', ' ').toUpperCase(),
            data: data,
            borderColor: colors[index % colors.length],
            backgroundColor: colors[index % colors.length] + '20',
            tension: 0.4
        });
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dates,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Errors'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
}
</script>
@endpush

@push('css')
<style>
.activity-item {
    border-left: 3px solid #e9ecef;
    padding-left: 10px;
    margin-left: 10px;
}

.activity-item:hover {
    border-left-color: #007bff;
    background-color: #f8f9fa;
}

.progress {
    min-width: 100px;
}

#locations-table tbody tr:hover {
    background-color: #f8f9fa;
}

.card-body {
    position: relative;
}

.refresh-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: opacity 0.3s;
}

.refreshing .refresh-indicator {
    opacity: 1;
}
</style>
@endpush
@endsection