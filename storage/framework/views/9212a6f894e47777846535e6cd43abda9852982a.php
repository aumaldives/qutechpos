<table class="table <?php if(!empty($for_ledger)): ?> table-slim mb-0 bg-light-gray <?php else: ?> bg-gray <?php endif; ?>" <?php if(!empty($for_pdf)): ?> style="width: 100%;" <?php endif; ?>>
        <tr <?php if(empty($for_ledger)): ?> class="bg-green" <?php endif; ?>>
        <th>#</th>
        <th><?php echo e(__('sale.product'), false); ?></th>
        <?php if( session()->get('business.enable_lot_number') == 1 && empty($for_ledger)): ?>
            <th><?php echo e(__('lang_v1.lot_n_expiry'), false); ?></th>
        <?php endif; ?>
        <?php if($sell->type == 'sales_order'): ?>
            <th><?php echo app('translator')->get('lang_v1.quantity_remaining'); ?></th>
        <?php endif; ?>
        <th><?php echo e(__('sale.qty'), false); ?></th>
        <?php if(!empty($pos_settings['inline_service_staff'])): ?>
            <th>
                <?php echo app('translator')->get('restaurant.service_staff'); ?>
            </th>
        <?php endif; ?>
        <th><?php echo e(__('sale.unit_price'), false); ?></th>
        <th><?php echo e(__('sale.discount'), false); ?></th>
        <th><?php echo e(__('sale.tax'), false); ?></th>
        <th><?php echo e(__('sale.price_inc_tax'), false); ?></th>
        <th><?php echo e(__('sale.subtotal'), false); ?></th>
    </tr>
    <?php $__currentLoopData = $sell->sell_lines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sell_line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($loop->iteration, false); ?></td>
            <td>
                <?php echo e($sell_line->product->name, false); ?>

                <?php if( $sell_line->product->type == 'variable'): ?>
                - <?php echo e($sell_line->variations->product_variation->name ?? '', false); ?>

                - <?php echo e($sell_line->variations->name ?? '', false); ?>,
                <?php endif; ?>
                <?php echo e($sell_line->variations->sub_sku ?? '', false); ?>

                <?php
                $brand = $sell_line->product->brand;
                ?>
                <?php if(!empty($brand->name)): ?>
                , <?php echo e($brand->name, false); ?>

                <?php endif; ?>

                <?php if(!empty($sell_line->sell_line_note)): ?>
                <br> <?php echo e($sell_line->sell_line_note, false); ?>

                <?php endif; ?>
                <?php if($is_warranty_enabled && !empty($sell_line->warranties->first()) ): ?>
                    <br><small><?php echo e($sell_line->warranties->first()->display_name ?? '', false); ?> - <?php echo e(\Carbon::createFromTimestamp(strtotime($sell_line->warranties->first()->getEndDate($sell->transaction_date)))->format(session('business.date_format')), false); ?></small>
                    <?php if(!empty($sell_line->warranties->first()->description)): ?>
                    <br><small><?php echo e($sell_line->warranties->first()->description ?? '', false); ?></small>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if(in_array('kitchen', $enabled_modules) && empty($for_ledger)): ?>
                    <br><span class="label <?php if($sell_line->res_line_order_status == 'cooked' ): ?> bg-red <?php elseif($sell_line->res_line_order_status == 'served'): ?> bg-green <?php else: ?> bg-light-blue <?php endif; ?>"><?php echo app('translator')->get('restaurant.order_statuses.' . $sell_line->res_line_order_status); ?> </span>
                <?php endif; ?>
            </td>
            <?php if( session()->get('business.enable_lot_number') == 1 && empty($for_ledger)): ?>
                <td><?php echo e($sell_line->lot_details->lot_number ?? '--', false); ?>

                    <?php if( session()->get('business.enable_product_expiry') == 1 && !empty($sell_line->lot_details->exp_date)): ?>
                    (<?php echo e(\Carbon::createFromTimestamp(strtotime($sell_line->lot_details->exp_date))->format(session('business.date_format')), false); ?>)
                    <?php endif; ?>
                </td>
            <?php endif; ?>
            <?php if($sell->type == 'sales_order'): ?>
                <td><span class="display_currency" data-currency_symbol="false" data-is_quantity="true"><?php echo e($sell_line->quantity - $sell_line->so_quantity_invoiced, false); ?></span> <?php if(!empty($sell_line->sub_unit)): ?> <?php echo e($sell_line->sub_unit->short_name, false); ?> <?php else: ?> <?php echo e($sell_line->product->unit->short_name, false); ?> <?php endif; ?></td>
            <?php endif; ?>
            <td>
                <?php if(!empty($for_ledger)): ?>
                    <?php echo e(number_format($sell_line->quantity, session('business.quantity_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), false); ?>

                <?php else: ?>
                    <span class="display_currency" data-currency_symbol="false" data-is_quantity="true"><?php echo e($sell_line->quantity, false); ?></span> 
                <?php endif; ?>
                    <?php if(!empty($sell_line->sub_unit)): ?> <?php echo e($sell_line->sub_unit->short_name, false); ?> <?php else: ?> <?php echo e($sell_line->product->unit->short_name, false); ?> <?php endif; ?>

                <?php if(!empty($sell_line->product->second_unit) && $sell_line->secondary_unit_quantity != 0): ?>
                    <br>
                    <?php if(!empty($for_ledger)): ?>
                        <?php echo e(number_format($sell_line->secondary_unit_quantity, session('business.quantity_precision', 2), session('currency')['decimal_separator'], session('currency')['thousand_separator']), false); ?>

                    <?php else: ?>
                        <span class="display_currency" data-is_quantity="true" data-currency_symbol="false"><?php echo e($sell_line->secondary_unit_quantity, false); ?></span> 
                    <?php endif; ?>
                    <?php echo e($sell_line->product->second_unit->short_name, false); ?>

                <?php endif; ?>
            </td>
            <?php if(!empty($pos_settings['inline_service_staff'])): ?>
                <td>
                <?php echo e($sell_line->service_staff->user_full_name ?? '', false); ?>

                </td>
            <?php endif; ?>
            <td>
                <?php if(!empty($for_ledger)): ?>
                    <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $sell_line->unit_price_before_discount, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                <?php else: ?>
                    <span class="display_currency" data-currency_symbol="true"><?php echo e($sell_line->unit_price_before_discount, false); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if(!empty($for_ledger)): ?>
                    <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $sell_line->get_discount_amount(), session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                <?php else: ?>
                    <span class="display_currency" data-currency_symbol="true"><?php echo e($sell_line->get_discount_amount(), false); ?></span>
                <?php endif; ?>
                <?php if($sell_line->line_discount_type == 'percentage'): ?> (<?php echo e($sell_line->line_discount_amount, false); ?>%) <?php endif; ?>
            </td>
            <td>
                <?php if(!empty($for_ledger)): ?>
                    <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $sell_line->item_tax, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                <?php else: ?>
                    <span class="display_currency" data-currency_symbol="true"><?php echo e($sell_line->item_tax, false); ?></span> 
                <?php endif; ?>
                <?php if(!empty($taxes[$sell_line->tax_id])): ?>
                ( <?php echo e($taxes[$sell_line->tax_id], false); ?> )
                <?php endif; ?>
            </td>
            <td>
                <?php if(!empty($for_ledger)): ?>
                    <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $sell_line->unit_price_inc_tax, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                <?php else: ?>
                    <span class="display_currency" data-currency_symbol="true"><?php echo e($sell_line->unit_price_inc_tax, false); ?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if(!empty($for_ledger)): ?>
                    <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $sell_line->quantity * $sell_line->unit_price_inc_tax, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                <?php else: ?>
                    <span class="display_currency" data-currency_symbol="true"><?php echo e($sell_line->quantity * $sell_line->unit_price_inc_tax, false); ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php if(!empty($sell_line->modifiers)): ?>
        <?php $__currentLoopData = $sell_line->modifiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $modifier): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <?php echo e($modifier->product->name, false); ?> - <?php echo e($modifier->variations->name ?? '', false); ?>,
                    <?php echo e($modifier->variations->sub_sku ?? '', false); ?>

                </td>
                <?php if( session()->get('business.enable_lot_number') == 1): ?>
                    <td>&nbsp;</td>
                <?php endif; ?>
                <td><?php echo e($modifier->quantity, false); ?></td>
                <?php if(!empty($pos_settings['inline_service_staff'])): ?>
                    <td>
                        &nbsp;
                    </td>
                <?php endif; ?>
                <td>
                    <?php if(!empty($for_ledger)): ?>
                        <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $modifier->unit_price, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                    <?php else: ?>
                        <span class="display_currency" data-currency_symbol="true"><?php echo e($modifier->unit_price, false); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    &nbsp;
                </td>
                <td>
                    <?php if(!empty($for_ledger)): ?>
                        <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $modifier->item_tax, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                    <?php else: ?>
                        <span class="display_currency" data-currency_symbol="true"><?php echo e($modifier->item_tax, false); ?></span> 
                    <?php endif; ?>
                    <?php if(!empty($taxes[$modifier->tax_id])): ?>
                    ( <?php echo e($taxes[$modifier->tax_id], false); ?> )
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!empty($for_ledger)): ?>
                        <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $modifier->unit_price_inc_tax, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                    <?php else: ?>
                        <span class="display_currency" data-currency_symbol="true"><?php echo e($modifier->unit_price_inc_tax, false); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!empty($for_ledger)): ?>
                        <?php 
            $formated_number = "";
            if (session("business.currency_symbol_placement") == "before") {
                $formated_number .= session("currency")["symbol"] . " ";
            } 
            $formated_number .= number_format((float) $modifier->quantity * $modifier->unit_price_inc_tax, session("business.currency_precision", 2) , session("currency")["decimal_separator"], session("currency")["thousand_separator"]);

            if (session("business.currency_symbol_placement") == "after") {
                $formated_number .= " " . session("currency")["symbol"];
            }
            echo $formated_number; ?>
                    <?php else: ?>
                        <span class="display_currency" data-currency_symbol="true"><?php echo e($modifier->quantity * $modifier->unit_price_inc_tax, false); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table><?php /**PATH /var/www/html/resources/views/sale_pos/partials/sale_line_details.blade.php ENDPATH**/ ?>