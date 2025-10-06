<div class="modal fade" tabindex="-1" role="dialog" id="modal_cash_payment">
	<div class="modal-dialog modal-xl" role="document" style="width: 95%; max-width: 1200px;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo app('translator')->get('lang_v1.payment'); ?> - <?php echo app('translator')->get('sale.cash'); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-9">
						<div class="row">
							<div class="col-md-12">
								<div class="box box-solid payment_row bg-lightgray">
									<div class="box-body">
										<div class="row">
											<input type="hidden" class="payment_row_index" value="0">
											<input type="hidden" class="payment_types_dropdown" name="payment[0][method]" value="cash" disabled>

											<div class="col-md-6">
												<div class="form-group">
													<?php echo Form::label("cash_amount_0" ,__('sale.amount') . ':*'); ?>

													<div class="input-group">
														<span class="input-group-addon">
															<i class="fas fa-money-bill-alt"></i>
														</span>
														<?php echo Form::text("payment[0][amount]", 0, ['class' => 'form-control input_number', 'required', 'id' => "cash_amount_0", 'placeholder' => __('sale.amount'), 'autocomplete' => 'off', 'disabled' => true]); ?>

													</div>
												</div>
											</div>

											<?php
												$pos_settings = !empty(session()->get('business.pos_settings')) ? json_decode(session()->get('business.pos_settings'), true) : [];
												$enable_cash_denomination_for_payment_methods = !empty($pos_settings['enable_cash_denomination_for_payment_methods']) ? $pos_settings['enable_cash_denomination_for_payment_methods'] : [];
											?>

											<?php if(!empty($pos_settings['enable_cash_denomination_on']) && $pos_settings['enable_cash_denomination_on'] == 'all_screens' && !empty($show_denomination)): ?>
												<input type="hidden" class="enable_cash_denomination_for_payment_methods" value="<?php echo e(json_encode($enable_cash_denomination_for_payment_methods), false); ?>">
												<div class="clearfix"></div>
												<div class="col-md-12 cash_denomination_div">
													<hr>
													<strong><?php echo app('translator')->get( 'lang_v1.cash_denominations' ); ?></strong>
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
																<?php
																	$total = 0;
																?>
																<?php $__currentLoopData = explode(',', $pos_settings['cash_denominations']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dnm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
																<tr>
																	<td class="text-right"><?php echo e($dnm, false); ?></td>
																	<td class="text-center" >X</td>
																	<td><?php echo Form::number("payment[0][denominations][$dnm]", 0, ['class' => 'form-control cash_denomination input-sm', 'min' => 0, 'data-denomination' => $dnm, 'style' => 'width: 100px; margin:auto;', 'disabled' => true ]); ?></td>
																	<td class="text-center">=</td>
																	<td class="text-left">
																		<span class="denomination_subtotal"><?php echo e(number_format(0, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), false); ?></span>
																	</td>
																</tr>
																<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
															</tbody>
															<tfoot>
																<tr>
																	<th colspan="4" class="text-center"><?php echo app('translator')->get('sale.total'); ?></th>
																	<td>
																		<span class="denomination_total"><?php echo e(number_format(0, session('business.currency_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), false); ?></span>
																		<input type="hidden" class="denomination_total_amount" value="0">
																		<input type="hidden" class="is_strict" value="<?php echo e($pos_settings['cash_denomination_strict_check'] ?? '', false); ?>">
																	</td>
																</tr>
															</tfoot>
														</table>
														<p class="cash_denomination_error error hide"><?php echo app('translator')->get('lang_v1.cash_denomination_error'); ?></p>
													<?php else: ?>
														<p class="help-block"><?php echo app('translator')->get('lang_v1.denomination_add_help_text'); ?></p>
													<?php endif; ?>
												</div>
												<div class="clearfix"></div>
											<?php endif; ?>

											<div class="col-md-12">
												<div class="form-group">
													<?php echo Form::label('cash_note_0', __('lang_v1.payment_note') . ':'); ?>

													<?php echo Form::textarea('payment[0][note]', null, ['class' => 'form-control', 'rows' => 3, 'id' => 'cash_note_0', 'placeholder' => __('lang_v1.payment_note'), 'disabled' => true]); ?>

												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-3">
						<div class="box box-solid bg-orange">
							<div class="box-body">
								<div class="col-md-12">
									<strong>
										<?php echo app('translator')->get('lang_v1.total_items'); ?>:
									</strong>
									<br/>
									<span class="lead text-bold total_quantity">0</span>
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										<?php echo app('translator')->get('sale.total_payable'); ?>:
									</strong>
									<br/>
									<span class="lead text-bold total_payable_span">0</span>
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										<?php echo app('translator')->get('lang_v1.total_paying'); ?>:
									</strong>
									<br/>
									<span class="lead text-bold total_paying">0</span>
									<input type="hidden" id="cash_total_paying_input">
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										<?php echo app('translator')->get('lang_v1.change_return'); ?>:
									</strong>
									<br/>
									<span class="lead text-bold change_return_span">0</span>
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										<?php echo app('translator')->get('lang_v1.balance'); ?>:
									</strong>
									<br/>
									<span class="lead text-bold balance_due">0</span>
									<input type="hidden" id="cash_in_balance_due" value="0">
								</div>
			            </div>
			        </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo app('translator')->get('messages.close'); ?></button>
				<button type="button" class="btn btn-primary" id="pos-cash-save"><?php echo app('translator')->get('sale.finalize_payment'); ?></button>
			</div>
		</div>
	</div>
</div>
<?php /**PATH /var/www/html/resources/views/sale_pos/partials/cash_payment_modal.blade.php ENDPATH**/ ?>