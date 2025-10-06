<?php
    $sms_service = isset($sms_settings['sms_service']) ? $sms_settings['sms_service'] : 'msgowl';
?>
<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-3">
            <div class="form-group">
                <?php echo Form::label('sms_service', __('lang_v1.sms_service') . ':'); ?>

                <?php echo Form::select('sms_settings[sms_service]', ['msgowl' => 'MsgOwl'], 'msgowl', ['class' => 'form-control', 'id' => 'sms_service']); ?>

            </div>
        </div>
        <div class="col-xs-9">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>Message Owl SMS Service:</strong> This application is configured to use Message Owl as the SMS provider for reliable message delivery.
            </div>
        </div>
    </div>
    <div class="row sms_service_settings <?php if($sms_service != 'msgowl'): ?> hide <?php endif; ?>" data-service="msgowl">
        <div class="col-xs-3">
            <div class="form-group">
                <?php echo Form::label('msgowl_api_key', __('lang_v1.msgowl_api_key') . ':'); ?>

                <?php echo Form::text('sms_settings[msgowl_api_key]', !empty($sms_settings['msgowl_api_key']) ? $sms_settings['msgowl_api_key'] : null, ['class' => 'form-control','placeholder' => __('lang_v1.msgowl_api_key'), 'id' => 'msgowl_api_key']); ?>

            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                <?php echo Form::label('msgowl_sender_id', __('lang_v1.msgowl_sender_id') . ':'); ?>

                <?php echo Form::text('sms_settings[msgowl_sender_id]', !empty($sms_settings['msgowl_sender_id']) ? $sms_settings['msgowl_sender_id'] : null, ['class' => 'form-control','placeholder' => __('lang_v1.msgowl_sender_id'), 'id' => 'msgowl_sender_id']); ?>

            </div>
        </div>
        <div class="col-xs-6">
            <small class="text-muted">
                <?php echo __('lang_v1.msgowl_help_text'); ?>

            </small>
        </div>
    </div>
</div><?php /**PATH /var/www/html/resources/views/business/partials/settings_sms.blade.php ENDPATH**/ ?>