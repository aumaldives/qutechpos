<!-- Bank Transfer Settings Form -->
{!! Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postBusinessSettings']), 'method' => 'post', 'id' => 'business_bank_transfer_form', 'files' => true ]) !!}

<div class="row">
    <div class="col-xs-12">
        <h4>@lang('lang_v1.bank_transfer_settings') <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="@lang('lang_v1.bank_transfer_settings_tooltip')" aria-hidden="true"></i></h4>
    </div>
</div>

<hr/>

    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('enable_bank_transfer_payment', 1, !empty($business->enable_bank_transfer_payment), ['class' => 'input-icheck', 'id' => 'enable_bank_transfer_payment']); !!}
                        @lang('lang_v1.enable_bank_transfer_payment')
                        <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="@lang('lang_v1.enable_bank_transfer_payment_tooltip')" aria-hidden="true"></i>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div id="bank_transfer_settings" style="{{empty($business->enable_bank_transfer_payment) ? 'display:none;' : ''}}">
        
        <!-- Bank Accounts Section -->
        <div class="row">
            <div class="col-xs-12">
                <h5>@lang('lang_v1.bank_accounts_configuration')</h5>
                <p class="help-block">@lang('lang_v1.bank_accounts_configuration_help')</p>
            </div>
        </div>

        <!-- Existing Bank Accounts -->
        <div class="row" id="bank-accounts-list">
            <div class="col-xs-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="bank_accounts_table">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.bank_name')</th>
                                <th>@lang('lang_v1.account_name')</th>
                                <th>@lang('lang_v1.account_number')</th>
                                <th>@lang('lang_v1.location')</th>
                                <th>@lang('lang_v1.status')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(!empty($bank_accounts))
                                @foreach($bank_accounts as $account)
                                    <tr data-account-id="{{$account->id}}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($account->bank_logo)
                                                    <img src="{{$account->bank_logo}}" alt="{{$account->bank_name}}" class="bank-logo-small" style="width: 30px; height: 30px; margin-right: 10px; object-fit: contain;">
                                                @endif
                                                {{$account->bank_name}}
                                            </div>
                                        </td>
                                        <td>{{$account->account_name}}</td>
                                        <td>{{$account->account_number}}</td>
                                        <td>
                                            @if($account->location_name)
                                                {{$account->location_name}}
                                            @else
                                                <span class="label label-info">@lang('lang_v1.all_locations')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($account->is_active)
                                                <span class="label label-success">@lang('lang_v1.active')</span>
                                            @else
                                                <span class="label label-default">@lang('lang_v1.inactive')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-primary edit-bank-account" data-account-id="{{$account->id}}" title="@lang('messages.edit')">
                                                <i class="glyphicon glyphicon-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger delete-bank-account" data-account-id="{{$account->id}}" title="@lang('messages.delete')">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Bank Account Button -->
        <div class="row">
            <div class="col-xs-12">
                <button type="button" class="btn btn-primary" id="add_bank_account">
                    <i class="fa fa-plus"></i> @lang('lang_v1.add_bank_account')
                </button>
            </div>
        </div>

        <hr/>

        <!-- Payment Processing Settings -->
        <div class="row">
            <div class="col-xs-12">
                <h5>@lang('lang_v1.payment_processing_settings')</h5>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('auto_approve_bank_payments', 1, !empty($business->auto_approve_bank_payments), ['class' => 'input-icheck']); !!}
                            @lang('lang_v1.auto_approve_bank_payments')
                        </label>
                        <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="@lang('lang_v1.auto_approve_bank_payments_tooltip')" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('send_bank_payment_notifications', 1, !empty($business->send_bank_payment_notifications), ['class' => 'input-icheck']); !!}
                            @lang('lang_v1.send_bank_payment_notifications')
                        </label>
                        <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="@lang('lang_v1.send_bank_payment_notifications_tooltip')" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('max_bank_transfer_amount', __('lang_v1.max_bank_transfer_amount') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-money"></i>
                        </span>
                        {!! Form::number('max_bank_transfer_amount', $business->max_bank_transfer_amount ?? '', ['class' => 'form-control', 'placeholder' => __('lang_v1.max_amount_placeholder'), 'min' => '0', 'step' => '0.01']); !!}
                    </div>
                    <p class="help-block">@lang('lang_v1.max_bank_transfer_amount_help')</p>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('min_bank_transfer_amount', __('lang_v1.min_bank_transfer_amount') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-money"></i>
                        </span>
                        {!! Form::number('min_bank_transfer_amount', $business->min_bank_transfer_amount ?? '0.01', ['class' => 'form-control', 'placeholder' => __('lang_v1.min_amount_placeholder'), 'min' => '0.01', 'step' => '0.01']); !!}
                    </div>
                    <p class="help-block">@lang('lang_v1.min_bank_transfer_amount_help')</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    {!! Form::label('bank_transfer_instructions', __('lang_v1.bank_transfer_instructions') . ':') !!}
                    {!! Form::textarea('bank_transfer_instructions', $business->bank_transfer_instructions ?? '', ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('lang_v1.bank_transfer_instructions_placeholder')]); !!}
                    <p class="help-block">@lang('lang_v1.bank_transfer_instructions_help')</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Bank Account Modal -->
<div class="modal fade" id="bank_account_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modal_title">@lang('lang_v1.add_bank_account')</h4>
            </div>
            <div class="modal-body">
                <form id="bank_account_form">
                    <input type="hidden" id="account_id" name="account_id">
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="bank_id">@lang('lang_v1.select_bank') <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="bank_id" name="bank_id" required>
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach($system_banks as $bank)
                                        <option value="{{$bank->id}}" data-logo="{{$bank->logo_url}}">
                                            {{$bank->name}} - {{$bank->full_name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="location_id">@lang('lang_v1.location')</label>
                                <select class="form-control select2" id="location_id" name="location_id">
                                    <option value="">@lang('lang_v1.all_locations')</option>
                                    @foreach($business_locations as $location_id => $location_name)
                                        <option value="{{$location_id}}">{{$location_name}}</option>
                                    @endforeach
                                </select>
                                <p class="help-block">@lang('lang_v1.location_help')</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="account_name">@lang('lang_v1.account_name') <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_name" name="account_name" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="account_number">@lang('lang_v1.account_number') <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_number" name="account_number" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="account_type">@lang('lang_v1.account_type')</label>
                                <select class="form-control" id="account_type" name="account_type">
                                    <option value="Current">@lang('lang_v1.current_account')</option>
                                    <option value="Savings">@lang('lang_v1.savings_account')</option>
                                    <option value="Business">@lang('lang_v1.business_account')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="swift_code">@lang('lang_v1.swift_code')</label>
                                <input type="text" class="form-control" id="swift_code" name="swift_code">
                                <p class="help-block">@lang('lang_v1.swift_code_help')</p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="branch_name">@lang('lang_v1.branch_name')</label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <br>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="is_active" name="is_active" checked>
                                        @lang('lang_v1.active')
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="notes">@lang('lang_v1.notes')</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="button" class="btn btn-primary" id="save_bank_account">
                    <i class="fa fa-save"></i> @lang('messages.save')
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Submit Button -->
<div class="row">
    <div class="col-sm-12">
        <hr/>
        <button type="submit" class="btn btn-primary pull-right">
            <i class="fa fa-save"></i> @lang('messages.save')
        </button>
        <div class="clearfix"></div>
    </div>
</div>

{!! Form::close() !!}

