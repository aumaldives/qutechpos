<!-- Custom Tabs -->
<div class="nav-tabs-custom">
    <ul class="nav nav-tabs">
        @foreach($templates as $key => $value)
            <li @if($loop->index == 0) class="active" @endif>
                <a href="#cn_{{$key}}" data-toggle="tab" aria-expanded="true">
                {{$value['name']}} </a>
            </li>
        @endforeach
    </ul>
    <div class="tab-content">
        @foreach($templates as $key => $value)
            <div class="tab-pane @if($loop->index == 0) active @endif" id="cn_{{$key}}">
                <div class="row">
                <div class="col-md-12">
                    @if(!empty($value['extra_tags']))
                        <strong>@lang('lang_v1.available_tags'):</strong>
                        @include('notification_template.partials.tags', ['tags' => $value['extra_tags']])
                    
                    @endif
                    @if(!empty($value['help_text']))
                    <p class="help-block">{{$value['help_text']}}</p>
                    @endif
                </div>
                <div class="col-md-12 mt-10">
                    <div class="form-group">
                        {!! Form::label($key . '_subject',
                        __('lang_v1.email_subject').':') !!}
                        {!! Form::text('template_data[' . $key . '][subject]', 
                        $value['subject'], ['class' => 'form-control'
                        , 'placeholder' => __('lang_v1.email_subject'), 'id' => $key . '_subject']); !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label($key . '_cc',
                        'CC:') !!}
                        {!! Form::email('template_data[' . $key . '][cc]', 
                        $value['cc'], ['class' => 'form-control'
                        , 'placeholder' => 'CC', 'id' => $key . '_cc']); !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label($key . '_bcc',
                        'BCC:') !!}
                        {!! Form::email('template_data[' . $key . '][bcc]', 
                        $value['bcc'], ['class' => 'form-control'
                        , 'placeholder' => 'BCC', 'id' => $key . '_bcc']); !!}
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label($key . '_email_body',
                        __('lang_v1.email_body').':') !!}
                        {!! Form::textarea('template_data[' . $key . '][email_body]', 
                        $value['email_body'], ['class' => 'form-control ckeditor'
                        , 'placeholder' => __('lang_v1.email_body'), 'id' => $key . '_email_body', 'rows' => 6]); !!}
                    </div>
                </div>
                <div class="col-md-12 @if($key == 'send_ledger') hide @endif">
                    <div class="form-group">
                        {!! Form::label($key . '_sms_body',
                        __('lang_v1.sms_body').':') !!}
                        {!! Form::textarea('template_data[' . $key . '][sms_body]', 
                        $value['sms_body'], ['class' => 'form-control'
                        , 'placeholder' => __('lang_v1.sms_body'), 'id' => $key . '_sms_body', 'rows' => 6]); !!}
                    </div>
                </div>
                <div class="col-md-12 @if($key == 'send_ledger') hide @endif">
                    <div class="form-group">
                        {!! Form::label($key . '_whatsapp_text',
                        __('lang_v1.whatsapp_text').':') !!}
                        {!! Form::textarea('template_data[' . $key . '][whatsapp_text]', 
                        $value['whatsapp_text'], ['class' => 'form-control'
                        , 'placeholder' => __('lang_v1.whatsapp_text'), 'id' => $key . '_whatsapp_text', 'rows' => 6]); !!}
                    </div>
                </div>
                @if($key == 'new_sale' || $key == 'payment_reminder' || $key == 'monthly_payment_link' || $key == 'payment_approved')
                    <div class="col-md-12 mt-15">
                        <div class="form-group">
                            <label class="checkbox-inline">
                                {!! Form::checkbox('template_data[' . $key . '][auto_send]', 1, $value['auto_send'], ['class' => 'input-icheck']); !!} @lang('lang_v1.autosend_email')
                            </label>
                            <label class="checkbox-inline">
                                {!! Form::checkbox('template_data[' . $key . '][auto_send_sms]', 1, $value['auto_send_sms'], ['class' => 'input-icheck']); !!} @lang('lang_v1.autosend_sms')
                            </label>
                            <label class="checkbox-inline">
                                {!! Form::checkbox('template_data[' . $key . '][auto_send_wa_notif]', 1, $value['auto_send_wa_notif'], ['class' => 'input-icheck']); !!} @lang('lang_v1.auto_send_wa_notif')
                            </label>
                        </div>
                        @if($key == 'payment_reminder')
                            <p class="help-block">@lang('lang_v1.payment_reminder_help')</p>
                        @elseif($key == 'new_sale')
                            <p class="help-block">@lang('lang_v1.new_sale_notification_help')</p>
                        @elseif($key == 'monthly_payment_link')
                            <div class="alert alert-info">
                                <h4><i class="fa fa-calendar"></i> Monthly SMS Schedule</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="monthly_send_day">Day of Month:</label>
                                            <select name="monthly_schedule[send_day]" id="monthly_send_day" class="form-control">
                                                @for($i = 1; $i <= 31; $i++)
                                                    <option value="{{ $i }}" {{ (isset($monthly_schedule['send_day']) && $monthly_schedule['send_day'] == $i) ? 'selected' : ($i == 1 ? 'selected' : '') }}>{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="monthly_send_time">Time:</label>
                                            <input type="time" name="monthly_schedule[send_time]" id="monthly_send_time" class="form-control" 
                                                   value="{{ $monthly_schedule['send_time'] ?? '09:00' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>&nbsp;</label><br>
                                            <label class="checkbox-inline">
                                                <input type="checkbox" name="monthly_schedule[is_enabled]" value="1" 
                                                       {{ (isset($monthly_schedule['is_enabled']) && $monthly_schedule['is_enabled']) ? 'checked' : '' }} 
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
                        @elseif($key == 'payment_approved')
                            <p class="help-block">This notification will be sent when a public payment submission is approved by admin.</p>
                        @endif
                    </div>
                @endif
                </div>
            </div>
        @endforeach
    </div>
</div>