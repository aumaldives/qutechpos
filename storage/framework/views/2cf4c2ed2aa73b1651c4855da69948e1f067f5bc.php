<!-- default value -->
<?php
    $go_back_url = action([\App\Http\Controllers\SellPosController::class, 'index']);
    $transaction_sub_type = '';
    $view_suspended_sell_url = action([\App\Http\Controllers\SellController::class, 'index']).'?suspended=1';
    $pos_redirect_url = action([\App\Http\Controllers\SellPosController::class, 'create']);
?>

<?php if(!empty($pos_module_data)): ?>
    <?php $__currentLoopData = $pos_module_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            if(!empty($value['go_back_url'])) {
                $go_back_url = $value['go_back_url'];
            }

            if(!empty($value['transaction_sub_type'])) {
                $transaction_sub_type = $value['transaction_sub_type'];
                $view_suspended_sell_url .= '&transaction_sub_type='.$transaction_sub_type;
                $pos_redirect_url .= '?sub_type='.$transaction_sub_type;
            }
        ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<input type="hidden" name="transaction_sub_type" id="transaction_sub_type" value="<?php echo e($transaction_sub_type, false); ?>">
<?php $request = app('Illuminate\Http\Request'); ?>
<div class="col-md-12 no-print pos-header">
  <input type="hidden" id="pos_redirect_url" value="<?php echo e($pos_redirect_url, false); ?>">
  <div class="row">
    <div class="col-md-6">
      <div class="m-6 mt-5" style="display: flex;">
        <p ><strong><?php echo app('translator')->get('sale.location'); ?>: &nbsp;</strong> 
          <?php if(empty($transaction->location_id)): ?>
            <?php if(count($business_locations) > 1): ?>
            <div style="width: 28%;margin-bottom: 5px;">
               <?php echo Form::select('select_location_id', $business_locations, $default_location->id ?? null , ['class' => 'form-control input-sm',
                'id' => 'select_location_id', 
                'required', 'autofocus'], $bl_attributes); ?>

            </div>
            <?php else: ?>
              <?php echo e($default_location->name, false); ?>

            <?php endif; ?>
          <?php endif; ?>

          <?php if(!empty($transaction->location_id)): ?> <?php echo e($transaction->location->name, false); ?> <?php endif; ?> &nbsp; <span class="curr_datetime"><?php echo e(\Carbon::createFromTimestamp(strtotime('now'))->format(session('business.date_format') . ' ' . 'H:i'), false); ?></span> <i class="fas fa-keyboard hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="<?php echo $__env->make('sale_pos.partials.keyboard_shortcuts_details', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>" data-html="true" data-trigger="hover" data-original-title="" title=""></i>
        </p>
      </div>
    </div>
    <div class="col-md-6">
      <a href="<?php echo e($go_back_url, false); ?>" title="<?php echo e(__('lang_v1.go_back'), false); ?>" class="btn btn-info btn-flat m-6 btn-xs m-5 pull-right">
        <strong><i class="fas fa-arrow-left fa-lg"></i></strong>
      </a>
      <?php if(!empty($pos_settings['inline_service_staff'])): ?>
        <button type="button" id="show_service_staff_availability" title="<?php echo e(__('lang_v1.service_staff_availability'), false); ?>" class="btn btn-primary btn-flat m-6 btn-xs m-5 pull-right" data-container=".view_modal" 
          data-href="<?php echo e(action([\App\Http\Controllers\SellPosController::class, 'showServiceStaffAvailibility']), false); ?>">
            <strong><i class="fas fa-users fa-lg"></i></strong>
        </button>
      <?php endif; ?>

      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('close_cash_register')): ?>
      <button type="button" id="close_register" title="<?php echo e(__('cash_register.close_register'), false); ?>" class="btn btn-danger btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".close_register_modal" 
          data-href="<?php echo e(action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister']), false); ?>">
            <strong><i class="fas fa-times-circle fa-lg"></i></strong>
      </button>
      <?php endif; ?>
      
      <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view_cash_register')): ?>
      <button type="button" id="register_details" title="<?php echo e(__('cash_register.register_details'), false); ?>" class="btn btn-success btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".register_details_modal" 
          data-href="<?php echo e(action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails']), false); ?>">
            <strong><i class="fas fa-cash-register fa-lg" aria-hidden="true"></i></strong>
      </button>
      
      <?php endif; ?>

      <button type="button" class="btn btn-danger btn-flat m-6 btn-xs m-5 pull-right popover-default" id="return_sale" title="<?php echo app('translator')->get('lang_v1.sell_return'); ?>" data-toggle="popover" data-trigger="click" data-content='<div class="m-8"><input type="text" class="form-control" placeholder="<?php echo app('translator')->get("sale.invoice_no"); ?>" id="send_for_sell_return_invoice_no"></div><div class="w-100 text-center"><button type="button" class="btn btn-danger" id="send_for_sell_return"><?php echo app('translator')->get("lang_v1.send"); ?></button></div>' data-html="true" data-placement="bottom">
            <strong><i class="fas fa-undo fa-lg"></i></strong>
      </button>

      <button type="button" title="<?php echo e(__('lang_v1.full_screen'), false); ?>" class="btn btn-primary btn-flat m-6 hidden-xs btn-xs m-5 pull-right" id="full_screen">
            <strong><i class="fas fa-expand fa-lg"></i></strong>
      </button>

      <button type="button" id="view_suspended_sales" title="<?php echo e(__('lang_v1.view_suspended_sales'), false); ?>" class="btn bg-yellow btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".view_modal" 
          data-href="<?php echo e($view_suspended_sell_url, false); ?>">
            <strong><i class="fas fa-pause-circle fa-lg"></i></strong>
      </button>
      <?php if(empty($pos_settings['hide_product_suggestion']) && isMobile()): ?>
        <button type="button" title="<?php echo e(__('lang_v1.view_products'), false); ?>"   
          data-placement="bottom" class="btn btn-success btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-toggle="modal" data-target="#mobile_product_suggestion_modal">
            <strong><i class="fas fa-cubes fa-lg"></i></strong>
        </button>
      <?php endif; ?>

      <?php if(Session::get('business.name') == 'Agro Mart'): ?>
        <button type="button" id="customer-screen-btn" title="Customer Screen" data-toggle="tooltip" data-placement="bottom" class="btn btn-success btn-flat m-6 btn-xs m-5 pull-right" onclick="window.open('<?php echo e(route('pos.customer-display'), false); ?>', '_blank', 'location=no,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,width=1024,height=768')">
          <i class="fas fa-desktop fa-lg"></i>
          <strong>Customer Screen</strong>
        </button>

        <button type="button" id="bank_transfers_btn" title="Bank Transfers" data-toggle="tooltip" data-placement="bottom" class="btn bg-purple btn-flat m-6 btn-xs m-5 pull-right">
          <i class="fa fa-university fa-lg"></i>
          <strong>Bank Transfers</strong>
        </button>
      <?php endif; ?>

        <?php if(in_array('pos_sale', $enabled_modules) && !empty($transaction_sub_type)): ?>
          <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sell.create')): ?>
            <a href="<?php echo e(action([\App\Http\Controllers\SellPosController::class, 'create']), false); ?>" title="<?php echo app('translator')->get('sale.pos_sale'); ?>" class="btn btn-success btn-flat m-6 btn-xs m-5 pull-right">
              <strong><i class="fas fa-cash-register"></i> &nbsp; <?php echo app('translator')->get('sale.pos_sale'); ?></strong>
            </a>
          <?php endif; ?>
        <?php endif; ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view_cash_register')): ?>
        <button type="button" id="cash_adjustment" title="<?php echo e(__('cash_register.cash_adjustment'), false); ?>" class="btn btn-warning btn-flat m-6 btn-xs m-5 btn-modal pull-right" data-container=".cash_adjustment_modal"
          data-href="<?php echo e(action([\App\Http\Controllers\CashRegisterController::class, 'getCashAdjustment']), false); ?>">
            <strong><i class="fas fa-money-bill-alt fa-lg" aria-hidden="true"></i> <?php echo app('translator')->get('cash_register.cash_adjustment'); ?></strong>
        </button>
        <?php endif; ?>

    </div>
    
  </div>
</div>
<?php /**PATH /var/www/html/resources/views/layouts/partials/header-pos.blade.php ENDPATH**/ ?>