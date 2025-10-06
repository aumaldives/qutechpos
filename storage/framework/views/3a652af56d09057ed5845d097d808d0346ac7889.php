<?php
	$subtype = '';
?>
<?php if(!empty($transaction_sub_type)): ?>
	<?php
		$subtype = '?sub_type='.$transaction_sub_type;
	?>
<?php endif; ?>
<style>
.share-dropdown {
    position: relative;
    display: inline-block;
}

.share-button , .copy-url-button {
    background-color: white;
    color: #fff;
	border: none;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1;
}

.dropdown-content a {
    display: block;
    padding: 12px 16px;
    text-decoration: none;
    color: #333;
}

.dropdown-content a:hover {
    background-color: #ddd;
}

	</style>
<?php if(!empty($transactions)): ?>
	<table class="table table-slim no-border">
		<?php $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
			<tr class="cursor-pointer" 
	    		title="Customer: <?php echo e($transaction->contact?->name, false); ?> 
		    		<?php if(!empty($transaction->contact->mobile) && $transaction->contact->is_default == 0): ?>
		    			<br/>Mobile: <?php echo e($transaction->contact->mobile, false); ?>

		    		<?php endif; ?>
	    		" >
				<td>
					<?php echo e($loop->iteration, false); ?>.
				</td>
				<td> 
					<?php echo e($transaction->invoice_no, false); ?> (<?php echo e($transaction->contact?->name, false); ?>)
					<?php if(!empty($transaction->table)): ?>
						- <?php echo e($transaction->table->name, false); ?>

					<?php endif; ?>
				</td>
				<td class="display_currency">
					<?php echo e($transaction->final_total, false); ?>

				</td>
				<td>
					<?php if(auth()->user()->can('sell.update') || auth()->user()->can('direct_sell.update')): ?>
					<a href="<?php echo e(action([\App\Http\Controllers\SellPosController::class, 'edit'], [$transaction->id]).$subtype, false); ?>">
	    				<i class="fas fa-pen text-muted" aria-hidden="true" title="<?php echo e(__('lang_v1.click_to_edit'), false); ?>"></i>
	    			</a>
	    			<?php endif; ?>
	    			<?php if(auth()->user()->can('sell.delete') || auth()->user()->can('direct_sell.delete')): ?>
	    			<a href="<?php echo e(action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$transaction->id]), false); ?>" class="delete-sale" style="padding-left: 20px; padding-right: 20px"><i class="fa fa-trash text-danger" title="<?php echo e(__('lang_v1.click_to_delete'), false); ?>"></i></a>
	    			<?php endif; ?>

					<?php if(!auth()->user()->can('sell.update') && auth()->user()->can('edit_pos_payment')): ?>
						<a href="<?php echo e(route('edit-pos-payment', ['id' => $transaction->id]), false); ?>" 
						title="<?php echo app('translator')->get('lang_v1.add_edit_payment'); ?>">
						 <i class="fas fa-money-bill-alt text-muted"></i>
						</a>
					<?php endif; ?>

	    			<a href="<?php echo e(action([\App\Http\Controllers\SellPosController::class, 'printInvoice'], [$transaction->id]), false); ?>" class="print-invoice-link">
	    				<i class="fa fa-print text-muted" aria-hidden="true" title="<?php echo e(__('lang_v1.click_to_print'), false); ?>"></i>
	    			</a>

				
				
		<td>			
    <!-- "Copy URL" Button -->
    <button class="copy-url-button" data-transaction-id="<?php echo e($transaction->id, false); ?>">
        <i class="fa fa-copy text-muted" aria-hidden="true" title="Copy Invoice Url"></i>
    </button>
    
    <!-- Input field with a unique ID -->
	<input type="text" class="form-control invoice-url" id="invoice-url-<?php echo e($transaction->id, false); ?>" value="<?php echo e($transaction->invoiceUrl, false); ?>" style="position: absolute; left: -9999px;" />

    <!-- "Share" Button (a simple link for sharing) -->
	<div class="share-dropdown">
    <button class="share-button" data-transaction-id="<?php echo e($transaction->id, false); ?>">
        <i class="fa fa-share text-muted" aria-hidden="true" title="Share invoice URL"></i>
    </button>
    <div class="dropdown-content" id="shareDropdown-<?php echo e($transaction->id, false); ?>">
        <a href="whatsapp://send?text=Here's a link to your invoice: *<?php echo e($transaction->invoice_no, false); ?>* %0a<?php echo e($transaction->invoiceUrl, false); ?>" title="Share via WhatsApp">Share via WhatsApp</a>
        <a href="mailto:?subject=Here's your link to your invoice# <?php echo e($transaction->invoice_no, false); ?>&body=<?php echo e($transaction->invoiceUrl, false); ?>" title="Share via Email">Share via Email</a>
    </div>
</div>
</td>



			</tr>
		<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
	</table>
<?php else: ?>
	<p><?php echo app('translator')->get('sale.no_recent_transactions'); ?></p>
<?php endif; ?>

<script>
$(document).ready(function() {
    $('.invoice-url').each(function() {
        var transactionId = $(this).data('transaction-id');
        var invoiceUrl = $(this).val();
        
      
    });

    $('.copy-url-button').click(function() {
        var button = $(this);
        var transactionId = button.data('transaction-id');
        var inputField = $('#invoice-url-' + transactionId);
        
        inputField.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                var iconElement = button.find('i');
                iconElement.removeClass('fa-copy');
                iconElement.addClass('fa-clipboard-check');
                
                setTimeout(function() {
                    iconElement.removeClass('fa-clipboard-check');
                    iconElement.addClass('fa-copy');
                }, 1500); // Reset the icon after 1.5 second
            } else {
                console.error('Copying to clipboard failed');
            }
        } catch (err) {
            console.error('Error copying to clipboard: ', err);
        }
    });
	$('.share-button').click(function() {
        var button = $(this);
        var transactionId = button.data('transaction-id');
        var dropdown = $('#shareDropdown-' + transactionId);

        // Hide all other dropdowns
        $('.dropdown-content').not(dropdown).hide();

        if (dropdown.is(':visible')) {
            dropdown.hide();
        } else {
            dropdown.show();
        }
    });
})
</script>

<?php /**PATH /var/www/html/resources/views/sale_pos/partials/recent_transactions.blade.php ENDPATH**/ ?>