<!-- Edit Plasticbag Modal -->
<div class="modal fade" tabindex="-1" role="dialog" id="posPlasticbagModal">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo app('translator')->get('Plastic Bag Selection'); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<h5><?php echo app('translator')->get('Select Plastic Bag Types and Quantities'); ?></h5>
						<div class="table-responsive">
							<table class="table table-bordered" id="plastic_bag_selection_table">
								<thead>
									<tr>
										<th><?php echo app('translator')->get('Plastic Bag Type'); ?></th>
										<th><?php echo app('translator')->get('Price per Bag'); ?></th>
										<th><?php echo app('translator')->get('Quantity'); ?></th>
										<th><?php echo app('translator')->get('Total'); ?></th>
									</tr>
								</thead>
								<tbody id="plastic_bag_rows">
									<!-- Plastic bag rows will be loaded here -->
								</tbody>
								<tfoot>
									<tr>
										<th colspan="3"><?php echo app('translator')->get('Total Plastic Bag Charges'); ?>:</th>
										<th>
											<span id="total_plastic_bag_amount">0.00</span>
										</th>
									</tr>
								</tfoot>
							</table>
						</div>
						<div class="text-muted">
							<small><?php echo app('translator')->get('Note: Plastic bag charges will be added to the total bill amount.'); ?></small>
						</div>
				    </div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" id="posPlasticbagModalUpdate"><?php echo app('translator')->get('messages.update'); ?></button>
			    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo app('translator')->get('messages.cancel'); ?></button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal --><?php /**PATH /var/www/html/resources/views/sale_pos/partials/edit_plasticbag_modal.blade.php ENDPATH**/ ?>