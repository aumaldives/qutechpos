<?php $__env->startSection('title', __('crm::lang.crm')); ?>

<?php $__env->startSection('content'); ?>

<?php echo $__env->make('crm::layouts.nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<section class="content no-print">
    <div class="row">
        <div class="col-md-4">
            <?php if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule')): ?>
                <div class="col-md-12">
                    <div class="info-box info-box-new-style">
                        <span class="info-box-icon bg-aqua"><i class="fas fa-calendar-check"></i></span>

                        <div class="info-box-content">
                          <span class="info-box-text"><?php echo e(__('crm::lang.todays_followups'), false); ?></span>
                          <span class="info-box-number"><?php echo e($todays_followups, false); ?></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                </div>
            <?php endif; ?>
            <?php if(auth()->user()->can('crm.access_all_leads') || auth()->user()->can('crm.access_own_leads')): ?>
                <div class="col-md-12">
                    <div class="info-box info-box-new-style">
                        <span class="info-box-icon bg-aqua"><i class="fas fa-user-check"></i></span>

                        <div class="info-box-content">
                          <span class="info-box-text"><?php echo e(__('crm::lang.my_leads'), false); ?></span>
                          <span class="info-box-number"><?php echo e($my_leads, false); ?></span>
                        </div>
                        <!-- /.info-box-content -->
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-md-12">
                <div class="info-box info-box-new-style">
                    <span class="info-box-icon bg-aqua"><i class="fas fa-exchange-alt"></i></span>

                    <div class="info-box-content">
                      <span class="info-box-text"><?php echo e(__('crm::lang.my_leads_to_customer_conversion'), false); ?></span>
                      <span class="info-box-number"><?php echo e($my_conversion, false); ?></span>
                    </div>
                    <!-- /.info-box-content -->
                </div>
            </div>
        </div>
        <?php if(auth()->user()->can('crm.access_all_schedule') || auth()->user()->can('crm.access_own_schedule')): ?>
            <div class="col-md-4">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo app('translator')->get('crm::lang.my_followups'); ?></h3>
                    </div>
                    <div class="box-body p-10">
                        <table class="table no-margin">

                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <th><?php echo e($value, false); ?></th>
                                    <td>
                                        <?php if(isset($my_follow_ups_arr[$key])): ?>
                                            <?php echo e($my_follow_ups_arr[$key], false); ?>

                                        <?php else: ?>
                                            0
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(isset($my_follow_ups_arr['__other'])): ?>
                                <tr>
                                    <th><?php echo app('translator')->get('lang_v1.others'); ?></th>
                                    <td>
                                        <?php echo e($my_follow_ups_arr['__other'], false); ?>

                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if(config('constants.enable_crm_call_log')): ?>
            <div class="col-md-4">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?php echo app('translator')->get('crm::lang.my_call_logs'); ?></h3>
                    </div>
                    <div class="box-body p-10">
                        <table class="table no-margin">
                            <tr>
                                <th><?php echo app('translator')->get('crm::lang.calls_today'); ?></th>
                                <td><?php echo e($my_call_logs->calls_today ?? 0, false); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo app('translator')->get('crm::lang.calls_yesterday'); ?></th>
                                <td><?php echo e($my_call_logs->calls_yesterday ?? 0, false); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo app('translator')->get('crm::lang.calls_this_month'); ?></th>
                                <td><?php echo e($my_call_logs->calls_this_month ?? 0, false); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if($is_admin): ?>
        <hr>
        <div class="row row-custom">
            <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
              <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-aqua"><i class="fas fa-user-friends"></i></span>

                <div class="info-box-content">
                  <span class="info-box-text"><?php echo e(__('lang_v1.customers'), false); ?></span>
                  <span class="info-box-number"><?php echo e($total_customers, false); ?></span>
                </div>
                <!-- /.info-box-content -->
              </div>
              <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
              <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-aqua"><i class="fas fa-user-check"></i></span>

                <div class="info-box-content">
                  <span class="info-box-text"><?php echo e(__('crm::lang.leads'), false); ?></span>
                  <span class="info-box-number"><?php echo e($total_leads, false); ?></span>
                </div>
                <!-- /.info-box-content -->
              </div>
              <!-- /.info-box -->
            </div>
            <!-- /.col -->
            <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
              <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-yellow">
                    <i class="fas fa fa-search"></i>
                </span>

                <div class="info-box-content">
                    <span class="info-box-text"><?php echo e(__('crm::lang.sources'), false); ?></span>
                  <span class="info-box-number"><?php echo e($total_sources, false); ?></span>
                </div>
                <!-- /.info-box-content -->
              </div>
              <!-- /.info-box -->
            </div>
            <!-- /.col -->

            <!-- fix for small devices only -->
            <!-- <div class="clearfix visible-sm-block"></div> -->
            <div class="col-md-3 col-sm-6 col-xs-12 col-custom">
              <div class="info-box info-box-new-style">
                <span class="info-box-icon bg-yellow">
                    <i class="fas fa-life-ring"></i>
                </span>

                <div class="info-box-content">
                  <span class="info-box-text"><?php echo e(__('crm::lang.life_stages'), false); ?></span>
                  <span class="info-box-number invoice_due"><?php echo e($total_life_stage, false); ?></span>
                </div>
                <!-- /.info-box-content -->
              </div>
              <!-- /.info-box -->
            </div>
            <!-- /.col -->
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="box box-solid">
                    <div class="box-body p-10">
                        <table class="table no-margin">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('crm::lang.sources'), false); ?></th>
                                    <th><?php echo e(__('sale.total'), false); ?></th>
                                    <th><?php echo e(__('crm::lang.conversion'), false); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($source->name, false); ?></td>
                                        <td>
                                            <?php if(!empty($leads_count_by_source[$source->id])): ?>
                                                <?php echo e($leads_count_by_source[$source->id]['count'], false); ?>

                                            <?php else: ?>
                                                0
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($customers_count_by_source[$source->id]) && !empty($contacts_count_by_source[$source->id])): ?>
                                                <?php
                                                    $conversion = ($customers_count_by_source[$source->id]['count']/$contacts_count_by_source[$source->id]['count']) * 100;
                                                ?>
                                                <?php echo e($conversion . '%', false); ?>

                                            <?php else: ?> 
                                                <?php echo e('0 %', false); ?>

                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="2" class="text-center"><?php echo app('translator')->get('lang_v1.no_data'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="box box-solid">
                    <div class="box-body p-10">
                        <table class="table no-margin">
                            <thead>
                                <tr>
                                    <th><?php echo e(__('crm::lang.life_stages'), false); ?></th>
                                    <th><?php echo e(__('sale.total'), false); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $life_stages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $life_stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td><?php echo e($life_stage->name, false); ?></td>
                                        <td><?php if(!empty($leads_by_life_stage[$life_stage->id])): ?><?php echo e(count($leads_by_life_stage[$life_stage->id]), false); ?> <?php else: ?> 0 <?php endif; ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="2" class="text-center"><?php echo app('translator')->get('lang_v1.no_data'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <i class="fas fa fa-birthday-cake"></i>
                        <h3 class="box-title"><?php echo app('translator')->get('crm::lang.birthdays'); ?></h3>
                        <a data-href="<?php echo e(action([\Modules\Crm\Http\Controllers\CampaignController::class, 'create']), false); ?>" class="btn btn-success btn-xs" id="wish_birthday">
                            <i class="fas fa-paper-plane"></i>
                            <?php echo app('translator')->get('crm::lang.send_wishes'); ?>
                        </a>
                    </div>
                    <div class="box-body p-10">
                        <table class="table no-margin table-striped">
                            <caption><?php echo app('translator')->get('home.today'); ?></caption>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo app('translator')->get('user.name'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $todays_birthdays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $birthday): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="contat_id" name="contat_id[]" value="<?php echo e($birthday['id'], false); ?>" id="contat_id_<?php echo e($birthday['id'], false); ?>">
                                        </td>
                                        <td>
                                            <label for="contat_id_<?php echo e($birthday['id'], false); ?>" class="cursor-pointer fw-100">
                                                <?php echo e($birthday['name'], false); ?>

                                            </label>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="2" class="text-center"><?php echo app('translator')->get('lang_v1.no_data'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if(!empty($upcoming_birthdays)): ?>
                            <hr class="m-2">
                        <?php endif; ?>
                        <table class="table no-margin table-striped">
                            <caption>
                                <?php echo app('translator')->get('crm::lang.upcoming'); ?>
                            </caption>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo app('translator')->get('user.name'); ?></th>
                                    <th><?php echo app('translator')->get('crm::lang.birthday_on'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $upcoming_birthdays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $birthday): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="contat_id" name="contat_id[]" value="<?php echo e($birthday['id'], false); ?>" id="contat_id_<?php echo e($birthday['id'], false); ?>">
                                        </td>
                                        <td>
                                            <label for="contat_id_<?php echo e($birthday['id'], false); ?>" class="cursor-pointer fw-100">
                                                <?php echo e($birthday['name'], false); ?>

                                            </label>
                                        </td>
                                        <td>
                                            <?php echo e(Carbon::createFromFormat('m-d', $birthday['dob'])->format('jS M'), false); ?>

                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="3" class="text-center"><?php echo app('translator')->get('lang_v1.no_data'); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <?php $__env->startComponent('components.widget', ['class' => 'box-solid', 'title' => __('crm::lang.follow_ups_by_user')]); ?>
                <div class="row">
                     <div class="col-md-3">
                        <div class="form-group">
                            <?php echo Form::label('follow_up_user_date_range', __('report.date_range') . ':'); ?>

                            <?php echo Form::text('follow_up_user_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); ?>

                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <?php echo Form::label('followup_category_id', __('crm::lang.followup_category') .':*'); ?>

                            <?php echo Form::select('followup_category_id', $followup_category, null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.all')]); ?>

                        </div>
                    </div>
                    <br/>
                </div>

                    <table class="table table-bordered table-striped" id="follow_ups_by_user_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th><?php echo app('translator')->get('role.user'); ?></th>
                                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <th>
                                        <?php echo e($value, false); ?>

                                    </th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <th>
                                    <?php echo app('translator')->get('lang_v1.none'); ?>
                                </th>
                                <th>
                                    <?php echo app('translator')->get('crm::lang.total_follow_ups'); ?>
                                </th>
                            </tr>
                        </thead>
                    </table>
                <?php echo $__env->renderComponent(); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php $__env->startComponent('components.widget', ['class' => 'box-solid', 'title' => __('crm::lang.lead_to_customer_conversion')]); ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="lead_to_customer_conversion" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>&nbsp;</th>
                                    <th><?php echo app('translator')->get('crm::lang.converted_by'); ?></th>
                                    <th><?php echo app('translator')->get('sale.total'); ?></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                <?php echo $__env->renderComponent(); ?>
            </div>

            <?php if(config('constants.enable_crm_call_log')): ?>
                <div class="col-md-6">
                    <div class="box box-solid">
                        <div class="box-header with-border">
                            <h3 class="box-title"><?php echo app('translator')->get('crm::lang.all_users_call_log'); ?></h3>
                        </div>
                        <div class="box-body p-10">
                            <div class="table-responsive">
                                <table class="table" id="all_users_call_log" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>
                                                <?php echo app('translator')->get('role.user'); ?>
                                            </th>
                                            <th>
                                                <?php echo app('translator')->get('crm::lang.calls_today'); ?>
                                            </th>
                                            <th>
                                                <?php echo app('translator')->get('crm::lang.calls_this_month'); ?>
                                            </th>
                                            <th>
                                                <?php echo app('translator')->get('lang_v1.all'); ?>
                                            </th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
<style type="text/css">
    .fw-100 {
        font-weight: 100;
    }
    
</style>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('javascript'); ?>
	<script src="<?php echo e(asset('modules/crm/js/crm.js?v=' . $asset_v), false); ?>"></script>
    <?php echo $__env->make('crm::reports.report_javascripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <script type="text/javascript">
        $(document).ready(function () {

            $(document).on('click', '#wish_birthday', function () {
                var url = $(this).data('href');
                var contact_ids = [];
                $("input.contat_id").each(function(){
                    if ($(this).is(":checked")) {
                        contact_ids.push($(this).val());
                    }
                });

                if (_.isEmpty(contact_ids)) {
                    alert("<?php echo e(__('crm::lang.plz_select_user'), false); ?>");
                } else {
                    location.href = url+'?contact_ids='+contact_ids;
                }
            });

            <?php if(config('constants.enable_crm_call_log')): ?>
                all_users_call_log = $("#all_users_call_log").DataTable({
                            processing: true,
                            serverSide: true,
                            scrollY: "75vh",
                            scrollX: true,
                            scrollCollapse: true,
                            fixedHeader: false,
                            'ajax': {
                                url: "<?php echo e(action([\Modules\Crm\Http\Controllers\CallLogController::class, 'allUsersCallLog']), false); ?>"
                            },
                            columns: [
                                { data: 'username', name: 'u.username' },
                                { data: 'calls_today', searchable: false },
                                { data: 'calls_yesterday', searchable: false },
                                { data: 'all_calls', searchable: false }
                            ],
                        });
            <?php endif; ?>
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/Modules/Crm/Providers/../Resources/views/crm_dashboard/index.blade.php ENDPATH**/ ?>