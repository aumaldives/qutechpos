<?php if(!empty($__subscription) && env('APP_ENV') != 'demo'): ?>
<?php
    $package_details = is_array($__subscription->package_details) ? $__subscription->package_details : json_decode($__subscription->package_details, true) ?? [];
?>
<i class="fas fa-info-circle pull-left mt-10 cursor-pointer" style= "margin-top: 24px; color:white" aria-hidden="true" data-toggle="popover" data-html="true" title="<?php echo app('translator')->get('superadmin::lang.active_package_description'); ?>" data-placement="right" data-trigger="hover" data-content="
    <table class='table table-condensed'>
     <tr class='text-center'> 
        <td colspan='2'>
            <?php echo e($package_details['name'] ?? 'N/A', false); ?>

        </td>
     </tr>
     <tr class='text-center'>
        <td colspan='2'>
            <?php echo e(\Carbon::createFromTimestamp(strtotime($__subscription->start_date))->format(session('business.date_format')), false); ?> - <?php echo e(\Carbon::createFromTimestamp(strtotime($__subscription->end_date))->format(session('business.date_format')), false); ?>

        </td>
     </tr>
     <tr> 
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            <?php if(($package_details['location_count'] ?? 0) == 0): ?>
                <?php echo app('translator')->get('superadmin::lang.unlimited'); ?>
            <?php else: ?>
                <?php echo e($package_details['location_count'] ?? 0, false); ?>

            <?php endif; ?>

            <?php echo app('translator')->get('business.business_locations'); ?>
        </td>
     </tr>
     <tr>
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            <?php if(($package_details['user_count'] ?? 0) == 0): ?>
                <?php echo app('translator')->get('superadmin::lang.unlimited'); ?>
            <?php else: ?>
                <?php echo e($package_details['user_count'] ?? 0, false); ?>

            <?php endif; ?>

            <?php echo app('translator')->get('superadmin::lang.users'); ?>
        </td>
     <tr>
     <tr>
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            <?php if(($package_details['product_count'] ?? 0) == 0): ?>
                <?php echo app('translator')->get('superadmin::lang.unlimited'); ?>
            <?php else: ?>
                <?php echo e($package_details['product_count'] ?? 0, false); ?>

            <?php endif; ?>

            <?php echo app('translator')->get('superadmin::lang.products'); ?>
        </td>
     </tr>
     <tr>
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            <?php if(($package_details['invoice_count'] ?? 0) == 0): ?>
                <?php echo app('translator')->get('superadmin::lang.unlimited'); ?>
            <?php else: ?>
                <?php echo e($package_details['invoice_count'] ?? 0, false); ?>

            <?php endif; ?>

            <?php echo app('translator')->get('superadmin::lang.invoices'); ?>
        </td>
     </tr>
     
    </table>                     
">
</i>
<?php endif; ?><?php /**PATH /var/www/html/Modules/Superadmin/Providers/../Resources/views/layouts/partials/active_subscription.blade.php ENDPATH**/ ?>