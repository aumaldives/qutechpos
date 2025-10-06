<div class="modal fade" tabindex="-1" role="dialog" id="modal_cash_payment">
	<div class="modal-dialog modal-xl" role="document" style="width: 95%; max-width: 1200px;">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">@lang('lang_v1.payment') - @lang('sale.cash')</h4>
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
											<input type="hidden" class="payment_types_dropdown" name="payment[0][method]" value="cash">

											<div class="col-md-6">
												<div class="form-group">
													{!! Form::label("cash_amount_0" ,__('sale.amount') . ':*') !!}
													<div class="input-group">
														<span class="input-group-addon">
															<i class="fas fa-money-bill-alt"></i>
														</span>
														{!! Form::text("payment[0][amount]", 0, ['class' => 'form-control input_number', 'required', 'id' => "cash_amount_0", 'placeholder' => __('sale.amount'), 'autocomplete' => 'off']); !!}
													</div>
												</div>
											</div>

											@php
												$pos_settings = !empty(session()->get('business.pos_settings')) ? json_decode(session()->get('business.pos_settings'), true) : [];
												$enable_cash_denomination_for_payment_methods = !empty($pos_settings['enable_cash_denomination_for_payment_methods']) ? $pos_settings['enable_cash_denomination_for_payment_methods'] : [];
											@endphp

											@if(!empty($pos_settings['enable_cash_denomination_on']) && $pos_settings['enable_cash_denomination_on'] == 'all_screens' && !empty($show_denomination))
												<input type="hidden" class="enable_cash_denomination_for_payment_methods" value="{{json_encode($enable_cash_denomination_for_payment_methods)}}">
												<div class="clearfix"></div>
												<div class="col-md-12 cash_denomination_div">
													<hr>
													<strong>@lang( 'lang_v1.cash_denominations' )</strong>
													@if(!empty($pos_settings['cash_denominations']))
														<table class="table table-slim">
															<thead>
																<tr>
																	<th width="20%" class="text-right">@lang('lang_v1.denomination')</th>
																	<th width="20%">&nbsp;</th>
																	<th width="20%" class="text-center">@lang('lang_v1.count')</th>
																	<th width="20%">&nbsp;</th>
																	<th width="20%" class="text-left">@lang('sale.subtotal')</th>
																</tr>
															</thead>
															<tbody>
																@php
																	$total = 0;
																@endphp
																@foreach(explode(',', $pos_settings['cash_denominations']) as $dnm)
																<tr>
																	<td class="text-right">{{$dnm}}</td>
																	<td class="text-center" >X</td>
																	<td>{!! Form::number("payment[0][denominations][$dnm]", 0, ['class' => 'form-control cash_denomination input-sm', 'min' => 0, 'data-denomination' => $dnm, 'style' => 'width: 100px; margin:auto;' ]); !!}</td>
																	<td class="text-center">=</td>
																	<td class="text-left">
																		<span class="denomination_subtotal">{{@num_format(0)}}</span>
																	</td>
																</tr>
																@endforeach
															</tbody>
															<tfoot>
																<tr>
																	<th colspan="4" class="text-center">@lang('sale.total')</th>
																	<td>
																		<span class="denomination_total">{{@num_format(0)}}</span>
																		<input type="hidden" class="denomination_total_amount" value="0">
																		<input type="hidden" class="is_strict" value="{{$pos_settings['cash_denomination_strict_check'] ?? ''}}">
																	</td>
																</tr>
															</tfoot>
														</table>
														<p class="cash_denomination_error error hide">@lang('lang_v1.cash_denomination_error')</p>
													@else
														<p class="help-block">@lang('lang_v1.denomination_add_help_text')</p>
													@endif
												</div>
												<div class="clearfix"></div>
											@endif

											<div class="col-md-12">
												<div class="form-group">
													{!! Form::label('cash_note_0', __('lang_v1.payment_note') . ':') !!}
													{!! Form::textarea('payment[0][note]', null, ['class' => 'form-control', 'rows' => 3, 'id' => 'cash_note_0', 'placeholder' => __('lang_v1.payment_note')]); !!}
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
										@lang('lang_v1.total_items'):
									</strong>
									<br/>
									<span class="lead text-bold total_quantity">0</span>
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										@lang('sale.total_payable'):
									</strong>
									<br/>
									<span class="lead text-bold total_payable_span">0</span>
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										@lang('lang_v1.total_paying'):
									</strong>
									<br/>
									<span class="lead text-bold total_paying">0</span>
									<input type="hidden" id="cash_total_paying_input">
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										@lang('lang_v1.change_return'):
									</strong>
									<br/>
									<span class="lead text-bold change_return_span">0</span>
								</div>

								<div class="col-md-12">
									<hr>
									<strong>
										@lang('lang_v1.balance'):
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
				<button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
				<button type="button" class="btn btn-primary" id="pos-cash-save">@lang('sale.finalize_payment')</button>
			</div>
		</div>
	</div>
</div>
