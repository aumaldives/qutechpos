@php
    $sms_service = isset($sms_settings['sms_service']) ? $sms_settings['sms_service'] : 'msgowl';
@endphp
<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('sms_service', __('lang_v1.sms_service') . ':') !!}
                {!! Form::select('sms_settings[sms_service]', ['msgowl' => 'MsgOwl'], 'msgowl', ['class' => 'form-control', 'id' => 'sms_service']); !!}
            </div>
        </div>
        <div class="col-xs-9">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>Message Owl SMS Service:</strong> This application is configured to use Message Owl as the SMS provider for reliable message delivery.
            </div>
        </div>
    </div>
    <div class="row sms_service_settings @if($sms_service != 'msgowl') hide @endif" data-service="msgowl">
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('msgowl_api_key', __('lang_v1.msgowl_api_key') . ':') !!}
                {!! Form::text('sms_settings[msgowl_api_key]', !empty($sms_settings['msgowl_api_key']) ? $sms_settings['msgowl_api_key'] : null, ['class' => 'form-control','placeholder' => __('lang_v1.msgowl_api_key'), 'id' => 'msgowl_api_key']); !!}
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('msgowl_sender_id', __('lang_v1.msgowl_sender_id') . ':') !!}
                {!! Form::text('sms_settings[msgowl_sender_id]', !empty($sms_settings['msgowl_sender_id']) ? $sms_settings['msgowl_sender_id'] : null, ['class' => 'form-control','placeholder' => __('lang_v1.msgowl_sender_id'), 'id' => 'msgowl_sender_id']); !!}
            </div>
        </div>
        <div class="col-xs-6">
            <small class="text-muted">
                {!! __('lang_v1.msgowl_help_text') !!}
            </small>
        </div>
    </div>
</div>