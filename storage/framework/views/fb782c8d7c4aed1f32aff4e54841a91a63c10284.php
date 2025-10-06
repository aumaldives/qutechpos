<div class="modal fade" id="ot_in_out_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog" role="document">
	  <div class="modal-content">

	    <?php echo Form::open(['url' => action([\Modules\Essentials\Http\Controllers\AttendanceController::class, 'otInOut']), 'method' => 'post', 'id' => 'ot_in_out_form' ]); ?>

	    <div class="modal-header">
	      	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	      	<h4 class="modal-title"><span id="ot_in_text"><?php echo app('translator')->get( 'essentials::lang.ot_in' ); ?></span>
	      	<span id="ot_out_text"><?php echo app('translator')->get( 'essentials::lang.ot_out' ); ?></span></h4>
	    </div>
	    <div class="modal-body">
	    	<div class="row">
	    		<input type="hidden" name="type" id="ot_type">
		      	<div class="form-group col-md-12">
		      		<strong><?php echo app('translator')->get( 'essentials::lang.ip_address' ); ?>: <?php echo e($ip_address, false); ?></strong>
		      	</div>
		      	<div class="form-group col-md-12 ot_in_note <?php if(!empty($ot_session)): ?> hide <?php endif; ?>">
		        	<?php echo Form::label('ot_in_note', __( 'essentials::lang.ot_in_note' ) . ':'); ?>

		        	<?php echo Form::textarea('ot_in_note', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.ot_in_note'), 'rows' => 3 ]); ?>

		      	</div>
		      	<div class="form-group col-md-12 ot_out_note <?php if(empty($ot_session)): ?> hide <?php endif; ?>">
		        	<?php echo Form::label('ot_out_note', __( 'essentials::lang.ot_out_note' ) . ':'); ?>

		        	<?php echo Form::textarea('ot_out_note', null, ['class' => 'form-control', 'placeholder' => __( 'essentials::lang.ot_out_note'), 'rows' => 3 ]); ?>

		      	</div>
		      	<input type="hidden" name="ot_location" id="ot_location" value="">
	    	</div>
	    	<?php if($is_location_required): ?>
		    	<div class="row">
		    		<div class="col-md-12">
		    			<b><?php echo app('translator')->get('messages.location'); ?>:</b> <button type="button" class="btn btn-primary btn-xs" id="get_current_location_ot"> <i class="fas fa-map-marker-alt"></i> <?php echo app('translator')->get('essentials::lang.get_current_location'); ?></button>
		    			<br><span class="ot_location"></span>
		    		</div>
		    		<div class="col-md-12 ask_location_ot" style="display: none;">
		    			<span class="location_required_ot error"></span>
		    		</div>
		    	</div>
		    <?php endif; ?>
	    </div>

	    <div class="modal-footer">
	      <button type="submit" class="btn btn-primary"><?php echo app('translator')->get( 'messages.submit' ); ?></button>
	      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo app('translator')->get( 'messages.close' ); ?></button>
	    </div>

	    <?php echo Form::close(); ?>


	  </div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
        	
</div><?php /**PATH /var/www/html/Modules/Essentials/Providers/../Resources/views/attendance/ot_in_out_modal.blade.php ENDPATH**/ ?>