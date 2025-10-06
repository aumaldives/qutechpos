<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <?php echo Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'postCloseRegister']), 'method' => 'post' ]); ?>


    <?php echo Form::hidden('user_id', $register_details->user_id); ?>

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title"><?php echo app('translator')->get( 'cash_register.current_register' ); ?> ( <?php echo e(\Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A'), false); ?> - <?php echo e(\Carbon::now()->format('jS M, Y h:i A'), false); ?>)</h3>
    </div>

    <div class="modal-body">
        <?php echo $__env->make('cash_register.payment_details', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <hr>
        
        <!-- Debug: cash_adjustments isset: <?php echo e(isset($cash_adjustments) ? 'YES' : 'NO', false); ?> -->
        <?php if(isset($cash_adjustments)): ?>
            <!-- Debug: cash_adjustments count: <?php echo e($cash_adjustments->count(), false); ?> -->
            <?php if($cash_adjustments->count() > 0): ?>
            <div class="row">
              <div class="col-md-12">
                <h4><?php echo app('translator')->get('cash_register.cash_adjustments'); ?></h4>
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th><?php echo app('translator')->get('messages.date'); ?></th>
                    <th><?php echo app('translator')->get('cash_register.adjustment_type'); ?></th>
                    <th><?php echo app('translator')->get('sale.amount'); ?></th>
                    <th><?php echo app('translator')->get('cash_register.adjustment_reason'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php $__currentLoopData = $cash_adjustments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adjustment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <tr>
                    <td><?php echo e(\Carbon::createFromTimestamp(strtotime($adjustment->created_at))->format(session('business.date_format') . ' ' . 'H:i'), false); ?></td>
                    <td>
                      <?php if($adjustment->type == 'credit'): ?>
                        <span class="label label-success"><?php echo app('translator')->get('cash_register.add_cash'); ?></span>
                      <?php else: ?>
                        <span class="label label-danger"><?php echo app('translator')->get('cash_register.remove_cash'); ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if($adjustment->type == 'credit'): ?>
                        <span class="text-green">+<?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $adjustment->amount, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></span>
                      <?php else: ?>
                        <span class="text-red">-<?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $adjustment->amount, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo e($adjustment->adjustment_reason, false); ?></td>
                  </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
              </table>
            </div>
          </div>
          <hr>
            <?php else: ?>
                <!-- Debug: cash_adjustments count is 0 -->
            <?php endif; ?>
        <?php else: ?>
            <!-- Debug: cash_adjustments not set -->
        <?php endif; ?>
      <div class="row">
        <div class="col-sm-4">
          <div class="form-group">
            <?php echo Form::label('closing_amount', __( 'cash_register.total_cash' ) . ':*'); ?>

              <?php echo Form::text('closing_amount', number_format($register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund - $register_details->total_cash_expense + ($register_details->total_cash_adjustments ?? 0), session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), ['class' => 'form-control input_number', 'required', 'placeholder' => __( 'cash_register.total_cash' ) ]); ?>

          </div>
        </div>
        <div class="col-sm-4">
          <div class="form-group">
            <?php echo Form::label('total_card_slips', __( 'cash_register.total_card_slips' ) . ':*'); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.total_card_slips') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
              <?php echo Form::number('total_card_slips', $register_details->total_card_slips, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_card_slips' ), 'min' => 0 ]); ?>

          </div>
        </div> 
        <div class="col-sm-4">
          <div class="form-group">
            <?php echo Form::label('total_cheques', __( 'cash_register.total_cheques' ) . ':*'); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.total_cheques') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
              <?php echo Form::number('total_cheques', $register_details->total_cheques, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_cheques' ), 'min' => 0 ]); ?>

          </div>
        </div> 
        <hr>
        <div class="col-md-8 col-sm-12">
          <h3><?php echo app('translator')->get( 'lang_v1.cash_denominations' ); ?></h3>
          <?php if(!empty($pos_settings['cash_denominations'])): ?>
            <table class="table table-slim">
              <thead>
                <tr>
                  <th width="20%" class="text-right"><?php echo app('translator')->get('lang_v1.denomination'); ?></th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-center"><?php echo app('translator')->get('lang_v1.count'); ?></th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-left"><?php echo app('translator')->get('sale.subtotal'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $__currentLoopData = explode(',', $pos_settings['cash_denominations']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dnm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                  <td class="text-right"><?php echo e($dnm, false); ?></td>
                  <td class="text-center" >X</td>
                  <td><?php echo Form::number("denominations[$dnm]", null, ['class' => 'form-control cash_denomination input-sm', 'min' => 0, 'data-denomination' => $dnm, 'style' => 'width: 100px; margin:auto;' ]); ?></td>
                  <td class="text-center">=</td>
                  <td class="text-left">
                    <span class="denomination_subtotal">0</span>
                  </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="4" class="text-center"><?php echo app('translator')->get('sale.total'); ?></th>
                  <td><span class="denomination_total">0</span></td>
                </tr>
              </tfoot>
            </table>
          <?php else: ?>
            <p class="help-block"><?php echo app('translator')->get('lang_v1.denomination_add_help_text'); ?></p>
          <?php endif; ?>
        </div>
        <hr>
        <div class="col-sm-12">
          <div class="form-group">
            <?php echo Form::label('closing_note', __( 'cash_register.closing_note' ) . ':'); ?>

              <?php echo Form::textarea('closing_note', null, ['class' => 'form-control', 'placeholder' => __( 'cash_register.closing_note' ), 'rows' => 3 ]); ?>

          </div>
        </div>
      </div> 

      <div class="row">
        <div class="col-xs-6">
          <b><?php echo app('translator')->get('report.user'); ?>:</b> <?php echo e($register_details->user_name, false); ?><br>
          <b><?php echo app('translator')->get('business.email'); ?>:</b> <?php echo e($register_details->email, false); ?><br>
          <b><?php echo app('translator')->get('business.business_location'); ?>:</b> <?php echo e($register_details->location_name, false); ?><br>
        </div>
        <?php if(!empty($register_details->closing_note)): ?>
          <div class="col-xs-6">
            <strong><?php echo app('translator')->get('cash_register.closing_note'); ?>:</strong><br>
            <?php echo e($register_details->closing_note, false); ?>

          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo app('translator')->get( 'messages.cancel' ); ?></button>
      <button type="submit" class="btn btn-primary"><?php echo app('translator')->get( 'cash_register.close_register' ); ?></button>
    </div>
    <?php echo Form::close(); ?>

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog --><?php /**PATH /var/www/html/resources/views/cash_register/close_register_modal.blade.php ENDPATH**/ ?>