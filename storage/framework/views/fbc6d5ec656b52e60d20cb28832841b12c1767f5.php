<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token(), false); ?>">
    <title><?php echo e(__('sale.pos_sale'), false); ?> - <?php echo e(config('app.name', 'POS'), false); ?></title>

    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/271b9f073a.js" crossorigin="anonymous"></script>

    <!-- jQuery -->
    <script src="<?php echo e(asset('js/jquery-3.6.0.min.js'), false); ?>"></script>

    <!-- Bootstrap CSS (for compatibility) -->
    <link rel="stylesheet" href="<?php echo e(asset('css/bootstrap.min.css'), false); ?>">

    <!-- iCheck -->
    <link rel="stylesheet" href="<?php echo e(asset('plugins/iCheck/square/blue.css'), false); ?>">

    <!-- Custom CSS -->
    <?php echo $__env->make('layouts.partials.css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Modern Card */
        .modern-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            padding: 24px;
            margin-bottom: 20px;
        }

        /* Special padding for POS container card */
        .modern-card.pos-container {
            padding: 0 !important;
        }

        /* Modern Input */
        .modern-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .modern-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        /* Modern Button */
        .modern-btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modern-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
        }

        .modern-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .modern-btn-success {
            background: linear-gradient(135deg, #0cebeb 0%, #20e3b2 100%);
            color: #ffffff;
        }

        .modern-btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(12, 235, 235, 0.4);
        }

        .modern-btn-white {
            background: #ffffff;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .modern-btn-white:hover {
            border-color: #667eea;
            color: #667eea;
        }

        /* Modern Table */
        .modern-table,
        #pos_table {
            width: 100% !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            border-radius: 16px !important;
            overflow: hidden !important;
            margin-bottom: 0 !important;
        }

        .modern-table thead,
        #pos_table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .modern-table thead th,
        #pos_table thead th {
            color: #ffffff !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 12px !important;
            letter-spacing: 0.5px !important;
            padding: 16px 12px !important;
            text-align: center !important;
            border: none !important;
        }

        .modern-table tbody tr,
        #pos_table tbody tr {
            transition: all 0.2s ease !important;
            border-bottom: 1px solid #f3f4f6 !important;
        }

        .modern-table tbody tr:hover,
        #pos_table tbody tr:hover {
            background-color: #f9fafb !important;
        }

        .modern-table tbody td,
        #pos_table tbody td {
            padding: 12px !important;
            text-align: center !important;
        }

        /* Modern Totals Section */
        .pos_form_totals {
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%) !important;
            border-top: 3px solid #667eea !important;
            border-radius: 16px 16px 0 0 !important;
            padding: 20px !important;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            margin: 0 !important;
        }

        .pos_form_totals table {
            width: 100% !important;
            margin: 0 !important;
            border: none !important;
        }

        .pos_form_totals td {
            padding: 10px 8px !important;
            font-weight: 600 !important;
            border: none !important;
            background: transparent !important;
        }

        .pos_form_totals tr {
            border: none !important;
        }

        .pos_form_totals b {
            color: #374151 !important;
            font-size: 13px !important;
        }

        .pos_form_totals span:not(.fa):not(.fas) {
            color: #667eea !important;
            font-weight: 700 !important;
            font-size: 14px !important;
        }

        .pos_form_totals .total_quantity,
        .pos_form_totals .price_total,
        .pos_form_totals #total_discount,
        .pos_form_totals #order_tax,
        .pos_form_totals #shipping_charges_amount,
        .pos_form_totals #plasticbag_charges_amount,
        .pos_form_totals #packing_charge_text,
        .pos_form_totals #round_off_text {
            color: #667eea !important;
            font-weight: 700 !important;
            font-size: 15px !important;
        }

        /* Make sure all totals columns are visible */
        .pos_form_totals .col-md-12 {
            padding: 0 !important;
        }

        .pos_form_totals tr td {
            white-space: nowrap !important;
            vertical-align: middle !important;
        }

        /* Edit icons in totals */
        .pos_form_totals .fa-edit {
            cursor: pointer !important;
            margin-left: 4px !important;
            margin-right: 4px !important;
            color: #667eea !important;
            transition: all 0.3s ease !important;
        }

        .pos_form_totals .fa-edit:hover {
            color: #764ba2 !important;
            transform: scale(1.2) !important;
        }

        /* Scrollable Area */
        .scrollable-products {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
            margin-bottom: 0;
        }

        .scrollable-products::-webkit-scrollbar {
            width: 8px;
        }

        .scrollable-products::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .scrollable-products::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 10px;
        }

        .scrollable-products::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }

        /* Flex Container for POS */
        .pos-container {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 40px);
            overflow: hidden;
            padding: 0 !important;
        }

        .pos-header {
            flex-shrink: 0;
            flex-grow: 0;
            overflow: visible;
            padding: 20px 20px 10px 20px;
        }

        .pos-products {
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: 0;
            max-height: 100%;
            padding: 10px 20px;
            margin-bottom: 0;
        }

        .pos-products .row {
            margin: 0;
        }

        .pos-footer {
            flex-shrink: 0;
            flex-grow: 0;
            overflow: visible;
            padding: 0 !important;
        }

        /* Input Group Modern */
        .modern-input-group {
            display: flex;
            width: 100%;
        }

        .modern-input-group-addon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 12px 16px;
            border-radius: 12px 0 0 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 48px;
        }

        .modern-input-group input {
            border-radius: 0 12px 12px 0 !important;
            border-left: none !important;
        }

        /* Select2 Modern */
        .select2-container--default .select2-selection--single {
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            height: 46px !important;
            padding: 8px 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px !important;
            color: #374151 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px !important;
        }

        .select2-dropdown {
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #667eea !important;
        }

        /* Modal Modern */
        .modal-content {
            border-radius: 20px !important;
            border: none !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: #ffffff !important;
            border-radius: 20px 20px 0 0 !important;
            border: none !important;
            padding: 20px 24px !important;
        }

        .modal-title {
            color: #ffffff !important;
            font-weight: 600 !important;
        }

        .modal-header .close {
            color: #ffffff !important;
            opacity: 0.8 !important;
            font-size: 28px !important;
        }

        .modal-header .close:hover {
            opacity: 1 !important;
        }

        .modal-body {
            padding: 24px !important;
        }

        /* Compatibility with Bootstrap */
        .form-control {
            display: block !important;
            width: 100% !important;
            padding: 12px 16px !important;
            font-size: 14px !important;
            line-height: 1.42857143 !important;
            color: #374151 !important;
            background-color: #fff !important;
            background-image: none !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 12px !important;
            transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s !important;
        }

        .form-control:focus {
            border-color: #667eea !important;
            outline: 0 !important;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1) !important;
        }

        .btn {
            border-radius: 12px !important;
            font-weight: 600 !important;
            padding: 10px 20px !important;
            transition: all 0.3s ease !important;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
            color: #ffffff !important;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4) !important;
        }

        .btn-success {
            background: linear-gradient(135deg, #0cebeb 0%, #20e3b2 100%) !important;
            border: none !important;
            color: #ffffff !important;
        }

        .btn-default {
            background: #ffffff !important;
            border: 2px solid #e5e7eb !important;
            color: #374151 !important;
        }

        .btn-default:hover {
            border-color: #667eea !important;
            color: #667eea !important;
        }

        .bg-white {
            background-color: #ffffff !important;
        }

        .btn-flat {
            border-radius: 12px !important;
        }

        /* Icon animations */
        .btn .fa, .btn .fas, .btn .far {
            transition: transform 0.3s ease;
        }

        .btn:hover .fa, .btn:hover .fas, .btn:hover .far {
            transform: scale(1.1);
        }

        /* Hide Bootstrap default margins */
        .no-print {
            padding: 20px;
        }

        .box-solid {
            border: none !important;
        }

        .box-body {
            padding: 0 !important;
        }

        /* Flatpickr custom styling */
        .flatpickr-calendar {
            border-radius: 16px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
        }

        .flatpickr-day.selected {
            background: #667eea !important;
            border-color: #667eea !important;
        }

        /* Print styles */
        @media print {
            body {
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- include module css -->
    <?php if(!empty($pos_module_data)): ?>
        <?php $__currentLoopData = $pos_module_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!empty($value['module_css_path'])): ?>
                <?php if ($__env->exists($value['module_css_path'])) echo $__env->make($value['module_css_path'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</head>
<body>
    <div class="no-print">
        <input type="hidden" id="amount_rounding_method" value="<?php echo e($pos_settings['amount_rounding_method'] ?? '', false); ?>">
        <?php if(!empty($pos_settings['allow_overselling'])): ?>
            <input type="hidden" id="is_overselling_allowed">
        <?php endif; ?>
        <?php if(session('business.enable_rp') == 1): ?>
            <input type="hidden" id="reward_point_enabled">
        <?php endif; ?>
        <?php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1 ? true : false;
            $is_rp_enabled = session('business.enable_rp') == 1 ? true : false;
        ?>

        <?php echo Form::open(['url' => action([\App\Http\Controllers\SellPosController::class, 'store']), 'method' => 'post', 'id' => 'add_pos_sell_form' ]); ?>


        <div class="row">
            <div class="<?php if(empty($pos_settings['hide_product_suggestion'])): ?> col-md-7 <?php else: ?> col-md-10 col-md-offset-1 <?php endif; ?>" style="padding-right: 10px;">
                <div class="modern-card pos-container">
                    <?php echo Form::hidden('location_id', $default_location->id ?? null , ['id' => 'location_id', 'data-receipt_printer_type' => !empty($default_location->receipt_printer_type) ? $default_location->receipt_printer_type : 'browser', 'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '']); ?>

                    <?php echo Form::hidden('sub_type', isset($sub_type) ? $sub_type : null); ?>

                    <input type="hidden" id="item_addition_method" value="<?php echo e($business_details->item_addition_method, false); ?>">

                    <!-- POS Form Header -->
                    <div class="pos-header">
                        <?php echo $__env->make('sale_pos.partials.pos_form_v2_header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>

                    <!-- POS Products Table (Scrollable) -->
                    <div class="pos-products scrollable-products">
                        <?php echo $__env->make('sale_pos.partials.pos_form_v2_table', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>

                    <!-- POS Totals Footer -->
                    <div class="pos-footer">
                        <?php echo $__env->make('sale_pos.partials.pos_form_totals', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    </div>

                    <!-- Modals -->
                    <?php echo $__env->make('sale_pos.partials.payment_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

                    <?php if(empty($pos_settings['disable_suspend'])): ?>
                        <?php echo $__env->make('sale_pos.partials.suspend_note_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php endif; ?>

                    <?php if(empty($pos_settings['disable_recurring_invoice'])): ?>
                        <?php echo $__env->make('sale_pos.partials.recurring_invoice_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(empty($pos_settings['hide_product_suggestion']) && !isMobile()): ?>
            <div class="col-md-5" style="padding-left: 10px;">
                <div class="modern-card">
                    <?php echo $__env->make('sale_pos.partials.pos_sidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php echo $__env->make('sale_pos.partials.pos_form_actions', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo Form::close(); ?>

    </div>

    <!-- This will be printed -->
    <section class="invoice print_section" id="receipt_section">
    </section>

    <!-- Modals -->
    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        <?php echo $__env->make('contact.create', ['quick_add' => true], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <?php if(empty($pos_settings['hide_product_suggestion']) && isMobile()): ?>
        <?php echo $__env->make('sale_pos.partials.mobile_product_suggestions', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>

    <div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade cash_adjustment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    <?php echo $__env->make('sale_pos.partials.configure_search_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('sale_pos.partials.recent_transactions_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('sale_pos.partials.weighing_scale_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php
        // Set default values for modal variables
        $discount_type = 'percentage';
        $sales_discount = $business_details->default_sales_discount ?? 0;
        $order_tax_id = $business_details->default_sales_tax ?? null;
        $selected_tax = $business_details->default_sales_tax ?? null;
        $shipping_details = '';
        $shipping_charges = 0;
        $rp_redeemed = 0;
        $rp_redeemed_amount = 0;
        $max_available = 0;
    ?>

    <?php echo $__env->make('sale_pos.partials.edit_discount_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('sale_pos.partials.edit_order_tax_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('sale_pos.partials.edit_shipping_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('sale_pos.partials.edit_plasticbag_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Bootstrap JS -->
    <script src="<?php echo e(asset('js/bootstrap.min.js'), false); ?>"></script>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- iCheck -->
    <script src="<?php echo e(asset('plugins/iCheck/icheck.min.js'), false); ?>"></script>

    <!-- Custom JS -->
    <?php echo $__env->make('layouts.partials.javascripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <script>
        $(document).ready(function() {
            // Initialize transaction_date with current datetime
            var transactionDateInput = document.getElementById('transaction_date');
            if (transactionDateInput) {
                // Get current datetime in proper format
                var now = new Date();
                var year = now.getFullYear();
                var month = String(now.getMonth() + 1).padStart(2, '0');
                var day = String(now.getDate()).padStart(2, '0');
                var hours = String(now.getHours()).padStart(2, '0');
                var minutes = String(now.getMinutes()).padStart(2, '0');
                var currentDateTime = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;

                // Set initial value
                transactionDateInput.value = currentDateTime;

                // Initialize Flatpickr
                var fp = flatpickr(transactionDateInput, {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    time_24hr: true,
                    defaultDate: currentDateTime,
                    onChange: function(selectedDates, dateStr, instance) {
                        console.log('POS V2: transaction_date changed to:', dateStr);
                    }
                });

                // Update transaction_date every minute
                setInterval(function() {
                    var now = new Date();
                    var year = now.getFullYear();
                    var month = String(now.getMonth() + 1).padStart(2, '0');
                    var day = String(now.getDate()).padStart(2, '0');
                    var hours = String(now.getHours()).padStart(2, '0');
                    var minutes = String(now.getMinutes()).padStart(2, '0');
                    var updatedDateTime = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;

                    // Update both the input value and flatpickr instance
                    transactionDateInput.value = updatedDateTime;
                    fp.setDate(updatedDateTime, false); // false = don't trigger onChange

                    console.log('POS V2: transaction_date auto-updated to:', updatedDateTime);
                }, 60000); // Update every 60 seconds (1 minute)

                console.log('POS V2: transaction_date initialized with Flatpickr:', currentDateTime);
            }

            // Ensure price_group has valid value
            function ensureValidPriceGroup() {
                var $priceGroup = $('#price_group');
                if ($priceGroup.length) {
                    var val = $priceGroup.val();
                    if (!val || val === '' || isNaN(parseInt(val))) {
                        var fallback = $('#hidden_price_group').val() || '0';
                        $priceGroup.val(fallback);
                        console.log('POS V2: Fixed price_group from empty/NaN to:', fallback);
                    }
                }
            }

            ensureValidPriceGroup();

            // Run before AJAX calls
            $(document).ajaxSend(function(event, jqxhr, settings) {
                if (settings.url && settings.url.includes('/pos/get_product_row')) {
                    ensureValidPriceGroup();
                }
            });

            // Focus on search input
            setTimeout(function() {
                $('#search_product').focus();
                console.log('POS V2: search_product focused');
            }, 500);

            console.log('POS V2: Modern POS initialized successfully');
        });
    </script>

    <script src="<?php echo e(asset('js/pos.js?v=' . $asset_v), false); ?>"></script>
    <script src="<?php echo e(asset('js/modal-fix.js?v=' . $asset_v), false); ?>"></script>
    <script src="<?php echo e(asset('js/printer.js?v=' . $asset_v), false); ?>"></script>
    <script src="<?php echo e(asset('js/product.js?v=' . $asset_v), false); ?>"></script>
    <script src="<?php echo e(asset('js/opening_stock.js?v=' . $asset_v), false); ?>"></script>
    <script>
        window.plastic_bag_api_url = '/plastic-bag/get-plastic-bag-types-for-pos';
    </script>
    <script src="<?php echo e(asset('js/pos_plastic_bags.js?v=' . $asset_v), false); ?>"></script>

    <?php echo $__env->make('sale_pos.partials.keyboard_shortcuts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <!-- Call restaurant module if defined -->
    <?php if(in_array('tables' ,$enabled_modules) || in_array('modifiers' ,$enabled_modules) || in_array('service_staff' ,$enabled_modules)): ?>
        <script src="<?php echo e(asset('js/restaurant.js?v=' . $asset_v), false); ?>"></script>
    <?php endif; ?>

    <!-- include module js -->
    <?php if(!empty($pos_module_data)): ?>
        <?php $__currentLoopData = $pos_module_data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!empty($value['module_js_path'])): ?>
                <?php if ($__env->exists($value['module_js_path'], ['view_data' => $value['view_data']])) echo $__env->make($value['module_js_path'], ['view_data' => $value['view_data']], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

    <script>
    $(document).ready(function() {
        // Function to format a number to 2 decimal places
        function formatNumber(value) {
            return parseFloat(value).toFixed(2);
        }

        // Event listener for both main page and modal unit price inputs
        $('body').on('input', '.main_page_unit_price, .modal_unit_price', function() {
            var inputElement = $(this);
            var changedValue = inputElement.val();
            var row = inputElement.closest('tr.product_row');
            var mainPageUnitPriceInput = row.find('input.main_page_unit_price');
            var modalUnitPriceInput = row.find('input.modal_unit_price');
            mainPageUnitPriceInput.val(changedValue);
            modalUnitPriceInput.val(changedValue);
        });

        // Event listener for when the input field loses focus
        $('body').on('focusout', '.main_page_unit_price, .modal_unit_price', function() {
            var inputElement = $(this);
            var changedValue = inputElement.val();
            inputElement.val(formatNumber(changedValue));
        });

        // Event listener for opening the modal
        $('body').on('show.bs.modal', '.row_edit_product_price_model', function () {
            var modal = $(this);
            var mainPageUnitPriceInput = modal.closest('tr.product_row').find('input.main_page_unit_price');
            var modalUnitPriceInput = modal.find('input.modal_unit_price');
            var mainPageValue = formatNumber(mainPageUnitPriceInput.val());
            modalUnitPriceInput.val(mainPageValue);
        });

        // Event listener for saving the modal
        $('body').on('click', '.modal-save-button', function() {
            var modal = $(this).closest('.modal');
            var modalUnitPriceInput = modal.find('input.modal_unit_price');
            modalUnitPriceInput.val(formatNumber(modalUnitPriceInput.val()));
        });
    });
    </script>

    <?php if(Session::get('business.name') == 'Agro Mart'): ?>
        <?php echo $__env->make('sale_pos.partials.bank_transfers_modal', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/sale_pos/createv2.blade.php ENDPATH**/ ?>