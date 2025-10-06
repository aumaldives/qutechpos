<?php if($__is_essentials_enabled && $is_employee_allowed): ?>
	<!-- Modern Clock In Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 clock_in_btn
		<?php if(!empty($clock_in)): ?> hide <?php endif; ?>"
	    data-type="clock_in"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    title="<?php echo app('translator')->get('essentials::lang.clock_in'); ?>" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-sign-in" style="color: #3b82f6; font-size: 16px;"></i>
	</button>

	<!-- Modern Clock Out Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 clock_out_btn
		<?php if(empty($clock_in)): ?> hide <?php endif; ?>" 	
	    data-type="clock_out"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    data-html="true"
	    title="<?php echo app('translator')->get('essentials::lang.clock_out'); ?> <?php if(!empty($clock_in)): ?>
                    <br>
                    <small>
                    	<b><?php echo app('translator')->get('essentials::lang.clocked_in_at'); ?>:</b> <?php echo e(\Carbon::createFromTimestamp(strtotime($clock_in->clock_in_time))->format(session('business.date_format') . ' ' . 'H:i'), false); ?>

                    </small>
                    <br>
                    <small><b><?php echo app('translator')->get('essentials::lang.shift'); ?>:</b> <?php echo e(ucfirst($clock_in->shift_name), false); ?></small>
                    <?php if(!empty($clock_in->start_time) && !empty($clock_in->end_time)): ?>
                    	<br>
                    	<small>
                    		<b><?php echo app('translator')->get('restaurant.start_time'); ?>:</b> <?php echo e(\Carbon::createFromTimestamp(strtotime($clock_in->start_time))->format('H:i'), false); ?><br>
                    		<b><?php echo app('translator')->get('restaurant.end_time'); ?>:</b> <?php echo e(\Carbon::createFromTimestamp(strtotime($clock_in->end_time))->format('H:i'), false); ?>

                    	</small>
                    <?php endif; ?>
                <?php endif; ?>" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-sign-out" style="color: #f59e0b; font-size: 16px;"></i>
	</button>

	<!-- Modern OT In Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 ot_in_btn
		<?php if(!empty($ot_session)): ?> hide <?php endif; ?>"
	    data-type="ot_in"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    title="<?php echo app('translator')->get('essentials::lang.ot_in'); ?>" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-clock-o" style="color: #8b5cf6; font-size: 16px;"></i>
	</button>

	<!-- Modern OT Out Button -->
	<button 
		type="button" 
		class="btn btn-flat pull-left m-8 btn-sm mt-10 ot_out_btn
		<?php if(empty($ot_session)): ?> hide <?php endif; ?>" 	
	    data-type="ot_out"
	    data-toggle="tooltip"
	    data-placement="bottom"
	    data-html="true"
	    title="<?php echo app('translator')->get('essentials::lang.ot_out'); ?> <?php if(!empty($ot_session)): ?>
                    <br>
                    <small>
                    	<b><?php echo app('translator')->get('essentials::lang.ot_started_at'); ?>:</b> <?php echo e(\Carbon::createFromTimestamp(strtotime($ot_session->start_time))->format(session('business.date_format') . ' ' . 'H:i'), false); ?>

                    </small>
                <?php endif; ?>" 
	    style="background: rgba(255, 255, 255, 0.1); color: #ffffff; border-radius: 6px; transition: all 0.3s ease; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);"
	    >
	    <i class="fa fa-stop-circle" style="color: #ef4444; font-size: 16px;"></i>
	</button>
<?php endif; ?><?php /**PATH /var/www/html/Modules/Essentials/Providers/../Resources/views/layouts/partials/header_part.blade.php ENDPATH**/ ?>