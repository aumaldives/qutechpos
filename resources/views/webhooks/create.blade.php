@extends('layouts.app')
@section('title', 'Create Webhook')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>Create Webhook
        <small>Set up real-time event notifications</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{route('integrations')}}">Integrations</a></li>
        <li><a href="{{route('webhooks.index')}}">Webhooks</a></li>
        <li class="active">Create</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    <form action="{{ route('webhooks.store') }}" method="POST" id="webhook-form">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                @component('components.widget', ['class' => 'box-primary', 'title' => 'Webhook Configuration'])
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Name -->
                            <div class="form-group">
                                <label for="name">Name <span class="text-red">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{ old('name') }}" placeholder="E.g., Order Notifications" required>
                                @error('name')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <!-- URL -->
                            <div class="form-group">
                                <label for="url">Endpoint URL <span class="text-red">*</span></label>
                                <input type="url" class="form-control" id="url" name="url" 
                                       value="{{ old('url') }}" placeholder="https://your-app.com/webhooks/islebooks" required>
                                <p class="help-block">
                                    <i class="fa fa-info-circle"></i> 
                                    This URL will receive HTTP POST requests when subscribed events occur.
                                </p>
                                @error('url')
                                    <span class="text-red">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endcomponent
                
                @component('components.widget', ['class' => 'box-info', 'title' => 'Event Subscriptions'])
                    <div class="row">
                        <div class="col-md-12">
                            <p class="text-muted">Select which events you want to receive notifications for:</p>
                            
                            <!-- Select All/None -->
                            <div class="form-group">
                                <button type="button" class="btn btn-sm btn-default" id="select-all-events">
                                    <i class="fa fa-check-square-o"></i> Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-default" id="select-none-events">
                                    <i class="fa fa-square-o"></i> Select None
                                </button>
                            </div>
                            
                            @php
                                $eventGroups = [
                                    'Product Events' => ['product.created', 'product.updated', 'product.deleted', 'product.stock_updated'],
                                    'Contact Events' => ['contact.created', 'contact.updated', 'contact.deleted'],
                                    'Transaction Events' => ['transaction.created', 'transaction.updated', 'transaction.deleted', 'transaction.payment_added', 'transaction.status_changed'],
                                    'Sale Events' => ['sale.created', 'sale.completed', 'sale.cancelled', 'sale.refunded'],
                                    'Purchase Events' => ['purchase.created', 'purchase.received', 'purchase.cancelled'],
                                    'Stock Events' => ['stock.low_alert', 'stock.adjustment', 'stock.transfer'],
                                    'Business Events' => ['business.settings_updated', 'business.location_created', 'business.location_updated']
                                ];
                            @endphp
                            
                            <div class="row">
                                @foreach($eventGroups as $groupName => $events)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">
                                                <strong>{{ $groupName }}</strong>
                                                <button type="button" class="btn btn-xs btn-default group-toggle" data-group="{{ $loop->index }}">
                                                    <i class="fa fa-check-square-o"></i> All
                                                </button>
                                            </label>
                                            
                                            @foreach($events as $event)
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="events[]" value="{{ $event }}" 
                                                               class="event-checkbox group-{{ $loop->parent->index }}"
                                                               {{ in_array($event, old('events', [])) ? 'checked' : '' }}>
                                                        <code>{{ $event }}</code>
                                                        <span class="text-muted">- {{ $eventDescriptions[$event] ?? 'Event notification' }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            @error('events')
                                <span class="text-red">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endcomponent
            </div>
            
            <div class="col-md-4">
                @component('components.widget', ['class' => 'box-warning', 'title' => 'Advanced Settings'])
                    <!-- Timeout -->
                    <div class="form-group">
                        <label for="timeout">Request Timeout (seconds)</label>
                        <input type="number" class="form-control" id="timeout" name="timeout" 
                               value="{{ old('timeout', 30) }}" min="5" max="120">
                        <p class="help-block">Maximum time to wait for response</p>
                    </div>
                    
                    <!-- Max Retries -->
                    <div class="form-group">
                        <label for="max_retries">Maximum Retries</label>
                        <input type="number" class="form-control" id="max_retries" name="max_retries" 
                               value="{{ old('max_retries', 3) }}" min="0" max="10">
                        <p class="help-block">Number of retry attempts on failure</p>
                    </div>
                    
                    <!-- Retry Delay -->
                    <div class="form-group">
                        <label for="retry_delay">Initial Retry Delay (seconds)</label>
                        <input type="number" class="form-control" id="retry_delay" name="retry_delay" 
                               value="{{ old('retry_delay', 60) }}" min="10" max="3600">
                        <p class="help-block">Delay before first retry (exponential backoff)</p>
                    </div>
                @endcomponent
                
                @component('components.widget', ['class' => 'box-success', 'title' => 'Webhook Information'])
                    <div class="alert alert-info">
                        <h4><i class="fa fa-info-circle"></i> How Webhooks Work</h4>
                        <ul class="list-unstyled">
                            <li>• Events trigger HTTP POST requests to your endpoint</li>
                            <li>• Requests include signature for verification</li>
                            <li>• Failed deliveries are automatically retried</li>
                            <li>• You can test endpoints before going live</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h4><i class="fa fa-shield"></i> Security</h4>
                        <p>Each webhook will receive a unique secret key for signature verification. Always verify signatures in production.</p>
                    </div>
                @endcomponent
                
                <!-- Submit Buttons -->
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fa fa-save"></i> Create Webhook
                    </button>
                    <a href="{{ route('webhooks.index') }}" class="btn btn-default btn-block">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </form>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    
    // Select all events
    $('#select-all-events').on('click', function() {
        $('.event-checkbox').prop('checked', true);
        updateGroupButtons();
    });
    
    // Select no events
    $('#select-none-events').on('click', function() {
        $('.event-checkbox').prop('checked', false);
        updateGroupButtons();
    });
    
    // Group toggle buttons
    $('.group-toggle').on('click', function() {
        var groupIndex = $(this).data('group');
        var groupCheckboxes = $('.group-' + groupIndex);
        var allChecked = groupCheckboxes.filter(':checked').length === groupCheckboxes.length;
        
        groupCheckboxes.prop('checked', !allChecked);
        updateGroupButtons();
    });
    
    // Individual checkbox changes
    $('.event-checkbox').on('change', function() {
        updateGroupButtons();
    });
    
    // Update group button states
    function updateGroupButtons() {
        $('.group-toggle').each(function() {
            var groupIndex = $(this).data('group');
            var groupCheckboxes = $('.group-' + groupIndex);
            var checkedCount = groupCheckboxes.filter(':checked').length;
            var totalCount = groupCheckboxes.length;
            
            var icon = $(this).find('i');
            if (checkedCount === 0) {
                icon.removeClass('fa-check-square-o fa-minus-square-o').addClass('fa-square-o');
                $(this).find('text').text(' None');
            } else if (checkedCount === totalCount) {
                icon.removeClass('fa-square-o fa-minus-square-o').addClass('fa-check-square-o');
                $(this).html('<i class="fa fa-check-square-o"></i> All');
            } else {
                icon.removeClass('fa-square-o fa-check-square-o').addClass('fa-minus-square-o');
                $(this).html('<i class="fa fa-minus-square-o"></i> Some');
            }
        });
    }
    
    // Form validation
    $('#webhook-form').on('submit', function(e) {
        var checkedEvents = $('.event-checkbox:checked').length;
        
        if (checkedEvents === 0) {
            e.preventDefault();
            toastr.error('Please select at least one event to subscribe to.');
            return false;
        }
        
        // Show loading state
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Creating...').prop('disabled', true);
    });
    
    // Initial update
    updateGroupButtons();
});
</script>
@endsection

