<div class="row">
  <div class="col-sm-12">
    <table class="table table-condensed">
      <tr>
        <th><?php echo app('translator')->get('lang_v1.payment_method'); ?></th>
        <th><?php echo app('translator')->get('sale.sale'); ?></th>
        <th><?php echo app('translator')->get('lang_v1.expense'); ?></th>
      </tr>
      <tr>
        <td>
          <?php echo app('translator')->get('cash_register.cash_in_hand'); ?>:
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->cash_in_hand, false); ?></span>
        </td>
        <td>--</td>
      </tr>
      <tr>
        <td>
          <?php echo app('translator')->get('cash_register.cash_payment'); ?>:
        </th>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_cash, false); ?></span>
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_cash_expense, false); ?></span>
        </td>
      </tr>
      <tr>
        <td>
          <?php echo app('translator')->get('cash_register.checque_payment'); ?>:
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_cheque, false); ?></span>
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_cheque_expense, false); ?></span>
        </td>
      </tr>
      <tr>
        <td>
          <?php echo app('translator')->get('cash_register.card_payment'); ?>:
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_card, false); ?></span>
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_card_expense, false); ?></span>
        </td>
      </tr>
      <tr>
        <td>
          <?php echo app('translator')->get('cash_register.bank_transfer'); ?>:
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_bank_transfer, false); ?></span>
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_bank_transfer_expense, false); ?></span>
        </td>
      </tr>
      <tr>
        <td>
          <?php echo app('translator')->get('lang_v1.advance_payment'); ?>:
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_advance, false); ?></span>
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_advance_expense, false); ?></span>
        </td>
      </tr>
      <?php if(array_key_exists('custom_pay_1', $payment_types)): ?>
        <tr>
          <td>
            <?php echo e($payment_types['custom_pay_1'], false); ?>:
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_1, false); ?></span>
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_1_expense, false); ?></span>
          </td>
        </tr>
      <?php endif; ?>
      <?php if(array_key_exists('custom_pay_2', $payment_types)): ?>
        <tr>
          <td>
            <?php echo e($payment_types['custom_pay_2'], false); ?>:
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_2, false); ?></span>
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_2_expense, false); ?></span>
          </td>
        </tr>
      <?php endif; ?>
      <?php if(array_key_exists('custom_pay_3', $payment_types)): ?>
        <tr>
          <td>
            <?php echo e($payment_types['custom_pay_3'], false); ?>:
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_3, false); ?></span>
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_3_expense, false); ?></span>
          </td>
        </tr>
      <?php endif; ?>
      <?php if(array_key_exists('custom_pay_4', $payment_types)): ?>
        <tr>
          <td>
            <?php echo e($payment_types['custom_pay_4'], false); ?>:
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_4, false); ?></span>
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_4_expense, false); ?></span>
          </td>
        </tr>
      <?php endif; ?>
      <?php if(array_key_exists('custom_pay_5', $payment_types)): ?>
        <tr>
          <td>
            <?php echo e($payment_types['custom_pay_5'], false); ?>:
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_5, false); ?></span>
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_5_expense, false); ?></span>
          </td>
        </tr>
      <?php endif; ?>
      <?php if(array_key_exists('custom_pay_6', $payment_types)): ?>
        <tr>
          <td>
            <?php echo e($payment_types['custom_pay_6'], false); ?>:
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_6, false); ?></span>
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_6_expense, false); ?></span>
          </td>
        </tr>
      <?php endif; ?>
      <?php if(array_key_exists('custom_pay_7', $payment_types)): ?>
        <tr>
          <td>
            <?php echo e($payment_types['custom_pay_7'], false); ?>:
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_7, false); ?></span>
          </td>
          <td>
            <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_7_expense, false); ?></span>
          </td>
        </tr>
      <?php endif; ?>
      <tr>
        <td>
          <?php echo app('translator')->get('cash_register.other_payments'); ?>:
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_other, false); ?></span>
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_other_expense, false); ?></span>
        </td>
      </tr>
    </table>
    <hr>
    <table class="table table-condensed">
      <tr>
        <td>
          <?php echo app('translator')->get('cash_register.total_sales'); ?>:
        </td>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_sale, false); ?></span>
        </td>
      </tr>
      <tr class="danger">
        <th>
          <?php echo app('translator')->get('cash_register.total_refund'); ?>
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_refund, false); ?></span></b><br>
          <small>
          <?php if($register_details->total_cash_refund != 0): ?>
            Cash: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_cash_refund, false); ?></span><br>
          <?php endif; ?>
          <?php if($register_details->total_cheque_refund != 0): ?> 
            Cheque: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_cheque_refund, false); ?></span><br>
          <?php endif; ?>
          <?php if($register_details->total_card_refund != 0): ?> 
            Card: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_card_refund, false); ?></span><br> 
          <?php endif; ?>
          <?php if($register_details->total_bank_transfer_refund != 0): ?>
            Bank Transfer: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_bank_transfer_refund, false); ?></span><br>
          <?php endif; ?>
          <?php if(array_key_exists('custom_pay_1', $payment_types) && $register_details->total_custom_pay_1_refund != 0): ?>
              <?php echo e($payment_types['custom_pay_1'], false); ?>: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_1_refund, false); ?></span>
          <?php endif; ?>
          <?php if(array_key_exists('custom_pay_2', $payment_types) && $register_details->total_custom_pay_2_refund != 0): ?>
              <?php echo e($payment_types['custom_pay_2'], false); ?>: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_2_refund, false); ?></span>
          <?php endif; ?>
          <?php if(array_key_exists('custom_pay_3', $payment_types) && $register_details->total_custom_pay_3_refund != 0): ?>
              <?php echo e($payment_types['custom_pay_3'], false); ?>: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_custom_pay_3_refund, false); ?></span>
          <?php endif; ?>
          <?php if($register_details->total_other_refund != 0): ?>
            Other: <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_other_refund, false); ?></span>
          <?php endif; ?>
          </small>
        </td>
      </tr>
      <tr class="info">
        <th>
          <?php echo app('translator')->get('cash_register.adjustments'); ?>:
        </th>
        <td>
          <span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_cash_adjustments ?? 0, false); ?></span>
        </td>
      </tr>
      <tr class="success">
        <th>
          <?php echo app('translator')->get('lang_v1.total_payment'); ?>
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund + ($register_details->total_cash_adjustments ?? 0), false); ?></span></b>
        </td>
      </tr>
      <tr class="success">
        <th>
          <?php echo app('translator')->get('lang_v1.credit_sales'); ?>:
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true"><?php echo e($details['transaction_details']->total_sales - $register_details->total_sale, false); ?></span></b>
        </td>
      </tr>
      <tr class="success">
        <th>
          <?php echo app('translator')->get('cash_register.total_sales'); ?>:
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true"><?php echo e($details['transaction_details']->total_sales, false); ?></span></b>
        </td>
      </tr>
      <tr class="danger">
        <th>
          <?php echo app('translator')->get('report.total_expense'); ?>:
        </th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true"><?php echo e($register_details->total_expense, false); ?></span></b>
        </td>
      </tr>
    </table>
  </div>
</div>

<?php echo $__env->make('cash_register.register_product_details', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/cash_register/payment_details.blade.php ENDPATH**/ ?>