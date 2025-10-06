<span id="view_contact_page"></span>
<div class="row">
    <div class="col-md-12">
        <div class="col-sm-3">
            <?php echo $__env->make('contact.contact_basic_info', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
        <div class="col-sm-3 mt-56">
            <?php echo $__env->make('contact.contact_more_info', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
        <?php if( $contact->type != 'customer'): ?>
            <div class="col-sm-3 mt-56">
                <?php echo $__env->make('contact.contact_tax_info', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            </div>
        <?php endif; ?>
        

        <?php if( $contact->type == 'supplier' || $contact->type == 'both'): ?>
            <div class="clearfix"></div>
            <div class="col-sm-12">
                <?php if(($contact->total_purchase - $contact->purchase_paid) > 0): ?>
                    <a href="<?php echo e(action([\App\Http\Controllers\TransactionPaymentController::class, 'getPayContactDue'], [$contact->id]), false); ?>?type=purchase" class="pay_purchase_due btn btn-primary btn-sm pull-right"><i class="fas fa-money-bill-alt" aria-hidden="true"></i> <?php echo app('translator')->get("contact.pay_due_amount"); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="col-sm-12">
            <button type="button" class="btn btn-primary btn-sm pull-right" data-toggle="modal" data-target="#add_discount_modal"><?php echo app('translator')->get('lang_v1.add_discount'); ?></button>
        </div>
    </div>
</div><?php /**PATH /var/www/html/resources/views/contact/partials/contact_info_tab.blade.php ENDPATH**/ ?>