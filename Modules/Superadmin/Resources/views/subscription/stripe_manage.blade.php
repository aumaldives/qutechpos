@extends('layouts.app')
@section('title', __('superadmin::lang.stripe_subscription_management'))

@section('content')
<div class="row">
    <div class="col-md-12">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('superadmin::lang.stripe_subscription_management')])
            <div class="row">
                <div class="col-md-6">
                    <h4>{{ __('superadmin::lang.current_subscription') }}</h4>
                    <table class="table table-bordered">
                        <tr>
                            <th>{{ __('superadmin::lang.package') }}:</th>
                            <td>{{ $subscription->package->name }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('superadmin::lang.status') }}:</th>
                            <td>
                                <span class="label label-{{ $stripeSubscription->status === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($stripeSubscription->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.start_date') }}:</th>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start)->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('lang_v1.end_date') }}:</th>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('superadmin::lang.next_billing') }}:</th>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('superadmin::lang.auto_renewal') }}:</th>
                            <td>
                                <span class="label label-{{ $stripeSubscription->cancel_at_period_end ? 'danger' : 'success' }}">
                                    {{ $stripeSubscription->cancel_at_period_end ? __('superadmin::lang.will_cancel') : __('superadmin::lang.enabled') }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('superadmin::lang.amount') }}:</th>
                            <td>
                                <span class="display_currency" data-currency_symbol="true">
                                    {{ $stripeSubscription->items->data[0]->price->unit_amount / 100 }}
                                </span>
                                / {{ $stripeSubscription->items->data[0]->price->recurring->interval }}
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h4>{{ __('superadmin::lang.subscription_actions') }}</h4>
                    
                    <div class="btn-group-vertical" role="group">
                        <!-- Customer Portal Button -->
                        <a href="{{ route('subscription.stripe.customer-portal') }}" class="btn btn-primary btn-block">
                            <i class="fa fa-external-link"></i> {{ __('superadmin::lang.manage_via_stripe') }}
                        </a>
                        
                        <hr>
                        
                        <!-- Update Payment Method -->
                        <button type="button" class="btn btn-info btn-block" data-toggle="modal" data-target="#updatePaymentModal">
                            <i class="fa fa-credit-card"></i> {{ __('superadmin::lang.update_payment_method') }}
                        </button>
                        
                        <!-- Cancel Subscription -->
                        @if(!$stripeSubscription->cancel_at_period_end)
                            <button type="button" class="btn btn-warning btn-block" onclick="cancelSubscription(true)">
                                <i class="fa fa-pause"></i> {{ __('superadmin::lang.cancel_at_period_end') }}
                            </button>
                            
                            <button type="button" class="btn btn-danger btn-block" onclick="cancelSubscription(false)">
                                <i class="fa fa-stop"></i> {{ __('superadmin::lang.cancel_immediately') }}
                            </button>
                        @else
                            <div class="alert alert-warning">
                                <i class="fa fa-info-circle"></i> 
                                {{ __('superadmin::lang.subscription_will_cancel_on') }} 
                                {{ \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end)->format('M d, Y') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endcomponent
    </div>
</div>

<!-- Update Payment Method Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('superadmin::lang.update_payment_method') }}</h4>
            </div>
            <form id="updatePaymentForm">
                <div class="modal-body">
                    <p>{{ __('superadmin::lang.update_payment_method_info') }}</p>
                    
                    <div id="card-element">
                        <!-- Stripe Elements will create form elements here -->
                    </div>
                    
                    <div id="card-errors" role="alert" class="text-danger"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        {{ __('lang_v1.close') }}
                    </button>
                    <button type="submit" class="btn btn-primary" id="updatePaymentBtn">
                        {{ __('superadmin::lang.update_payment_method') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script src="https://js.stripe.com/v3/"></script>
<script>
    var stripe = Stripe('{{ env("STRIPE_PUB_KEY") }}');
    var elements = stripe.elements();
    var cardElement = elements.create('card');
    
    // Mount the card element
    $('#updatePaymentModal').on('shown.bs.modal', function() {
        cardElement.mount('#card-element');
    });
    
    // Handle form submission
    $('#updatePaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $('#updatePaymentBtn');
        submitBtn.prop('disabled', true).text('{{ __("lang_v1.processing") }}...');
        
        stripe.createToken(cardElement).then(function(result) {
            if (result.error) {
                $('#card-errors').text(result.error.message);
                submitBtn.prop('disabled', false).text('{{ __("superadmin::lang.update_payment_method") }}');
            } else {
                // Submit the token to your server
                $.ajax({
                    url: '{{ route("subscription.stripe.update-payment") }}',
                    type: 'POST',
                    data: {
                        stripeToken: result.token.id,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#updatePaymentModal').modal('hide');
                            location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('{{ __("messages.something_went_wrong") }}');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text('{{ __("superadmin::lang.update_payment_method") }}');
                    }
                });
            }
        });
    });
    
    function cancelSubscription(cancelAtPeriodEnd) {
        var message = cancelAtPeriodEnd ? 
            '{{ __("superadmin::lang.confirm_cancel_at_period_end") }}' : 
            '{{ __("superadmin::lang.confirm_cancel_immediately") }}';
            
        if (confirm(message)) {
            $.ajax({
                url: '{{ route("subscription.stripe.cancel") }}',
                type: 'POST',
                data: {
                    cancel_at_period_end: cancelAtPeriodEnd,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('{{ __("messages.something_went_wrong") }}');
                }
            });
        }
    }
</script>
@endsection