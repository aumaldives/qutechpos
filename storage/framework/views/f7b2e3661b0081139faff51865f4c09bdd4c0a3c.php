<!-- Custom Tabs -->
<div class="nav-tabs-custom">
    <ul class="nav nav-tabs">
        <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li <?php if($loop->index == 0): ?> class="active" <?php endif; ?>>
                <a href="#cn_<?php echo e($key, false); ?>" data-toggle="tab" aria-expanded="true">
                <?php echo e($value['name'], false); ?> </a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <div class="tab-content">
        <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="tab-pane <?php if($loop->index == 0): ?> active <?php endif; ?>" id="cn_<?php echo e($key, false); ?>">
                <div class="row">
                <div class="col-md-12">
                    <?php if(!empty($value['extra_tags'])): ?>
                        <strong><?php echo app('translator')->get('lang_v1.available_tags'); ?>:</strong>
                        <?php echo $__env->make('notification_template.partials.tags', ['tags' => $value['extra_tags']], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    
                    <?php endif; ?>
                    <?php if(!empty($value['help_text'])): ?>
                    <p class="help-block"><?php echo e($value['help_text'], false); ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-12 mt-10">
                    <div class="form-group">
                        <?php echo Form::label($key . '_subject',
                        __('lang_v1.email_subject').':'); ?>

                        <?php echo Form::text('template_data[' . $key . '][subject]', 
                        $value['subject'], ['class' => 'form-control'
                        , 'placeholder' => __('lang_v1.email_subject'), 'id' => $key . '_subject']); ?>

                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <?php echo Form::label($key . '_cc',
                        'CC:'); ?>

                        <?php echo Form::email('template_data[' . $key . '][cc]', 
                        $value['cc'], ['class' => 'form-control'
                        , 'placeholder' => 'CC', 'id' => $key . '_cc']); ?>

                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <?php echo Form::label($key . '_bcc',
                        'BCC:'); ?>

                        <?php echo Form::email('template_data[' . $key . '][bcc]', 
                        $value['bcc'], ['class' => 'form-control'
                        , 'placeholder' => 'BCC', 'id' => $key . '_bcc']); ?>

                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <?php echo Form::label($key . '_email_body',
                        __('lang_v1.email_body').':'); ?>

                        <?php echo Form::textarea('template_data[' . $key . '][email_body]', 
                        $value['email_body'], ['class' => 'form-control ckeditor'
                        , 'placeholder' => __('lang_v1.email_body'), 'id' => $key . '_email_body', 'rows' => 6]); ?>

                    </div>
                </div>
                <div class="col-md-12 <?php if($key == 'send_ledger'): ?> hide <?php endif; ?>">
                    <div class="form-group">
                        <?php echo Form::label($key . '_sms_body',
                        __('lang_v1.sms_body').':'); ?>

                        <?php echo Form::textarea('template_data[' . $key . '][sms_body]', 
                        $value['sms_body'], ['class' => 'form-control'
                        , 'placeholder' => __('lang_v1.sms_body'), 'id' => $key . '_sms_body', 'rows' => 6]); ?>

                    </div>
                </div>
                <div class="col-md-12 <?php if($key == 'send_ledger'): ?> hide <?php endif; ?>">
                    <div class="form-group">
                        <?php echo Form::label($key . '_whatsapp_text',
                        __('lang_v1.whatsapp_text').':'); ?>

                        <?php echo Form::textarea('template_data[' . $key . '][whatsapp_text]', 
                        $value['whatsapp_text'], ['class' => 'form-control'
                        , 'placeholder' => __('lang_v1.whatsapp_text'), 'id' => $key . '_whatsapp_text', 'rows' => 6]); ?>

                    </div>
                </div>
                <?php if($key == 'new_sale' || $key == 'payment_reminder' || $key == 'monthly_payment_link' || $key == 'payment_approved'): ?>
                    <div class="col-md-12 mt-15">
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <?php echo Form::checkbox('template_data[' . $key . '][auto_send]', 1, $value['auto_send'], ['class' => 'input-icheck']); ?> <?php echo app('translator')->get('lang_v1.autosend_email'); ?>
                            </label>
                            <label class="checkbox-inline">
                                <?php echo Form::checkbox('template_data[' . $key . '][auto_send_sms]', 1, $value['auto_send_sms'], ['class' => 'input-icheck']); ?> <?php echo app('translator')->get('lang_v1.autosend_sms'); ?>
                            </label>
                            <label class="checkbox-inline">
                                <?php echo Form::checkbox('template_data[' . $key . '][auto_send_wa_notif]', 1, $value['auto_send_wa_notif'], ['class' => 'input-icheck']); ?> <?php echo app('translator')->get('lang_v1.auto_send_wa_notif'); ?>
                            </label>
                        </div>
                        <?php if($key == 'payment_reminder'): ?>
                            <p class="help-block"><?php echo app('translator')->get('lang_v1.payment_reminder_help'); ?></p>
                        <?php elseif($key == 'new_sale'): ?>
                            <p class="help-block"><?php echo app('translator')->get('lang_v1.new_sale_notification_help'); ?></p>
                        <?php elseif($key == 'monthly_payment_link'): ?>
                            <div class="alert alert-info">
                                <h4><i class="fa fa-calendar"></i> Monthly SMS Schedule</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="monthly_send_day">Day of Month:</label>
                                            <select name="monthly_schedule[send_day]" id="monthly_send_day" class="form-control">
                                                <?php for($i = 1; $i <= 31; $i++): ?>
                                                    <option value="<?php echo e($i, false); ?>" <?php echo e((isset($monthly_schedule['send_day']) && $monthly_schedule['send_day'] == $i) ? 'selected' : ($i == 1 ? 'selected' : ''), false); ?>><?php echo e($i, false); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="monthly_send_time">Time:</label>
                                            <input type="time" name="monthly_schedule[send_time]" id="monthly_send_time" class="form-control" 
                                                   value="<?php echo e($monthly_schedule['send_time'] ?? '09:00', false); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>&nbsp;</label><br>
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="monthly_schedule[is_enabled]" value="1" 
                                                       <?php echo e((isset($monthly_schedule['is_enabled']) && $monthly_schedule['is_enabled']) ? 'checked' : '', false); ?> 
                                                       class="input-icheck"> Enable Monthly SMS
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <p class="help-block">
                                    <i class="fa fa-info-circle"></i> Monthly SMS will be sent to all customers with outstanding invoices on the specified day and time each month. 
                                    <strong>Note:</strong> SMS gateway must be properly configured with MsgOwl API credentials.
                                </p>
                            </div>
                        <?php elseif($key == 'payment_approved'): ?>
                            <p class="help-block">This notification will be sent when a public payment submission is approved by admin.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div><?php /**PATH /var/www/html/resources/views/notification_template/partials/tabs.blade.php ENDPATH**/ ?>