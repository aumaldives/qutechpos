<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-12">
            <h4>@lang('superadmin::lang.sms_settings')</h4>
            <hr>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('msgowl_api_key', 'MsgOwl API Key:') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-key"></i>
                    </span>
                    {!! Form::text('msgowl_api_key', $settings["msgowl_api_key"] ?? '', ['class' => 'form-control','placeholder' => 'Enter your MsgOwl API Key']); !!}
                </div>
                <p class="help-block">Get your API Key from <a href="https://console.msgowl.com" target="_blank">MsgOwl Console</a></p>
            </div>
        </div>
        
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('msgowl_sender_id', 'MsgOwl Sender ID:') !!}
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-id-card"></i>
                    </span>
                    {!! Form::text('msgowl_sender_id', $settings["msgowl_sender_id"] ?? '', ['class' => 'form-control','placeholder' => 'Enter approved Sender ID']); !!}
                </div>
                <p class="help-block">Must be approved in your MsgOwl Console</p>
            </div>
        </div>

        <div class="col-xs-4">
            <div class="form-group">
                <label for="test_sms_number">Test SMS Number:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="test_sms_number" placeholder="Enter test number (e.g. 7123456)">
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-success" id="test_msgowl_sms">Test SMS</button>
                    </span>
                </div>
                <p class="help-block">Test your MsgOwl configuration</p>
            </div>
        </div>

        <div class="col-xs-12">
            <div class="alert alert-info">
                <strong>Note:</strong> These settings will be used for SuperAdmin SMS communications to businesses. 
                Businesses can configure their own MsgOwl credentials in their individual business settings.
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#test_msgowl_sms').click(function() {
        var testNumber = $('#test_sms_number').val();
        var apiKey = $('input[name="msgowl_api_key"]').val();
        var senderId = $('input[name="msgowl_sender_id"]').val();
        
        if (!testNumber || !apiKey || !senderId) {
            alert('Please fill in all MsgOwl fields before testing.');
            return;
        }
        
        $(this).prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: '{{ action([\Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'testSms']) }}',
            method: 'POST',
            data: {
                test_number: testNumber,
                api_key: apiKey,
                sender_id: senderId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    alert('Test SMS sent successfully!');
                } else {
                    alert('Failed to send test SMS: ' + (response.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error occurred while testing SMS');
            },
            complete: function() {
                $('#test_msgowl_sms').prop('disabled', false).text('Test SMS');
            }
        });
    });
});
</script>