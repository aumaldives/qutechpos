<section class="no-print">
    <nav class="navbar navbar-default bg-white m-4">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?php echo e(action([\Modules\Superadmin\Http\Controllers\SuperadminController::class, 'index']), false); ?>"><i class="fa fas fa-users-cog"></i> <?php echo e(__('superadmin::lang.superadmin'), false); ?></a>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li <?php if(request()->segment(1) == 'superadmin' && request()->segment(2) == 'business'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([Modules\Superadmin\Http\Controllers\BusinessController::class, 'index']), false); ?>"><?php echo app('translator')->get('superadmin::lang.all_business'); ?></a></li>

                    <li <?php if(request()->segment(1) == 'superadmin' && request()->segment(2) == 'superadmin-subscription'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController::class, 'index']), false); ?>"><?php echo app('translator')->get('superadmin::lang.subscription'); ?></a></li>

                    <li <?php if(request()->segment(1) == 'superadmin' && request()->segment(2) == 'packages'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Superadmin\Http\Controllers\PackagesController::class, 'index']), false); ?>"><?php echo app('translator')->get('superadmin::lang.subscription_packages'); ?></a></li>

                    <li <?php if(request()->segment(1) == 'superadmin' && request()->segment(2) == 'banks'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Superadmin\Http\Controllers\BankManagementController::class, 'index']), false); ?>"><i class="fa fa-university"></i> Bank Management</a></li>

                    <li <?php if(request()->segment(1) == 'superadmin' && request()->segment(2) == 'settings'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Superadmin\Http\Controllers\SuperadminSettingsController::class, 'edit']), false); ?>"><?php echo app('translator')->get('superadmin::lang.super_admin_settings'); ?></a></li>

                    <li <?php if(request()->segment(1) == 'superadmin' && request()->segment(2) == 'communicator'): ?> class="active" <?php endif; ?>><a href="<?php echo e(action([\Modules\Superadmin\Http\Controllers\CommunicatorController::class, 'index']), false); ?>"><?php echo app('translator')->get('superadmin::lang.communicator'); ?></a></li>
                </ul>

            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
</section><?php /**PATH /var/www/html/Modules/Superadmin/Providers/../Resources/views/layouts/nav.blade.php ENDPATH**/ ?>