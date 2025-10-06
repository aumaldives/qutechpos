<a class="btn btn-sm btn-primary pull-right contact-login-add" data-href="<?php echo e(action([\Modules\Crm\Http\Controllers\ContactLoginController::class, 'create']), false); ?>" >
	<i class="fa fa-plus"></i>
	<?php echo app('translator')->get( 'messages.add' ); ?>
</a>
<br><br>
<div class="table-responsive">
	<table class="table table-bordered table-striped" id="contact_login_table" style="width: 100%;">
		<thead>
			<tr>
				<th><?php echo app('translator')->get('messages.action'); ?></th>
				<th><?php echo app('translator')->get('business.username'); ?></th>
                <th><?php echo app('translator')->get('user.name'); ?></th>
                <th><?php echo app('translator')->get( 'business.email' ); ?></th>
                <th><?php echo app('translator')->get( 'lang_v1.department' ); ?></th>
                <th><?php echo app('translator')->get( 'lang_v1.designation' ); ?></th>
			</tr>
		</thead>
	</table>
</div>
<!-- modal -->
<div class="modal fade contact_login_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div><?php /**PATH /var/www/html/Modules/Crm/Providers/../Resources/views/contact_login/index.blade.php ENDPATH**/ ?>