<!-- app css -->
<?php if(!empty($for_pdf)): ?>
	<link rel="stylesheet" href="<?php echo e(asset('css/app.css?v='.$asset_v), false); ?>">
<?php endif; ?>
<div class="col-md-12 col-sm-12 <?php if(!empty($for_pdf)): ?> width-100 align-right <?php endif; ?>">
        <p class="text-right align-right"><strong><?php echo e($contact->business->name, false); ?></strong>
        	<br>
        	<?php if(!empty($location)): ?>
        		<?php echo $location->location_address; ?>

        	<?php else: ?>
        		<?php echo $contact->business->business_address; ?>

        	<?php endif; ?>
        </p>
</div>
<div class="col-md-6 col-sm-6 col-xs-6 <?php if(!empty($for_pdf)): ?> width-50 f-left <?php endif; ?>">
	<p class="blue-heading p-4 width-50"><?php echo app('translator')->get('lang_v1.to'); ?>:</p>
	<p><strong><?php echo e($contact->name, false); ?></strong><br> <?php echo $contact->contact_address; ?> <?php if(!empty($contact->email)): ?> <br><?php echo app('translator')->get('business.email'); ?>: <?php echo e($contact->email, false); ?> <?php endif; ?>
	<br><?php echo app('translator')->get('contact.mobile'); ?>: <?php echo e($contact->mobile, false); ?>

	<?php if(!empty($contact->tax_number)): ?> <br><?php echo app('translator')->get('contact.tax_no'); ?>: <?php echo e($contact->tax_number, false); ?> <?php endif; ?>
</p>
</div>
<div class="col-md-6 col-sm-6 col-xs-6 text-right align-right <?php if(!empty($for_pdf)): ?> width-50 f-left <?php endif; ?>">
	<h3 class="mb-0 blue-heading p-4"><?php echo app('translator')->get('lang_v1.account_summary'); ?></h3>
	<small><?php echo e($ledger_details['start_date'], false); ?> <?php echo app('translator')->get('lang_v1.to'); ?> <?php echo e($ledger_details['end_date'], false); ?></small>
	<hr>
	<table class="table table-condensed text-left align-left no-border <?php if(!empty($for_pdf)): ?> table-pdf <?php endif; ?>">
		<tr>
			<td><?php echo app('translator')->get('lang_v1.opening_balance'); ?></td>
			<td class="align-right"><?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $ledger_details['beginning_balance'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></td>
		</tr>
	<?php if( $contact->type == 'supplier' || $contact->type == 'both'): ?>
		<tr>
			<td><?php echo app('translator')->get('report.total_purchase'); ?></td>
			<td class="align-right"><?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $ledger_details['total_purchase'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></td>
		</tr>
	<?php endif; ?>
	<?php if( $contact->type == 'customer' || $contact->type == 'both'): ?>
		<tr>
			<td><?php echo app('translator')->get('lang_v1.total_invoice'); ?></td>
			<td class="align-right"><?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $ledger_details['total_invoice'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></td>
		</tr>
	<?php endif; ?>
	<tr>
		<td><?php echo app('translator')->get('sale.total_paid'); ?></td>
		<td class="align-right"><?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $ledger_details['total_paid'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></td>
	</tr>
	<tr>
		<td><?php echo app('translator')->get('lang_v1.advance_balance'); ?></td>
		<td class="align-right"><?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $contact->balance - $ledger_details['total_reverse_payment'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></td>
	</tr>
	<?php if($ledger_details['ledger_discount'] > 0): ?>
		<tr>
			<td><?php echo app('translator')->get('lang_v1.ledger_discount'); ?></td>
			<td class="align-right"><?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $ledger_details['ledger_discount'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></td>
		</tr>
	<?php endif; ?>
	<tr>
		<td><strong><?php echo app('translator')->get('lang_v1.balance_due'); ?></strong></td>
		<td class="align-right"><?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $ledger_details['balance_due'] - $ledger_details['ledger_discount'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?></td>
	</tr>
	</table>
</div>
<div class="col-md-12 col-sm-12 <?php if(!empty($for_pdf)): ?> width-100 <?php endif; ?>">
	<p class="text-center" style="text-align: center;"><strong><?php echo app('translator')->get('lang_v1.ledger_table_heading', ['start_date' => $ledger_details['start_date'], 'end_date' => $ledger_details['end_date']]); ?></strong></p>
	<div class="table-responsive">
	<table class="table table-striped <?php if(!empty($for_pdf)): ?> table-pdf td-border <?php endif; ?>" id="ledger_table">
		<thead>
			<tr class="row-border blue-heading">
				<th width="18%" class="text-center"><?php echo app('translator')->get('lang_v1.date'); ?></th>
				<th width="9%" class="text-center"><?php echo app('translator')->get('purchase.ref_no'); ?></th>
				<th width="8%" class="text-center"><?php echo app('translator')->get('lang_v1.type'); ?></th>
				<th width="10%" class="text-center"><?php echo app('translator')->get('sale.location'); ?></th>
				<th width="5%" class="text-center"><?php echo app('translator')->get('sale.payment_status'); ?></th>
				
				<th width="10%" class="text-center"><?php echo app('translator')->get('account.debit'); ?></th>
				<th width="10%" class="text-center"><?php echo app('translator')->get('account.credit'); ?></th>
				<th width="10%" class="text-center"><?php echo app('translator')->get('lang_v1.balance'); ?></th>
				<th width="5%" class="text-center"><?php echo app('translator')->get('lang_v1.payment_method'); ?></th>
				<th width="15%" class="text-center"><?php echo app('translator')->get('report.others'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php $__currentLoopData = $ledger_details['ledger']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
				<tr <?php if(!empty($for_pdf) && $loop->iteration % 2 == 0): ?> class="odd" <?php endif; ?>>
					<td class="row-border"><?php echo e(\Carbon::createFromTimestamp(strtotime($data['date']))->format(session('business.date_format') . ' ' . 'H:i'), false); ?></td>
					<td><?php echo e($data['ref_no'], false); ?></td>
					<td><?php echo e($data['type'], false); ?></td>
					<td><?php echo e($data['location'], false); ?></td>
					<td><?php echo e($data['payment_status'], false); ?></td>
					
					<td class="ws-nowrap align-right"><?php if($data['debit'] != ''): ?> <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $data['debit'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?> <?php endif; ?></td>
					<td class="ws-nowrap align-right"><?php if($data['credit'] != ''): ?> <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $data['credit'], session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?> <?php endif; ?></td>
					<td class="ws-nowrap align-right"><?php echo e($data['balance'], false); ?></td>
					<td><?php echo e($data['payment_method'], false); ?></td>
					<td>
						<?php echo $data['others']; ?>


						<?php if(!empty($is_admin) && !empty($data['transaction_id']) && $data['transaction_type'] == 'ledger_discount'): ?>
							<br>
							<button type="button" class="btn btn-xs btn-danger delete_ledger_discount" data-href="<?php echo e(action([\App\Http\Controllers\LedgerDiscountController::class, 'destroy'], ['ledger_discount' => $data['transaction_id']]), false); ?>"><i class="fas fa-trash"></i></button>
							<button type="button" class="btn btn-xs btn-primary btn-modal" data-href="<?php echo e(action([\App\Http\Controllers\LedgerDiscountController::class, 'edit'], ['ledger_discount' => $data['transaction_id']]), false); ?>" data-container="#edit_ledger_discount_modal"><i class="fas fa-edit"></i></button>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
		</tbody>
	</table>
	</div>
</div><?php /**PATH /var/www/html/resources/views/contact/ledger.blade.php ENDPATH**/ ?>