<div class="payment_details_div <?php if( $payment_line['method'] !== 'cheque' ): ?> <?php echo e('hide', false); ?> <?php endif; ?>" data-type="cheque" >
	<div class="col-md-12">
		<div class="form-group">
			<?php echo Form::label("cheque_number_$row_index",__('lang_v1.cheque_no')); ?>

			<?php echo Form::text("payment[$row_index][cheque_number]", $payment_line['cheque_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.cheque_no'), 'id' => "cheque_number_$row_index"]); ?>

		</div>
	</div>
</div>
<div class="payment_details_div <?php if( $payment_line['method'] !== 'bank_transfer' ): ?> <?php echo e('hide', false); ?> <?php endif; ?>" data-type="bank_transfer" >
	<div class="col-md-12">
		<div class="form-group">
			<?php echo Form::label("bank_account_number_$row_index",__('lang_v1.bank_account_number')); ?>

			<?php echo Form::text( "payment[$row_index][bank_account_number]", $payment_line['bank_account_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number'), 'id' => "bank_account_number_$row_index"]); ?>

		</div>
	</div>
</div>

<?php for($i = 1; $i < 8; $i++): ?>
<div class="payment_details_div <?php if( $payment_line['method'] !== 'custom_pay_' . $i ): ?> <?php echo e('hide', false); ?> <?php endif; ?>" data-type="custom_pay_<?php echo e($i, false); ?>" >
	<div class="col-md-12">
		<div class="form-group">
			<?php echo Form::label("transaction_no_{$i}_{$row_index}", __('lang_v1.transaction_no')); ?>

			<?php echo Form::text("payment[$row_index][transaction_no_{$i}]", $payment_line['transaction_no'], ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_{$i}_{$row_index}"]); ?>

		</div>
	</div>
</div>
<?php endfor; ?><?php /**PATH /var/www/html/resources/views/sale_pos/partials/payment_type_details.blade.php ENDPATH**/ ?>