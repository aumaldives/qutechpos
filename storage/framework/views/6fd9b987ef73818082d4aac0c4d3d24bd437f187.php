<div class="row">
	<div class="col-sm-12 pos_product_div">
		<input type="hidden" name="sell_price_tax" id="sell_price_tax" value="<?php echo e($business_details->sell_price_tax, false); ?>">

		<!-- Keeps count of product rows -->
		<input type="hidden" id="product_row_count"
			value="0">
		<?php
			$hide_tax = '';
			if( session()->get('business.enable_inline_tax') == 0){
				$hide_tax = 'hide';
			}
		?>

		<?php
			$hide_tax_1 = '';
			if( session()->get('business.enable_exc_tax') == 0){
				$hide_tax_1 = 'hide';
			}
		?>
		<table class="table table-condensed table-bordered table-striped table-responsive" id="pos_table">
			<thead>
				<tr>
					<th class="tex-center <?php if(!empty($pos_settings['inline_service_staff'])): ?> col-md-3 <?php else: ?> col-md-4 <?php endif; ?>">
						<?php echo app('translator')->get('sale.product'); ?> <?php
                if(session('business.enable_tooltip')){
                    echo '<i class="fa fa-info-circle text-info hover-q no-print " aria-hidden="true" 
                    data-container="body" data-toggle="popover" data-placement="auto bottom" 
                    data-content="' . __('lang_v1.tooltip_sell_product_column') . '" data-html="true" data-trigger="hover"></i>';
                }
                ?>
					</th>
					<th class="text-center col-md-3">
						<?php echo app('translator')->get('sale.qty'); ?>
					</th>
					<?php if(!empty($pos_settings['inline_service_staff'])): ?>
						<th class="text-center col-md-2">
							<?php echo app('translator')->get('restaurant.service_staff'); ?>
						</th>
					<?php endif; ?>
					<th class="text-center col-md-2 <?php echo e($hide_tax_1, false); ?>">
						<?php echo app('translator')->get('lang_v1.price_exc_tax'); ?>
					</th>
					<th class="text-center col-md-2 <?php echo e($hide_tax, false); ?>">
						<?php echo app('translator')->get('sale.price_inc_tax'); ?>
					</th>

					<th class="text-center col-md-2">
						<?php echo app('translator')->get('sale.subtotal'); ?>
					</th>
					<th class="text-center"><i class="fad fa-times text-danger" aria-hidden="true"></i></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>
<?php /**PATH /var/www/html/resources/views/sale_pos/partials/pos_form_v2_table.blade.php ENDPATH**/ ?>