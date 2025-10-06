@extends('layouts.app')

@section('title', __('superadmin::lang.subscription'))

@section('content')

<section class="content-header">
    <h1>{{ __('superadmin::lang.location_limit_exceeded') }}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-warning text-yellow"></i>
                        @lang('superadmin::lang.location_limit_exceeded')
                    </h3>
                </div>
                
                <div class="box-body">
                    <div class="alert alert-warning">
                        <h4><i class="fa fa-warning"></i> @lang('superadmin::lang.action_required')!</h4>
                        <p>
                            @lang('superadmin::lang.package_allows_locations', [
                                'package_name' => $package->name,
                                'allowed_locations' => $package->location_count
                            ])
                        </p>
                        <p>
                            @lang('superadmin::lang.current_active_locations', ['count' => $active_locations_count])
                        </p>
                        @if($excess_locations > 0)
                        <p>
                            <strong>@lang('superadmin::lang.deactivate_locations_required', ['count' => $excess_locations])</strong>
                        </p>
                        @else
                        <p>
                            <strong>@lang('superadmin::lang.select_locations_to_keep_active', ['limit' => $package->location_count])</strong>
                        </p>
                        @endif
                    </div>

                    <form action="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'deactivateLocationsAndProceed'], $package->id) }}" method="post" id="location-deactivation-form">
                        @csrf
                        
                        <div class="form-group">
                            <label>@lang('superadmin::lang.select_locations_to_deactivate') ({{ $excess_locations }} required):</label>
                            <div class="row">
                                @foreach($locations_to_deactivate as $index => $location)
                                <div class="col-md-6">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="locations_to_deactivate[]" value="{{ $location->id }}" 
                                                   @if($excess_locations > 0 && $index < $excess_locations) checked @endif>
                                            {{ $location->name }}
                                            @if($location->city)
                                                <small class="text-muted">({{ $location->city }})</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <small class="help-block">
                                @lang('superadmin::lang.choose_from_all_active_locations')
                            </small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            @lang('superadmin::lang.deactivated_locations_note')
                        </div>
                    </form>
                </div>
                
                <div class="box-footer">
                    <div class="row">
                        <div class="col-sm-6">
                            <a href="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index']) }}" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i>
                                @lang('messages.back')
                            </a>
                        </div>
                        <div class="col-sm-6 text-right">
                            <button type="button" class="btn btn-success btn-upgrade-instead" data-package-id="{{ $package->id }}">
                                <i class="fa fa-arrow-up"></i>
                                @lang('superadmin::lang.upgrade_to_higher_plan')
                            </button>
                            <button type="submit" form="location-deactivation-form" class="btn btn-warning" id="proceed-with-deactivation">
                                <i class="fa fa-check"></i>
                                @lang('superadmin::lang.deactivate_and_proceed')
                            </button>
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
    // Count selected checkboxes
    function updateProceedButton() {
        var checkedCount = $('input[name="locations_to_deactivate[]"]:checked').length;
        var requiredCount = {{ $excess_locations }};
        var totalLocations = {{ $active_locations_count }};
        var packageLimit = {{ $package->location_count }};
        
        // When excess_locations = 0, user is choosing which locations to keep active
        if (requiredCount === 0) {
            // User must leave exactly packageLimit locations unchecked (active)
            var remainingActive = totalLocations - checkedCount;
            if (remainingActive === packageLimit) {
                $('#proceed-with-deactivation').prop('disabled', false);
                $('#proceed-with-deactivation').html('<i class="fa fa-check"></i> @lang("superadmin::lang.proceed_with_selection")');
            } else {
                $('#proceed-with-deactivation').prop('disabled', true);
                $('#proceed-with-deactivation').html('<i class="fa fa-warning"></i> Keep exactly ' + packageLimit + ' locations active (' + remainingActive + ' currently selected)');
            }
        } else {
            // Normal excess locations scenario
            if (checkedCount >= requiredCount) {
                $('#proceed-with-deactivation').prop('disabled', false);
                $('#proceed-with-deactivation').html('<i class="fa fa-check"></i> @lang("superadmin::lang.deactivate_and_proceed")');
            } else {
                $('#proceed-with-deactivation').prop('disabled', true);
                $('#proceed-with-deactivation').html('<i class="fa fa-warning"></i> ' + 
                    '@lang("superadmin::lang.select_more_locations", ["needed" => "' + (requiredCount - checkedCount) + '"])');
            }
        }
    }
    
    // Handle checkbox changes
    $('input[name="locations_to_deactivate[]"]').change(function() {
        updateProceedButton();
    });
    
    // Handle upgrade button
    $('.btn-upgrade-instead').click(function() {
        var packageId = $(this).data('package-id');
        
        // Redirect to subscription page to see higher plans
        window.location.href = '{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'index']) }}';
    });
    
    // Handle form submission
    $('#location-deactivation-form').submit(function(e) {
        var checkedCount = $('input[name="locations_to_deactivate[]"]:checked').length;
        var requiredCount = {{ $excess_locations }};
        var totalLocations = {{ $active_locations_count }};
        var packageLimit = {{ $package->location_count }};
        
        if (requiredCount === 0) {
            // Coming from unlimited - must keep exactly packageLimit locations active
            var remainingActive = totalLocations - checkedCount;
            if (remainingActive !== packageLimit) {
                e.preventDefault();
                toastr.error('Please keep exactly ' + packageLimit + ' locations active by deactivating ' + (totalLocations - packageLimit) + ' locations.');
                return false;
            }
        } else {
            // Normal excess scenario
            if (checkedCount < requiredCount) {
                e.preventDefault();
                toastr.error('@lang("superadmin::lang.select_required_locations_count", ["count" => "' + requiredCount + '"])');
                return false;
            }
        }
        
        // Confirm deactivation
        if (!confirm('@lang("superadmin::lang.confirm_location_deactivation")')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Initial button state update
    updateProceedButton();
});
</script>
@endsection