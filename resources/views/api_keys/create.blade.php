@extends('layouts.app')
@section('title', 'Create API Key')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Create API Key
        <small>Generate a new API key for secure third-party integrations</small>
    </h1>
    <hr class="header-row"/>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => route('api-keys.store'), 'method' => 'post', 'id' => 'api_key_form']) !!}

    <div class="row">
        <div class="col-md-8">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'API Key Details'])
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            {!! Form::label('name', 'API Key Name*') !!}
                            {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => 'e.g., Mobile App Integration, Website API, Third-party Service']) !!}
                            <p class="help-block">Give your API key a descriptive name to identify its purpose.</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('rate_limit_per_minute', 'Rate Limit (requests per minute)*') !!}
                            {!! Form::number('rate_limit_per_minute', 60, ['class' => 'form-control', 'required', 'min' => 1, 'max' => 1000]) !!}
                            <p class="help-block">Maximum API requests allowed per minute (1-1000).</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('expires_at', 'Expiration Date (Optional)') !!}
                            {!! Form::datetime('expires_at', null, ['class' => 'form-control', 'placeholder' => 'Leave empty for no expiration']) !!}
                            <p class="help-block">API key will stop working after this date.</p>
                        </div>
                    </div>
                </div>
            @endcomponent

            @component('components.widget', ['class' => 'box-info', 'title' => 'API Permissions'])
                <p class="text-muted">Select the permissions this API key should have. Choose only the minimum permissions needed for security.</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <h4>Basic Permissions</h4>
                        @foreach($available_abilities as $ability => $description)
                            @if(in_array($ability, ['read', 'write', 'delete']))
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('abilities[]', $ability, in_array($ability, ['read', 'write']), ['id' => 'ability_' . $ability]) !!}
                                        <strong>{{ ucfirst($ability) }}</strong> - {{ $description }}
                                    </label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <h4>Resource Permissions</h4>
                        @foreach($available_abilities as $ability => $description)
                            @if(!in_array($ability, ['read', 'write', 'delete']))
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('abilities[]', $ability, false, ['id' => 'ability_' . $ability]) !!}
                                        <strong>{{ ucfirst($ability) }}</strong> - {{ $description }}
                                    </label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Security Note:</strong> Resource permissions (Products, Transactions, etc.) require basic permissions (Read/Write) to function. For example, to create products via API, you need both "Write" and "Products" permissions.
                </div>
            @endcomponent
        </div>

        <div class="col-md-4">
            @component('components.widget', ['class' => 'box-success', 'title' => 'Security Information'])
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fa fa-shield-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Security Features</span>
                        <span class="info-box-number">SHA-256 Encrypted</span>
                    </div>
                </div>

                <h4><i class="fa fa-key"></i> Key Features</h4>
                <ul class="list-unstyled">
                    <li><i class="fa fa-check text-green"></i> Secure 64-character keys</li>
                    <li><i class="fa fa-check text-green"></i> SHA-256 hash storage</li>
                    <li><i class="fa fa-check text-green"></i> Rate limiting protection</li>
                    <li><i class="fa fa-check text-green"></i> Usage analytics tracking</li>
                    <li><i class="fa fa-check text-green"></i> Revocation capability</li>
                    <li><i class="fa fa-check text-green"></i> Expiration support</li>
                </ul>

                <h4><i class="fa fa-info-circle"></i> Usage Tips</h4>
                <ul class="list-unstyled text-muted small">
                    <li>• Store API keys securely</li>
                    <li>• Use HTTPS for all API calls</li>
                    <li>• Rotate keys regularly</li>
                    <li>• Monitor usage in statistics</li>
                    <li>• Revoke unused keys</li>
                </ul>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-plus"></i> Generate API Key
                    </button>
                    <a href="{{ route('api-keys.index') }}" class="btn btn-default btn-block">
                        <i class="fa fa-arrow-left"></i> Back to API Keys
                    </a>
                </div>
            @endcomponent
        </div>
    </div>

    {!! Form::close() !!}
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize datetime picker for expiration date
        $('#expires_at').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            sideBySide: true,
            useCurrent: false,
            minDate: moment().add(1, 'hour')
        });

        // Form submission with validation
        $('#api_key_form').submit(function(e) {
            // Check if at least one ability is selected
            var abilities_checked = $('input[name="abilities[]"]:checked').length;
            
            if (abilities_checked === 0) {
                e.preventDefault();
                toastr.error('Please select at least one permission for the API key.');
                return false;
            }

            // Show loading state
            var submit_btn = $(this).find('button[type="submit"]');
            submit_btn.attr('disabled', true);
            submit_btn.html('<i class="fa fa-spinner fa-spin"></i> Generating...');
        });

        // Auto-check dependencies
        $('#ability_products, #ability_transactions, #ability_contacts, #ability_reports, #ability_settings').change(function() {
            if ($(this).is(':checked')) {
                // If a resource permission is checked, ensure read is checked
                if (!$('#ability_read').is(':checked')) {
                    $('#ability_read').prop('checked', true);
                    toastr.info('Read permission automatically enabled for resource access.');
                }
            }
        });

        $('#ability_write').change(function() {
            if ($(this).is(':checked')) {
                // If write is checked, ensure read is checked
                if (!$('#ability_read').is(':checked')) {
                    $('#ability_read').prop('checked', true);
                    toastr.info('Read permission automatically enabled for write access.');
                }
            }
        });

        $('#ability_delete').change(function() {
            if ($(this).is(':checked')) {
                // If delete is checked, ensure read and write are checked
                if (!$('#ability_read').is(':checked')) {
                    $('#ability_read').prop('checked', true);
                }
                if (!$('#ability_write').is(':checked')) {
                    $('#ability_write').prop('checked', true);
                }
                toastr.info('Read and Write permissions automatically enabled for delete access.');
            }
        });

        // Rate limit slider feedback
        $('#rate_limit_per_minute').on('input', function() {
            var value = $(this).val();
            var feedback = '';
            
            if (value <= 10) {
                feedback = 'Very conservative - good for testing';
            } else if (value <= 60) {
                feedback = 'Moderate - suitable for most applications';
            } else if (value <= 200) {
                feedback = 'High - for busy applications';
            } else {
                feedback = 'Very high - use with caution';
            }
            
            $(this).next('.help-block').text('Maximum API requests allowed per minute (1-1000). ' + feedback);
        });
    });
</script>
@endsection