<?php $__env->startSection('title', __('purchase.edit_purchase')); ?>

<?php $__env->startSection('content'); ?>

<?php
  $custom_labels = json_decode(session('business.custom_labels'), true);
?>
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1><?php echo app('translator')->get('purchase.edit_purchase'); ?> <i class="fa fa-keyboard-o hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="<?php echo $__env->make('purchase.partials.keyboard_shortcuts_details', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>" data-html="true" data-trigger="hover" data-original-title="" title=""></i></h1>
</section>

<!-- Main content -->
<section class="content">

  <!-- Page level currency setting -->
  <input type="hidden" id="p_code" value="<?php echo e($currency_details->code, false); ?>">
  <input type="hidden" id="p_symbol" value="<?php echo e($currency_details->symbol, false); ?>">
  <input type="hidden" id="p_thousand" value="<?php echo e($currency_details->thousand_separator, false); ?>">
  <input type="hidden" id="p_decimal" value="<?php echo e($currency_details->decimal_separator, false); ?>">

  <?php echo $__env->make('layouts.partials.error', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <?php echo Form::open(['url' =>  action([\App\Http\Controllers\PurchaseController::class, 'update'] , [$purchase->id] ), 'method' => 'PUT', 'id' => 'add_purchase_form', 'files' => true ]); ?>


  <?php
    $currency_precision = session('business.currency_precision', 2);
  ?>

  <input type="hidden" id="purchase_id" value="<?php echo e($purchase->id, false); ?>">

    <?php $__env->startComponent('components.widget', ['class' => 'box-primary']); ?>
        <div class="row">
            <div class="<?php if(!empty($default_purchase_status)): ?> col-sm-4 <?php else: ?> col-sm-3 <?php endif; ?>">
              <div class="form-group">
                <?php echo Form::label('supplier_id', __('purchase.supplier') . ':*'); ?>

                <div class="input-group">
                  <span class="input-group-addon">
                    <i class="fa fa-user"></i>
                  </span>
                  <?php echo Form::select('contact_id', [ $purchase->contact_id => $purchase->contact->name], $purchase->contact_id, ['class' => 'form-control', 'placeholder' => __('messages.please_select') , 'required', 'id' => 'supplier_id']); ?>

                  <span class="input-group-btn">
                    <button type="button" class="btn btn-default bg-white btn-flat add_new_supplier" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
                  </span>
                </div>
              </div>
              <strong>
                <?php echo app('translator')->get('business.address'); ?>:
              </strong>
              <div id="supplier_address_div">
                <?php echo $purchase->contact->contact_address; ?>

              </div>
            </div>

            <div class="<?php if(!empty($default_purchase_status)): ?> col-sm-4 <?php else: ?> col-sm-3 <?php endif; ?>">
              <div class="form-group">
                <?php echo Form::label('ref_no', __('purchase.ref_no') . '*'); ?>

                <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.leave_empty_to_autogenerate') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
                <?php echo Form::text('ref_no', $purchase->ref_no, ['class' => 'form-control', 'required']); ?>

              </div>
            </div>
            
            <div class="<?php if(!empty($default_purchase_status)): ?> col-sm-4 <?php else: ?> col-sm-3 <?php endif; ?>">
              <div class="form-group">
                <?php echo Form::label('transaction_date', __('purchase.purchase_date') . ':*'); ?>

                <div class="input-group">
                  <span class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </span>
                  <?php echo Form::text('transaction_date', \Carbon::createFromTimestamp(strtotime($purchase->transaction_date))->format(session('business.date_format') . ' ' . 'H:i'), ['class' => 'form-control', 'readonly', 'required']); ?>

                </div>
              </div>
            </div>
            
            <div class="col-sm-3 <?php if(!empty($default_purchase_status)): ?> hide <?php endif; ?>">
              <div class="form-group">
                <?php echo Form::label('status', __('purchase.purchase_status') . ':*'); ?>

                <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.order_status') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
                <?php echo Form::select('status', $orderStatuses, $purchase->status, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select') , 'required']); ?>

              </div>
            </div>
            <div class="col-sm-3">
              <div class="form-group">
                <?php echo Form::label('location_id', __('purchase.business_location').':*'); ?>

                <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.purchase_location') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
                <?php echo Form::select('location_id', $business_locations, $purchase->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'disabled']); ?>

              </div>
            </div>

            <!-- Currency Exchange Rate -->
            <div class="col-sm-3 <?php if(!$currency_details->purchase_in_diff_currency): ?> hide <?php endif; ?>">
              <div class="form-group">
                <?php echo Form::label('exchange_rate', __('purchase.p_exchange_rate') . ':*'); ?>

                <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.currency_exchange_factor') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
                <div class="input-group">
                  <span class="input-group-addon">
                    <i class="fa fa-info"></i>
                  </span>
                  <?php echo Form::number('exchange_rate', $purchase->exchange_rate, ['class' => 'form-control', 'required', 'step' => 0.001]); ?>

                </div>
                <span class="help-block text-danger">
                  <?php echo app('translator')->get('purchase.diff_purchase_currency_help', ['currency' => $currency_details->name]); ?>
                </span>
              </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                  <div class="multi-input">
                    <?php echo Form::label('pay_term_number', __('contact.pay_term') . ':'); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('tooltip.pay_term') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
                    <br/>
                    <?php echo Form::number('pay_term_number', $purchase->pay_term_number, ['class' => 'form-control width-40 pull-left', 'placeholder' => __('contact.pay_term')]); ?>


                    <?php echo Form::select('pay_term_type', 
                      ['months' => __('lang_v1.months'), 
                        'days' => __('lang_v1.days')], 
                        $purchase->pay_term_type, 
                      ['class' => 'form-control width-60 pull-left','placeholder' => __('messages.please_select'), 'id' => 'pay_term_type']); ?>

                  </div>
              </div>
          </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <?php echo Form::label('document', __('purchase.attach_document') . ':'); ?>

                    <?php echo Form::file('document', ['id' => 'upload_document', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); ?>

                    <p class="help-block"><?php echo app('translator')->get('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]); ?>
                    <?php if ($__env->exists('components.document_help_text')) echo $__env->make('components.document_help_text', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?></p>
                </div>
            </div>
        </div>
        <div class="row">
          <?php
            $custom_field_1_label = !empty($custom_labels['purchase']['custom_field_1']) ? $custom_labels['purchase']['custom_field_1'] : '';

            $is_custom_field_1_required = !empty($custom_labels['purchase']['is_custom_field_1_required']) && $custom_labels['purchase']['is_custom_field_1_required'] == 1 ? true : false;

            $custom_field_2_label = !empty($custom_labels['purchase']['custom_field_2']) ? $custom_labels['purchase']['custom_field_2'] : '';

            $is_custom_field_2_required = !empty($custom_labels['purchase']['is_custom_field_2_required']) && $custom_labels['purchase']['is_custom_field_2_required'] == 1 ? true : false;

            $custom_field_3_label = !empty($custom_labels['purchase']['custom_field_3']) ? $custom_labels['purchase']['custom_field_3'] : '';

            $is_custom_field_3_required = !empty($custom_labels['purchase']['is_custom_field_3_required']) && $custom_labels['purchase']['is_custom_field_3_required'] == 1 ? true : false;

            $custom_field_4_label = !empty($custom_labels['purchase']['custom_field_4']) ? $custom_labels['purchase']['custom_field_4'] : '';

            $is_custom_field_4_required = !empty($custom_labels['purchase']['is_custom_field_4_required']) && $custom_labels['purchase']['is_custom_field_4_required'] == 1 ? true : false;
        ?>
        <?php if(!empty($custom_field_1_label)): ?>
          <?php
            $label_1 = $custom_field_1_label . ':';
            if($is_custom_field_1_required) {
              $label_1 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('custom_field_1', $label_1 ); ?>

                    <?php echo Form::text('custom_field_1', $purchase->custom_field_1, ['class' => 'form-control','placeholder' => $custom_field_1_label, 'required' => $is_custom_field_1_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($custom_field_2_label)): ?>
          <?php
            $label_2 = $custom_field_2_label . ':';
            if($is_custom_field_2_required) {
              $label_2 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('custom_field_2', $label_2 ); ?>

                    <?php echo Form::text('custom_field_2', $purchase->custom_field_2, ['class' => 'form-control','placeholder' => $custom_field_2_label, 'required' => $is_custom_field_2_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($custom_field_3_label)): ?>
          <?php
            $label_3 = $custom_field_3_label . ':';
            if($is_custom_field_3_required) {
              $label_3 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('custom_field_3', $label_3 ); ?>

                    <?php echo Form::text('custom_field_3', $purchase->custom_field_3, ['class' => 'form-control','placeholder' => $custom_field_3_label, 'required' => $is_custom_field_3_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($custom_field_4_label)): ?>
          <?php
            $label_4 = $custom_field_4_label . ':';
            if($is_custom_field_4_required) {
              $label_4 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('custom_field_4', $label_4 ); ?>

                    <?php echo Form::text('custom_field_4', $purchase->custom_field_4, ['class' => 'form-control','placeholder' => $custom_field_4_label, 'required' => $is_custom_field_4_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        </div>
        <?php if(!empty($common_settings['enable_purchase_order'])): ?>
        <div class="row">
          <div class="col-sm-3">
            <div class="form-group">
              <?php echo Form::label('purchase_order_ids', __('lang_v1.purchase_order').':'); ?>

              <?php echo Form::select('purchase_order_ids[]', $purchase_orders, $purchase->purchase_order_ids, ['class' => 'form-control select2', 'multiple', 'id' => 'purchase_order_ids']); ?>

            </div>
          </div>
        </div>
        <?php endif; ?>
    <?php echo $__env->renderComponent(); ?>

    <?php $__env->startComponent('components.widget', ['class' => 'box-primary']); ?>
        <div class="row">
            <div class="col-sm-2 text-center">
              <button type="button" class="btn btn-primary btn-flat" data-toggle="modal" data-target="#import_purchase_products_modal"><?php echo app('translator')->get('product.import_products'); ?></button>
            </div>
            <div class="col-sm-8">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon">
                    <i class="fa fa-search"></i>
                  </span>
                  <?php echo Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); ?>

                </div>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <button tabindex="-1" type="button" class="btn btn-link btn-modal"data-href="<?php echo e(action([\App\Http\Controllers\ProductController::class, 'quickAdd']), false); ?>" 
                      data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> <?php echo app('translator')->get( 'product.add_new_product' ); ?> </button>
              </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
              <?php echo $__env->make('purchase.partials.edit_purchase_entry_row', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

              <hr/>
              <div class="pull-right col-md-5">
                <table class="pull-right col-md-12">
                  <tr>
                    <th class="col-md-7 text-right"><?php echo app('translator')->get( 'lang_v1.total_items' ); ?>:</th>
                    <td class="col-md-5 text-left">
                      <span id="total_quantity" class="display_currency" data-currency_symbol="false"></span>
                    </td>
                  </tr>
                  <tr class="hide">
                    <th class="col-md-7 text-right"><?php echo app('translator')->get( 'purchase.total_before_tax' ); ?>:</th>
                    <td class="col-md-5 text-left">
                      <span id="total_st_before_tax" class="display_currency"></span>
                      <input type="hidden" id="st_before_tax_input" value=0>
                    </td>
                  </tr>
                  <tr>
                    <th class="col-md-7 text-right"><?php echo app('translator')->get( 'purchase.net_total_amount' ); ?>:</th>
                    <td class="col-md-5 text-left">
                      <span id="total_subtotal" class="display_currency"><?php echo e($purchase->total_before_tax/$purchase->exchange_rate, false); ?></span>
                      <!-- This is total before purchase tax-->
                      <input type="hidden" id="total_subtotal_input" value="<?php echo e($purchase->total_before_tax/$purchase->exchange_rate, false); ?>" name="total_before_tax">
                    </td>
                  </tr>
                </table>
              </div>

            </div>
        </div>
    <?php echo $__env->renderComponent(); ?>

    <?php $__env->startComponent('components.widget', ['class' => 'box-primary']); ?>
        <div class="row">
            <div class="col-sm-12">
                <table class="table">
                  <tr>
                    <td class="col-md-3">
                      <div class="form-group">
                        <?php echo Form::label('discount_type', __( 'purchase.discount_type' ) . ':'); ?>

                        <?php echo Form::select('discount_type', [ '' => __('lang_v1.none'), 'fixed' => __( 'lang_v1.fixed' ), 'percentage' => __( 'lang_v1.percentage' )], $purchase->discount_type, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); ?>

                      </div>
                    </td>
                    <td class="col-md-3">
                      <div class="form-group">
                      <?php echo Form::label('discount_amount', __( 'purchase.discount_amount' ) . ':'); ?>

                      <?php echo Form::text('discount_amount', 

                      ($purchase->discount_type == 'fixed' ? 
                        number_format($purchase->discount_amount/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)
                      :
                        number_format($purchase->discount_amount, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)
                      )
                      , ['class' => 'form-control input_number']); ?>

                      </div>
                    </td>
                    <td class="col-md-3">
                      &nbsp;
                    </td>
                    <td class="col-md-3">
                      <b>Discount:</b>(-) 
                      <span id="discount_calculated_amount" class="display_currency">0</span>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <div class="form-group">
                      <?php echo Form::label('tax_id', __( 'purchase.purchase_tax' ) . ':'); ?>

                      <select name="tax_id" id="tax_id" class="form-control select2" placeholder="'Please Select'">
                        <option value="" data-tax_amount="0" selected><?php echo app('translator')->get('lang_v1.none'); ?></option>
                        <?php $__currentLoopData = $taxes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tax): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <option value="<?php echo e($tax->id, false); ?>" <?php if($purchase->tax_id == $tax->id): ?> <?php echo e('selected', false); ?> <?php endif; ?> data-tax_amount="<?php echo e($tax->amount, false); ?>"
                          >
                            <?php echo e($tax->name, false); ?>

                          </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </select>
                      <?php echo Form::hidden('tax_amount', $purchase->tax_amount, ['id' => 'tax_amount']); ?>

                      </div>
                    </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>
                      <b><?php echo app('translator')->get( 'purchase.purchase_tax' ); ?>:</b>(+) 
                      <span id="tax_calculated_amount" class="display_currency">0</span>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="4">
                      <div class="form-group">
                        <?php echo Form::label('additional_notes',__('purchase.additional_notes')); ?>

                        <?php echo Form::textarea('additional_notes', $purchase->additional_notes, ['class' => 'form-control', 'rows' => 3]); ?>

                      </div>
                    </td>
                  </tr>

                </table>
            </div>
        </div>
    <?php echo $__env->renderComponent(); ?>
    <?php $__env->startComponent('components.widget', ['class' => 'box-primary']); ?>
    <div class="row">
      <div class="col-md-4">
        <div class="form-group">
        <?php echo Form::label('shipping_details', __( 'purchase.shipping_details' ) . ':'); ?>

        <?php echo Form::text('shipping_details', $purchase->shipping_details, ['class' => 'form-control']); ?>

        </div>
      </div>
      <div class="col-md-4 col-md-offset-4">
        <div class="form-group">
          <?php echo Form::label('shipping_charges','(+) ' . __( 'purchase.additional_shipping_charges') . ':'); ?>

          <?php echo Form::text('shipping_charges', number_format($purchase->shipping_charges/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number']); ?>

          </div>
      </div>
    </div>
    <div class="row">
          <?php
            $shipping_custom_label_1 = !empty($custom_labels['purchase_shipping']['custom_field_1']) ? $custom_labels['purchase_shipping']['custom_field_1'] : '';

            $is_shipping_custom_field_1_required = !empty($custom_labels['purchase_shipping']['is_custom_field_1_required']) && $custom_labels['purchase_shipping']['is_custom_field_1_required'] == 1 ? true : false;

            $shipping_custom_label_2 = !empty($custom_labels['purchase_shipping']['custom_field_2']) ? $custom_labels['purchase_shipping']['custom_field_2'] : '';

            $is_shipping_custom_field_2_required = !empty($custom_labels['purchase_shipping']['is_custom_field_2_required']) && $custom_labels['purchase_shipping']['is_custom_field_2_required'] == 1 ? true : false;

            $shipping_custom_label_3 = !empty($custom_labels['purchase_shipping']['custom_field_3']) ? $custom_labels['purchase_shipping']['custom_field_3'] : '';
            
            $is_shipping_custom_field_3_required = !empty($custom_labels['purchase_shipping']['is_custom_field_3_required']) && $custom_labels['purchase_shipping']['is_custom_field_3_required'] == 1 ? true : false;

            $shipping_custom_label_4 = !empty($custom_labels['purchase_shipping']['custom_field_4']) ? $custom_labels['purchase_shipping']['custom_field_4'] : '';
            
            $is_shipping_custom_field_4_required = !empty($custom_labels['purchase_shipping']['is_custom_field_4_required']) && $custom_labels['purchase_shipping']['is_custom_field_4_required'] == 1 ? true : false;

            $shipping_custom_label_5 = !empty($custom_labels['purchase_shipping']['custom_field_5']) ? $custom_labels['purchase_shipping']['custom_field_5'] : '';
            
            $is_shipping_custom_field_5_required = !empty($custom_labels['purchase_shipping']['is_custom_field_5_required']) && $custom_labels['purchase_shipping']['is_custom_field_5_required'] == 1 ? true : false;
        ?>

        <?php if(!empty($shipping_custom_label_1)): ?>
          <?php
            $label_1 = $shipping_custom_label_1 . ':';
            if($is_shipping_custom_field_1_required) {
              $label_1 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('shipping_custom_field_1', $label_1 ); ?>

                    <?php echo Form::text('shipping_custom_field_1', $purchase->shipping_custom_field_1 ?? null, ['class' => 'form-control','placeholder' => $shipping_custom_label_1, 'required' => $is_shipping_custom_field_1_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($shipping_custom_label_2)): ?>
          <?php
            $label_2 = $shipping_custom_label_2 . ':';
            if($is_shipping_custom_field_2_required) {
              $label_2 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('shipping_custom_field_2', $label_2 ); ?>

                    <?php echo Form::text('shipping_custom_field_2', $purchase->shipping_custom_field_2 ?? null, ['class' => 'form-control','placeholder' => $shipping_custom_label_2, 'required' => $is_shipping_custom_field_2_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($shipping_custom_label_3)): ?>
          <?php
            $label_3 = $shipping_custom_label_3 . ':';
            if($is_shipping_custom_field_3_required) {
              $label_3 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('shipping_custom_field_3', $label_3 ); ?>

                    <?php echo Form::text('shipping_custom_field_3', $purchase->shipping_custom_field_3 ?? null, ['class' => 'form-control','placeholder' => $shipping_custom_label_3, 'required' => $is_shipping_custom_field_3_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($shipping_custom_label_4)): ?>
          <?php
            $label_4 = $shipping_custom_label_4 . ':';
            if($is_shipping_custom_field_4_required) {
              $label_4 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('shipping_custom_field_4', $label_4 ); ?>

                    <?php echo Form::text('shipping_custom_field_4', $purchase->shipping_custom_field_4 ?? null, ['class' => 'form-control','placeholder' => $shipping_custom_label_4, 'required' => $is_shipping_custom_field_4_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        <?php if(!empty($shipping_custom_label_5)): ?>
          <?php
            $label_5 = $shipping_custom_label_5 . ':';
            if($is_shipping_custom_field_5_required) {
              $label_5 .= '*';
            }
          ?>

          <div class="col-md-4">
                <div class="form-group">
                    <?php echo Form::label('shipping_custom_field_5', $label_5 ); ?>

                    <?php echo Form::text('shipping_custom_field_5', $purchase->shipping_custom_field_5 ?? null, ['class' => 'form-control','placeholder' => $shipping_custom_label_5, 'required' => $is_shipping_custom_field_5_required]); ?>

                </div>
            </div>
        <?php endif; ?>
        </div>

    <div class="row">
      <div class="col-md-12 text-center">
        <button type="button" class="btn btn-primary btn-sm" id="toggle_additional_expense"> <i class="fas fa-plus"></i> <?php echo app('translator')->get('lang_v1.add_additional_expenses'); ?> <i class="fas fa-chevron-down"></i></button>
      </div>
      <div class="col-md-8 col-md-offset-4" id="additional_expenses_div">
        <table class="table table-condensed">
          <thead>
            <tr>
              <th><?php echo app('translator')->get('lang_v1.additional_expense_name'); ?></th>
              <th><?php echo app('translator')->get('sale.amount'); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <?php echo Form::text('additional_expense_key_1', $purchase->additional_expense_key_1, ['class' => 'form-control', 'id' => 'additional_expense_key_1']); ?>

              </td>
              <td>
                <?php echo Form::text('additional_expense_value_1', number_format($purchase->additional_expense_value_1/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_1']); ?>

              </td>
            </tr>
            <tr>
              <td>
                <?php echo Form::text('additional_expense_key_2', $purchase->additional_expense_key_2, ['class' => 'form-control', 'id' => 'additional_expense_key_2']); ?>

              </td>
              <td>
                <?php echo Form::text('additional_expense_value_2', number_format($purchase->additional_expense_value_2/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_2']); ?>

              </td>
            </tr>
            <tr>
              <td>
                <?php echo Form::text('additional_expense_key_3', $purchase->additional_expense_key_3, ['class' => 'form-control', 'id' => 'additional_expense_key_3']); ?>

              </td>
              <td>
                <?php echo Form::text('additional_expense_value_3', number_format($purchase->additional_expense_value_3/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_3']); ?>

              </td>
            </tr>
            <tr>
              <td>
                <?php echo Form::text('additional_expense_key_4', $purchase->additional_expense_key_4, ['class' => 'form-control', 'id' => 'additional_expense_key_4']); ?>

              </td>
              <td>
                <?php echo Form::text('additional_expense_value_4', number_format($purchase->additional_expense_value_4/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input_number', 'id' => 'additional_expense_value_4']); ?>

              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="row">
    <div class="col-md-12 text-right">
      <?php echo Form::hidden('final_total', $purchase->final_total , ['id' => 'grand_total_hidden']); ?>

      <b><?php echo app('translator')->get('purchase.purchase_total'); ?>: </b><span id="grand_total" class="display_currency" data-currency_symbol='true'><?php echo e($purchase->final_total, false); ?></span>
    </div>
    </div>
    <?php echo $__env->renderComponent(); ?>
  
    <div class="row">
        <div class="col-sm-12 text-center">
          <button type="button" id="submit_purchase_form" class="btn btn-primary btn-big"><?php echo app('translator')->get('messages.update'); ?></button>
        </div>
    </div>
<?php echo Form::close(); ?>

</section>
<!-- /.content -->
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
  <?php echo $__env->make('contact.create', ['quick_add' => true], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>
<?php echo $__env->make('purchase.partials.import_purchase_products_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('javascript'); ?>
  <script src="<?php echo e(asset('js/purchase.js?v=' . $asset_v), false); ?>"></script>
  <script src="<?php echo e(asset('js/product.js?v=' . $asset_v), false); ?>"></script>
  <script type="text/javascript">
    $(document).ready( function(){
      update_table_total();
      update_grand_total();
      __page_leave_confirmation('#add_purchase_form');
    });
  </script>
  <?php echo $__env->make('purchase.partials.keyboard_shortcuts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/purchase/edit.blade.php ENDPATH**/ ?>