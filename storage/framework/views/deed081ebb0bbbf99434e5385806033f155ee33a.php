<?php
    $transaction_types = [];
    if(in_array($contact->type, ['both', 'supplier'])){
        $transaction_types['purchase'] = __('lang_v1.purchase');
        $transaction_types['purchase_return'] = __('lang_v1.purchase_return');
    }

    if(in_array($contact->type, ['both', 'customer'])){
        $transaction_types['sell'] = __('sale.sale');
        $transaction_types['sell_return'] = __('lang_v1.sell_return');
    }

    $transaction_types['opening_balance'] = __('lang_v1.opening_balance');
?>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-3">
            <div class="form-group">
                <?php echo Form::label('ledger_date_range', __('report.date_range') . ':'); ?>

                <?php echo Form::text('ledger_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); ?>

            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label><?php echo app('translator')->get('lang_v1.ledger_format'); ?></label>
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default active">
                    <input type="radio" name="ledger_format" value="format_1" checked> <?php echo app('translator')->get('lang_v1.format_1'); ?>
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="ledger_format" value="format_2"> <?php echo app('translator')->get('lang_v1.format_2'); ?>
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="ledger_format" value="format_3"> <?php echo app('translator')->get('lang_v1.format_3'); ?>
                </label>
            </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <?php echo Form::label('ledger_location', __('purchase.business_location') . ':'); ?>

                <?php echo Form::select('ledger_location', $business_locations, null , ['class' => 'form-control select2', 'id' => 'ledger_location']); ?>

            </div>
        </div>
        <div class="col-md-2 text-right">
            <button data-href="<?php echo e(action([\App\Http\Controllers\ContactController::class, 'getLedger']), false); ?>?contact_id=<?php echo e($contact->id, false); ?>&action=pdf" class="btn btn-default btn-xs" id="print_ledger_pdf"><i class="fas fa-file-pdf"></i></button>

            <button type="button" class="btn btn-default btn-xs" id="send_ledger"><i class="fas fa-envelope"></i></button>
        </div>
    </div>
    <div id="contact_ledger_div"></div>
</div><?php /**PATH /var/www/html/resources/views/contact/partials/ledger_tab.blade.php ENDPATH**/ ?>