<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title" id="myModalLabel"><?php echo e($product->product_name, false); ?> - <?php echo e($product->sub_sku, false); ?></h4>
		</div>
		<div class="modal-body">
			<div class="row">
				<div class="form-group col-xs-12 <?php if(!auth()->user()->can('edit_product_price_from_sale_screen')): ?> hide <?php endif; ?>">
					<?php
						$pos_unit_price = !empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price;
					?>
					<label><?php echo app('translator')->get('sale.unit_price'); ?></label>
						<input type="text" name="products[<?php echo e($row_count, false); ?>][unit_price]" class="form-control pos_unit_price input_number mousetrap modal_unit_price" value="<?php echo e(number_format($pos_unit_price, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), false); ?>" <?php if(!empty($pos_settings['enable_msp'])): ?> data-rule-min-value="<?php echo e($pos_unit_price, false); ?>" data-msg-min-value="<?php echo e(__('lang_v1.minimum_selling_price_error_msg', ['price' => number_format($pos_unit_price, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator'])]), false); ?>" <?php endif; ?>>

				</div>
				<?php if(!auth()->user()->can('edit_product_price_from_sale_screen')): ?>
					<div class="form-group col-xs-12">
						<strong><?php echo app('translator')->get('sale.unit_price'); ?>:</strong> <?php echo e(number_format(!empty($product->unit_price_before_discount) ? $product->unit_price_before_discount : $product->default_sell_price, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), false); ?>

					</div>
				<?php endif; ?>
				<div class="form-group col-xs-12 col-sm-6 <?php if(!$edit_discount): ?> hide <?php endif; ?>">
					<label><?php echo app('translator')->get('sale.discount_type'); ?></label>
						<?php echo Form::select("products[$row_count][line_discount_type]", ['fixed' => __('lang_v1.fixed'), 'percentage' => __('lang_v1.percentage')], $discount_type , ['class' => 'form-control row_discount_type']); ?>

				</div>
				<div class="form-group col-xs-12 col-sm-6 <?php if(!$edit_discount): ?> hide <?php endif; ?>">
					<label><?php echo app('translator')->get('sale.discount_amount'); ?></label>
						<?php echo Form::text("products[$row_count][line_discount_amount]", number_format($discount_amount, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), ['class' => 'form-control input_number row_discount_amount']); ?>

				</div>
				<?php if(!empty($discount)): ?>
					<div class="form-group col-xs-12">
						<p class="help-block"><?php echo __('lang_v1.applied_discount_text', ['discount_name' => $discount->name, 'starts_at' => $discount->formated_starts_at, 'ends_at' => $discount->formated_ends_at]); ?></p>
					</div>
				<?php endif; ?>
				<div class="form-group col-xs-12 <?php echo e($hide_tax, false); ?>">
					<label><?php echo app('translator')->get('sale.tax'); ?></label>

					<?php echo Form::hidden("products[$row_count][item_tax]", number_format($item_tax, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), ['class' => 'item_tax']); ?>

		
					<?php echo Form::select("products[$row_count][tax_id]", $tax_dropdown['tax_rates'], $tax_id, ['placeholder' => 'Select', 'class' => 'form-control tax_id'], $tax_dropdown['attributes']); ?>

				</div>
				<?php if(!empty($warranties)): ?>
					<div class="form-group col-xs-12">
						<label><?php echo app('translator')->get('lang_v1.warranty'); ?></label>
						<?php echo Form::select("products[$row_count][warranty_id]", $warranties, $warranty_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control']); ?>

					</div>
				<?php endif; ?>
				
				<div class="form-group col-xs-12 col-sm-6">
					<label><?php echo app('translator')->get('sale.qty'); ?></label>
					<div class="input-group">
						<input type="text" name="products[<?php echo e($row_count, false); ?>][quantity]" 
							   class="form-control input_number pos_quantity mousetrap modal_quantity" 
							   value="<?php echo e(number_format(!empty($product->quantity_ordered) ? $product->quantity_ordered : 1, session('business.quantity_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), false); ?>" 
							   data-row-index="<?php echo e($row_count, false); ?>">
						<span class="input-group-addon">
							<?php echo e($product->unit_short_name, false); ?>

						</span>
					</div>
				</div>

				
				<?php if(!empty($product->enable_sr_no) && $product->enable_sr_no == 1): ?>
				<div class="form-group col-xs-12">
					<label><?php echo app('translator')->get('lang_v1.imei_numbers'); ?></label>
					<div class="imei_selection_container">
						<div class="row">
							<div class="col-sm-8">
								<input type="text" class="form-control imei_search_input" 
									   placeholder="<?php echo app('translator')->get('lang_v1.search_imei'); ?>" 
									   data-row-index="<?php echo e($row_count, false); ?>"
									   data-product-id="<?php echo e($product->product_id, false); ?>"
									   data-variation-id="<?php echo e($product->variation_id, false); ?>"
									   data-location-id="<?php echo e(session('user.business_location_id'), false); ?>">
							</div>
							<div class="col-sm-4">
								<button type="button" class="btn btn-primary btn-sm search_available_imeis" 
										data-row-index="<?php echo e($row_count, false); ?>">
									<i class="fa fa-search"></i> <?php echo app('translator')->get('lang_v1.search_available_imeis'); ?>
								</button>
							</div>
						</div>
						
						
						<div class="available_imeis_container" style="margin-top: 10px; max-height: 200px; overflow-y: auto;">
							<div class="no_imeis_message text-muted" style="padding: 10px; text-align: center;">
								<?php echo app('translator')->get('lang_v1.click_search_to_load_available_imeis'); ?>
							</div>
						</div>

						
						<div class="selected_imeis_container" style="margin-top: 15px;">
							<label><?php echo app('translator')->get('lang_v1.selected_imeis'); ?>:</label>
							<div class="selected_imeis_list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; min-height: 50px;">
								<div class="no_selection_message text-muted"><?php echo app('translator')->get('lang_v1.no_imeis_selected'); ?></div>
							</div>
							<input type="hidden" name="products[<?php echo e($row_count, false); ?>][selected_imeis]" class="selected_imeis_input" value="">
						</div>

						
						<div class="imei_selection_summary" style="margin-top: 10px;">
							<small class="text-info">
								<span class="selected_imei_count">0</span> <?php echo app('translator')->get('lang_v1.imeis_selected'); ?> / 
								<span class="required_imei_count"><?php echo e(!empty($product->quantity_ordered) ? $product->quantity_ordered : 1, false); ?></span> <?php echo app('translator')->get('lang_v1.required'); ?>
							</small>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<div class="form-group col-xs-12">
		      		<label><?php echo app('translator')->get('lang_v1.description'); ?></label>
		      		<textarea class="form-control" name="products[<?php echo e($row_count, false); ?>][sell_line_note]" rows="3"><?php echo e($sell_line_note, false); ?></textarea>
		      		<p class="help-block"><?php echo app('translator')->get('lang_v1.sell_line_description_help'); ?></p>
		      	</div>
			</div>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-primary update-modal-quantities" data-row-index="<?php echo e($row_count, false); ?>">
				<i class="fa fa-refresh"></i> <?php echo app('translator')->get('messages.update'); ?>
			</button>
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo app('translator')->get('messages.close'); ?></button>
		</div>
	</div>
</div><?php /**PATH /var/www/html/resources/views/sale_pos/partials/row_edit_product_price_modal.blade.php ENDPATH**/ ?>