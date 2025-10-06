<?php $__env->startSection('title', __('sale.pos_sale')); ?>

<?php $__env->startSection('content'); ?>
<section class="content no-print">
	<input type="hidden" id="amount_rounding_method" value="<?php echo e($pos_settings['amount_rounding_method'] ?? '', false); ?>">
	<?php if(!empty($pos_settings['allow_overselling'])): ?>
		<input type="hidden" id="is_overselling_allowed">
	<?php endif; ?>
	<?php if(session('business.enable_rp') == 1): ?>
        <input type="hidden" id="reward_point_enabled">
    <?php endif; ?>
    <?php
		$is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
		$is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
	?>
	<?php echo Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'store']), 'method' => 'post', 'id' => 'add_pos_sell_form' ]); ?>

	<div class="row mb-12">
		<div class="col-md-12">
			<div class="row">
				<div class="<?php if(empty($pos_settings['hide_product_suggestion'])): ?> col-md-7 <?php else: ?> col-md-10 col-md-offset-1 <?php endif; ?> no-padding pr-12">
					<div class="box box-solid mb-12 <?php if(!isMobile()): ?> mb-40 <?php endif; ?>">
						<div class="box-body pb-0">
							<?php echo Form::hidden('location_id', $default_location->id ?? null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '']); ?>

							<!-- sub_type -->
							<?php echo Form::hidden('sub_type', isset($sub_type) ? $sub_type : null); ?>

							<input type="hidden" id="item_addition_method" value="<?php echo e($business_details->item_addition_method, false); ?>">
								<?php echo $__env->make('sale_pos.partials.pos_form', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

								<?php echo $__env->make('sale_pos.partials.pos_form_totals', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

								<?php echo $__env->make('sale_pos.partials.payment_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

								<?php if(empty($pos_settings['disable_suspend'])): ?>
									<?php echo $__env->make('sale_pos.partials.suspend_note_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
								<?php endif; ?>

								<?php if(empty($pos_settings['disable_recurring_invoice'])): ?>
									<?php echo $__env->make('sale_pos.partials.recurring_invoice_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php if(empty($pos_settings['hide_product_suggestion']) && !isMobile()): ?>
				<div class="col-md-5 no-padding">
					<?php echo $__env->make('sale_pos.partials.pos_sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php echo $__env->make('sale_pos.partials.pos_form_actions', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
	<?php echo Form::close(); ?>

</section>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	<?php echo $__env->make('contact.create', ['quick_add' => true], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>
<?php if(empty($pos_settings['hide_product_suggestion']) && isMobile()): ?>
	<?php echo $__env->make('sale_pos.partials.mobile_product_suggestions', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php endif; ?>
<!-- /.content -->
<div class="modal fade register_details_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade cash_adjustment_modal" tabindex="-1" role="dialog" 
	aria-labelledby="gridSystemModalLabel">
</div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

<?php echo $__env->make('sale_pos.partials.configure_search_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php echo $__env->make('sale_pos.partials.recent_transactions_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php echo $__env->make('sale_pos.partials.weighing_scale_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php $__env->stopSection(); ?>
<?php $__env->startSection('css'); ?>
	<!-- include module css -->
    <?php if(!empty($pos_module_data)): ?>
        <?php $__currentLoopData = $pos_module_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!empty($value['module_css_path'])): ?>
                <?php if ($__env->exists($value['module_css_path'])) echo $__env->make($value['module_css_path'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('javascript'); ?>
	<script src="<?php echo e(asset('js/pos.js?v=' . $asset_v), false); ?>"></script>
	<script src="<?php echo e(asset('js/modal-fix.js?v=' . $asset_v), false); ?>"></script>
	<script src="<?php echo e(asset('js/printer.js?v=' . $asset_v), false); ?>"></script>
	<script src="<?php echo e(asset('js/product.js?v=' . $asset_v), false); ?>"></script>
	<script src="<?php echo e(asset('js/opening_stock.js?v=' . $asset_v), false); ?>"></script>
	<script>
		window.plastic_bag_api_url = '/plastic-bag/get-plastic-bag-types-for-pos';
	</script>
	<script src="<?php echo e(asset('js/pos_plastic_bags.js?v=' . $asset_v), false); ?>"></script>
	<?php echo $__env->make('sale_pos.partials.keyboard_shortcuts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

	<!-- Call restaurant module if defined -->
    <?php if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules)): ?>
    	<script src="<?php echo e(asset('js/restaurant.js?v=' . $asset_v), false); ?>"></script>
    <?php endif; ?>
    <!-- include module js -->
    <?php if(!empty($pos_module_data)): ?>
	    <?php $__currentLoopData = $pos_module_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!empty($value['module_js_path'])): ?>
                <?php if ($__env->exists($value['module_js_path'], ['view_data' => $value['view_data']])) echo $__env->make($value['module_js_path'], ['view_data' => $value['view_data']], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
	    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
	<?php endif; ?>

	
<script>
$(document).ready(function() {
    // Function to format a number to 2 decimal places
    function formatNumber(value) {
        return parseFloat(value).toFixed(2);
    }

    // Event listener for both main page and modal unit price inputs
    $('body').on('input', '.main_page_unit_price, .modal_unit_price', function() {
        var inputElement = $(this);
        
        // Get the value from the input that changed
        var changedValue = inputElement.val();
        
        // Find the corresponding main page and modal inputs
        var row = inputElement.closest('tr.product_row');
        var mainPageUnitPriceInput = row.find('input.main_page_unit_price');
        var modalUnitPriceInput = row.find('input.modal_unit_price');
        
        // Update the corresponding inputs
        mainPageUnitPriceInput.val(changedValue);
        modalUnitPriceInput.val(changedValue);
    });

    // Event listener for when the input field loses focus
    $('body').on('focusout', '.main_page_unit_price, .modal_unit_price', function() {
        var inputElement = $(this);
        
        // Get the value from the input that changed
        var changedValue = inputElement.val();
        
        // Format the input value with two decimal places (0.00)
        inputElement.val(formatNumber(changedValue));
    });

    // Event listener for opening the modal
    $('body').on('show.bs.modal', '.row_edit_product_price_model', function () {
        // Find the modal unit price input and update it with the main page value
        var modal = $(this);
        var mainPageUnitPriceInput = modal.closest('tr.product_row').find('input.main_page_unit_price');
        var modalUnitPriceInput = modal.find('input.modal_unit_price');
        var mainPageValue = formatNumber(mainPageUnitPriceInput.val());
        modalUnitPriceInput.val(mainPageValue);
    });

    // Event listener for saving the modal
    $('body').on('click', '.modal-save-button', function() {
        // Find the modal unit price input and format it
        var modal = $(this).closest('.modal');
        var modalUnitPriceInput = modal.find('input.modal_unit_price');
        modalUnitPriceInput.val(formatNumber(modalUnitPriceInput.val()));
    });
});
</script>

<?php if(Session::get('business.name') == 'Agro Mart'): ?>
    <?php echo $__env->make('sale_pos.partials.bank_transfers_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/sale_pos/create.blade.php ENDPATH**/ ?>