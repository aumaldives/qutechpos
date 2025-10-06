<!-- Bank Transfer Settings Form -->
<?php echo Form::open(['url' => action([\App\Http\Controllers\BusinessController::class, 'postBusinessSettings']), 'method' => 'post', 'id' => 'business_bank_transfer_form', 'files' => true ]); ?>


<div class="row">
    <div class="col-xs-12">
        <h4><?php echo app('translator')->get('lang_v1.bank_transfer_settings'); ?> <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="<?php echo app('translator')->get('lang_v1.bank_transfer_settings_tooltip'); ?>" aria-hidden="true"></i></h4>
    </div>
</div>

<hr/>

    <div class="row">
        <div class="col-sm-12">
            <div class="form-group">
                <div class="checkbox">
                    <label>
                        <?php echo Form::checkbox('enable_bank_transfer_payment', 1, !empty($business->enable_bank_transfer_payment), ['class' => 'input-icheck', 'id' => 'enable_bank_transfer_payment']); ?>

                        <?php echo app('translator')->get('lang_v1.enable_bank_transfer_payment'); ?>
                        <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="<?php echo app('translator')->get('lang_v1.enable_bank_transfer_payment_tooltip'); ?>" aria-hidden="true"></i>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div id="bank_transfer_settings" style="<?php echo e(empty($business->enable_bank_transfer_payment) ? 'display:none;' : '', false); ?>">
        
        <!-- Bank Accounts Section -->
        <div class="row">
            <div class="col-xs-12">
                <h5><?php echo app('translator')->get('lang_v1.bank_accounts_configuration'); ?></h5>
                <p class="help-block"><?php echo app('translator')->get('lang_v1.bank_accounts_configuration_help'); ?></p>
            </div>
        </div>

        <!-- Existing Bank Accounts -->
        <div class="row" id="bank-accounts-list">
            <div class="col-xs-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="bank_accounts_table">
                        <thead>
                            <tr>
                                <th><?php echo app('translator')->get('lang_v1.bank_name'); ?></th>
                                <th><?php echo app('translator')->get('lang_v1.account_name'); ?></th>
                                <th><?php echo app('translator')->get('lang_v1.account_number'); ?></th>
                                <th><?php echo app('translator')->get('lang_v1.location'); ?></th>
                                <th><?php echo app('translator')->get('lang_v1.status'); ?></th>
                                <th><?php echo app('translator')->get('messages.action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($bank_accounts)): ?>
                                <?php $__currentLoopData = $bank_accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr data-account-id="<?php echo e($account->id, false); ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if($account->bank_logo): ?>
                                                    <img src="<?php echo e($account->bank_logo, false); ?>" alt="<?php echo e($account->bank_name, false); ?>" class="bank-logo-small" style="width: 30px; height: 30px; margin-right: 10px; object-fit: contain;">
                                                <?php endif; ?>
                                                <?php echo e($account->bank_name, false); ?>

                                            </div>
                                        </td>
                                        <td><?php echo e($account->account_name, false); ?></td>
                                        <td><?php echo e($account->account_number, false); ?></td>
                                        <td>
                                            <?php if($account->location_name): ?>
                                                <?php echo e($account->location_name, false); ?>

                                            <?php else: ?>
                                                <span class="label label-info"><?php echo app('translator')->get('lang_v1.all_locations'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($account->is_active): ?>
                                                <span class="label label-success"><?php echo app('translator')->get('lang_v1.active'); ?></span>
                                            <?php else: ?>
                                                <span class="label label-default"><?php echo app('translator')->get('lang_v1.inactive'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-primary edit-bank-account" data-account-id="<?php echo e($account->id, false); ?>" title="<?php echo app('translator')->get('messages.edit'); ?>">
                                                <i class="glyphicon glyphicon-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger delete-bank-account" data-account-id="<?php echo e($account->id, false); ?>" title="<?php echo app('translator')->get('messages.delete'); ?>">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Bank Account Button -->
        <div class="row">
            <div class="col-xs-12">
                <button type="button" class="btn btn-primary" id="add_bank_account">
                    <i class="fa fa-plus"></i> <?php echo app('translator')->get('lang_v1.add_bank_account'); ?>
                </button>
            </div>
        </div>

        <hr/>

        <!-- Payment Processing Settings -->
        <div class="row">
            <div class="col-xs-12">
                <h5><?php echo app('translator')->get('lang_v1.payment_processing_settings'); ?></h5>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <?php echo Form::checkbox('auto_approve_bank_payments', 1, !empty($business->auto_approve_bank_payments), ['class' => 'input-icheck']); ?>

                            <?php echo app('translator')->get('lang_v1.auto_approve_bank_payments'); ?>
                        </label>
                        <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="<?php echo app('translator')->get('lang_v1.auto_approve_bank_payments_tooltip'); ?>" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            <?php echo Form::checkbox('send_bank_payment_notifications', 1, !empty($business->send_bank_payment_notifications), ['class' => 'input-icheck']); ?>

                            <?php echo app('translator')->get('lang_v1.send_bank_payment_notifications'); ?>
                        </label>
                        <i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="<?php echo app('translator')->get('lang_v1.send_bank_payment_notifications_tooltip'); ?>" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <?php echo Form::label('max_bank_transfer_amount', __('lang_v1.max_bank_transfer_amount') . ':'); ?>

                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-money"></i>
                        </span>
                        <?php echo Form::number('max_bank_transfer_amount', $business->max_bank_transfer_amount ?? '', ['class' => 'form-control', 'placeholder' => __('lang_v1.max_amount_placeholder'), 'min' => '0', 'step' => '0.01']); ?>

                    </div>
                    <p class="help-block"><?php echo app('translator')->get('lang_v1.max_bank_transfer_amount_help'); ?></p>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <?php echo Form::label('min_bank_transfer_amount', __('lang_v1.min_bank_transfer_amount') . ':'); ?>

                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-money"></i>
                        </span>
                        <?php echo Form::number('min_bank_transfer_amount', $business->min_bank_transfer_amount ?? '0.01', ['class' => 'form-control', 'placeholder' => __('lang_v1.min_amount_placeholder'), 'min' => '0.01', 'step' => '0.01']); ?>

                    </div>
                    <p class="help-block"><?php echo app('translator')->get('lang_v1.min_bank_transfer_amount_help'); ?></p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <?php echo Form::label('bank_transfer_instructions', __('lang_v1.bank_transfer_instructions') . ':'); ?>

                    <?php echo Form::textarea('bank_transfer_instructions', $business->bank_transfer_instructions ?? '', ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('lang_v1.bank_transfer_instructions_placeholder')]); ?>

                    <p class="help-block"><?php echo app('translator')->get('lang_v1.bank_transfer_instructions_help'); ?></p>
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
                <h4 class="modal-title" id="modal_title"><?php echo app('translator')->get('lang_v1.add_bank_account'); ?></h4>
            </div>
            <div class="modal-body">
                <form id="bank_account_form">
                    <input type="hidden" id="account_id" name="account_id">
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="bank_id"><?php echo app('translator')->get('lang_v1.select_bank'); ?> <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="bank_id" name="bank_id" required>
                                    <option value=""><?php echo app('translator')->get('messages.please_select'); ?></option>
                                    <?php $__currentLoopData = $system_banks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $bank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($bank->id, false); ?>" data-logo="<?php echo e($bank->logo_url, false); ?>">
                                            <?php echo e($bank->name, false); ?> - <?php echo e($bank->full_name, false); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="location_id"><?php echo app('translator')->get('lang_v1.location'); ?></label>
                                <select class="form-control select2" id="location_id" name="location_id">
                                    <option value=""><?php echo app('translator')->get('lang_v1.all_locations'); ?></option>
                                    <?php $__currentLoopData = $business_locations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $location_id => $location_name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($location_id, false); ?>"><?php echo e($location_name, false); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <p class="help-block"><?php echo app('translator')->get('lang_v1.location_help'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="account_name"><?php echo app('translator')->get('lang_v1.account_name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_name" name="account_name" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="account_number"><?php echo app('translator')->get('lang_v1.account_number'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_number" name="account_number" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="account_type"><?php echo app('translator')->get('lang_v1.account_type'); ?></label>
                                <select class="form-control" id="account_type" name="account_type">
                                    <option value="Current"><?php echo app('translator')->get('lang_v1.current_account'); ?></option>
                                    <option value="Savings"><?php echo app('translator')->get('lang_v1.savings_account'); ?></option>
                                    <option value="Business"><?php echo app('translator')->get('lang_v1.business_account'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="swift_code"><?php echo app('translator')->get('lang_v1.swift_code'); ?></label>
                                <input type="text" class="form-control" id="swift_code" name="swift_code">
                                <p class="help-block"><?php echo app('translator')->get('lang_v1.swift_code_help'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="branch_name"><?php echo app('translator')->get('lang_v1.branch_name'); ?></label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <br>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="is_active" name="is_active" checked>
                                        <?php echo app('translator')->get('lang_v1.active'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label for="notes"><?php echo app('translator')->get('lang_v1.notes'); ?></label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo app('translator')->get('messages.cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="save_bank_account">
                    <i class="fa fa-save"></i> <?php echo app('translator')->get('messages.save'); ?>
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
            <i class="fa fa-save"></i> <?php echo app('translator')->get('messages.save'); ?>
        </button>
        <div class="clearfix"></div>
    </div>
</div>

<?php echo Form::close(); ?>


<?php /**PATH /var/www/html/resources/views/business/partials/settings_bank_transfer.blade.php ENDPATH**/ ?>