<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-12">
            <h4>Telegram Bot Settings</h4>
            <hr/>
        </div>
    </div>

    @php
        $telegram_settings = isset($business) && $business->telegram_bot_setting ? $business->telegram_bot_setting : null;
    @endphp

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('telegram_bot_token', 'Bot Token:') !!}
                {!! Form::text('telegram_bot_token', $telegram_settings ? $telegram_settings->bot_token : '', ['class' => 'form-control', 'placeholder' => 'Enter your Telegram Bot Token']); !!}
                <p class="help-block">
                    <small>
                        <strong>How to create a bot:</strong><br/>
                        1. Message <a href="https://t.me/botfather" target="_blank">@BotFather</a> on Telegram<br/>
                        2. Send /newbot and follow instructions<br/>
                        3. Copy the Bot Token and paste it here
                    </small>
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('telegram_authorized_chats', 'Authorized Chat IDs:') !!}
                {!! Form::textarea('telegram_authorized_chats', $telegram_settings ? $telegram_settings->authorized_chat_ids : '', ['class' => 'form-control', 'rows' => '3', 'placeholder' => 'Enter Chat IDs separated by comma (e.g., 123456789, 987654321)']); !!}
                <p class="help-block">
                    <small>
                        <strong>How to get your Chat ID:</strong><br/>
                        1. Message <a href="https://t.me/userinfobot" target="_blank">@userinfobot</a> on Telegram<br/>
                        2. Copy the Chat ID and paste it here<br/>
                        3. Multiple IDs can be separated by commas
                    </small>
                </p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                <label>&nbsp;</label><br/>
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('telegram_bot_active', 1, $telegram_settings ? $telegram_settings->is_active : false, ['id' => 'telegram_bot_active']); !!}
                        <strong>Enable Telegram Bot</strong>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <h5><i class="fa fa-info-circle"></i> Bot Features</h5>
            <div class="well well-sm">
                <p><strong>Your bot will provide these features:</strong></p>
                <ul>
                    <li><strong>📅 Reports:</strong> View daily, weekly, monthly sales reports and profits</li>
                    <li><strong>💵 Add Expense:</strong> Quickly add business expenses with attachments</li>
                    <li><strong>🏢 Multi-Location:</strong> Select specific business locations for reports/expenses</li>
                    <li><strong>🔒 Secure:</strong> Only authorized chat IDs can access the bot</li>
                </ul>
                <p><strong>Setup Steps:</strong></p>
                <ol>
                    <li>Create your bot using @BotFather and get the bot token</li>
                    <li>Get your chat ID from @userinfobot</li>
                    <li>Fill in the form above and enable the bot</li>
                    <li>Start chatting with your bot on Telegram!</li>
                </ol>
            </div>
        </div>
    </div>

    @if($telegram_settings && $telegram_settings->is_active)
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <strong>Telegram Bot is Active!</strong>
                Your bot is running and ready to receive messages from authorized users.
                @if($telegram_settings->bot_token)
                    @php
                        $bot_info = explode(':', $telegram_settings->bot_token);
                        $bot_id = $bot_info[0] ?? '';
                        $webhook_url = url("/telegram/webhook/{$business->id}");
                    @endphp
                    <br/>Bot ID: <strong>{{ $bot_id }}</strong>
                    <br/>Webhook URL: <strong>{{ $webhook_url }}</strong>
                    <br/><small class="text-muted">Webhook is automatically configured when bot is enabled.</small>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-xs-12">
            <button type="button" class="btn btn-info" id="test-telegram-bot">
                <i class="fa fa-paper-plane"></i> Test Bot Connection
            </button>
            <span id="telegram-test-result"></span>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#test-telegram-bot').click(function() {
        var botToken = $('input[name="telegram_bot_token"]').val();
        var chatIds = $('textarea[name="telegram_authorized_chats"]').val();
        
        if (!botToken) {
            alert('Please enter a bot token first.');
            return;
        }
        
        if (!chatIds) {
            alert('Please enter at least one authorized chat ID.');
            return;
        }
        
        $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Testing...');
        
        $.ajax({
            url: '{{ action([\App\Http\Controllers\BusinessController::class, "testTelegramBot"]) }}',
            method: 'POST',
            data: {
                bot_token: botToken,
                chat_ids: chatIds,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $('#telegram-test-result').html('<span class="text-success"><i class="fa fa-check"></i> ' + response.message + '</span>');
                } else {
                    $('#telegram-test-result').html('<span class="text-danger"><i class="fa fa-times"></i> ' + response.message + '</span>');
                }
            },
            error: function(xhr) {
                var message = 'Error testing bot connection';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                $('#telegram-test-result').html('<span class="text-danger"><i class="fa fa-times"></i> ' + message + '</span>');
            },
            complete: function() {
                $('#test-telegram-bot').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Test Bot Connection');
            }
        });
    });
});
</script>