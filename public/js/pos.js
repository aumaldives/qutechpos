// pos.js loaded successfully - UPDATED VERSION 2025-10-06
// pos.js loaded at: 2025-10-06 00:20:00 - with tax data

// ============================================
// Customer Display Broadcasting
// Uses shared WebSocket connection (window.ws) from bank_transfers_modal
// Uses MutationObserver to detect DOM changes and broadcast automatically
// ============================================

var broadcastDebounceTimer = null;

function broadcastToCustomerDisplay(type, data) {
    console.log('═══════════════════════════════════════════════════');
    console.log('[Customer Display] 🚀 BROADCAST START');
    console.log('[Customer Display] Type:', type);
    console.log('[Customer Display] Data:', JSON.stringify(data, null, 2));

    if (!window.ws) {
        console.error('[Customer Display] ❌ WebSocket not initialized (window.ws is null/undefined)');
        console.log('═══════════════════════════════════════════════════');
        return;
    }

    console.log('[Customer Display] ✅ WebSocket exists');
    console.log('[Customer Display] WebSocket readyState:', window.ws.readyState, '(0=CONNECTING, 1=OPEN, 2=CLOSING, 3=CLOSED)');

    if (window.ws.readyState !== 1) {
        console.error('[Customer Display] ❌ WebSocket not ready. State:', window.ws.readyState);
        console.log('═══════════════════════════════════════════════════');
        return;
    }

    console.log('[Customer Display] ✅ WebSocket is OPEN');

    var message = { type: type, data: data };
    console.log('[Customer Display] Message object created:', message);

    console.log('[Customer Display] Checking encryptMessage function...');
    console.log('[Customer Display] window.encryptMessage exists:', typeof window.encryptMessage);

    var encryptedMessage = window.encryptMessage(message);

    if (!encryptedMessage) {
        console.error('[Customer Display] ❌ Encryption failed! encryptMessage returned:', encryptedMessage);
        console.log('═══════════════════════════════════════════════════');
        return;
    }

    console.log('[Customer Display] ✅ Encryption successful');
    console.log('[Customer Display] Encrypted message length:', encryptedMessage.length);
    console.log('[Customer Display] Encrypted message (first 50 chars):', encryptedMessage.substring(0, 50) + '...');

    var payload = JSON.stringify({
        encrypted_message: encryptedMessage,
        type: 'pos_update'
    });

    console.log('[Customer Display] Payload created, length:', payload.length);

    try {
        window.ws.send(payload);
        console.log('[Customer Display] ✅✅✅ Broadcast sent successfully:', type);
        console.log('═══════════════════════════════════════════════════');
    } catch (error) {
        console.error('[Customer Display] ❌ Send failed:', error);
        console.error('[Customer Display] Error details:', error.message, error.stack);
        console.log('═══════════════════════════════════════════════════');
    }
}

function collectCartData() {
    console.log('[Customer Display] Collecting cart data...');
    var items = [];

    // Get rows from table
    $('table#pos_table tbody tr.product_row').each(function() {
        var $row = $(this);

        // Get product name from first td (text before first <br>)
        var $firstTd = $row.find('td:first');
        var firstTdHtml = $firstTd.html();
        var productName = '';

        if (firstTdHtml) {
            // Extract text before first <br>
            var parts = firstTdHtml.split('<br>');
            productName = $('<div>').html(parts[0]).text().trim();
        }

        if (productName) {
            // Use .input_quantity to avoid modal inputs, and main_page_unit_price for price exc. tax
            var quantity = __read_number($row.find('input.input_quantity').first());
            var priceExcTax = __read_number($row.find('input.main_page_unit_price').first());
            var priceIncTax = __read_number($row.find('input.pos_unit_price_inc_tax').not('.modal_unit_price').first());
            var lineTotal = __read_number($row.find('input.pos_line_total').first());

            var item = {
                id: $row.find('input.row_variation_id').val(),
                name: productName,
                quantity: quantity,
                price_exc_tax: priceExcTax,
                price_inc_tax: priceIncTax,
                line_total: lineTotal
            };

            items.push(item);
        }
    });

    // Get totals from the totals section (read text from spans)
    var itemsCount = parseFloat($('span.total_quantity').text()) || 0;
    var subtotal = parseFloat($('span.price_total').text()) || 0;
    var discount = parseFloat($('span#total_discount').text()) || 0;
    var plasticBag = parseFloat($('span#plasticbag_charges_amount').text()) || 0;
    var orderTax = parseFloat($('span#order_tax').text()) || 0;
    var shipping = parseFloat($('span#shipping_charges_amount').text()) || 0;

    // Calculate final total
    var total = subtotal - discount + orderTax + shipping + plasticBag;

    var cartData = {
        items: items,
        items_count: itemsCount,
        subtotal: subtotal,
        discount: discount,
        order_tax: orderTax,
        shipping: shipping,
        plastic_bag: plasticBag,
        total: total
    };

    console.log('[Customer Display] Final cart data:', cartData);
    return cartData;
}

function collectPaymentData() {
    console.log('[Customer Display - Payment] Collecting payment data...');
    console.log('[Customer Display - Payment] Modal exists:', $('#modal_cash_payment').length);
    console.log('[Customer Display - Payment] Modal visible:', $('#modal_cash_payment').is(':visible'));

    // Get values from the cash modal sidebar - read text directly and parse
    var totalItemsText = $('#modal_cash_payment span.total_quantity').text().trim();
    var totalPayableText = $('#modal_cash_payment span.total_payable_span').text().trim();
    var totalPayingText = $('#modal_cash_payment span.total_paying').text().trim();
    var changeReturnText = $('#modal_cash_payment span.change_return_span').text().trim();
    var balanceText = $('#modal_cash_payment span.balance_due').text().trim();

    console.log('[Customer Display - Payment] Text values:', {
        totalItemsText: totalItemsText,
        totalPayableText: totalPayableText,
        totalPayingText: totalPayingText,
        changeReturnText: changeReturnText,
        balanceText: balanceText
    });

    console.log('[Customer Display - Payment] Elements found:', {
        total_quantity: $('#modal_cash_payment span.total_quantity').length,
        total_payable_span: $('#modal_cash_payment span.total_payable_span').length,
        total_paying: $('#modal_cash_payment span.total_paying').length,
        change_return_span: $('#modal_cash_payment span.change_return_span').length,
        balance_due: $('#modal_cash_payment span.balance_due').length
    });

    // Parse the text values (remove currency symbols and commas)
    var totalItems = parseFloat(totalItemsText.replace(/[^\d.-]/g, '')) || 0;
    var totalPayable = parseFloat(totalPayableText.replace(/[^\d.-]/g, '')) || 0;
    var totalPaying = parseFloat(totalPayingText.replace(/[^\d.-]/g, '')) || 0;
    var changeReturn = parseFloat(changeReturnText.replace(/[^\d.-]/g, '')) || 0;
    var balance = parseFloat(balanceText.replace(/[^\d.-]/g, '')) || 0;

    console.log('[Customer Display - Payment] Parsed values:', {
        totalItems: totalItems,
        totalPayable: totalPayable,
        totalPaying: totalPaying,
        changeReturn: changeReturn,
        balance: balance
    });

    var paymentData = {
        total_items: totalItems,
        total_payable: totalPayable,
        total_paying: totalPaying,
        change_return: changeReturn,
        balance: balance
    };

    console.log('[Customer Display - Payment] Final payment data:', paymentData);
    return paymentData;
}

// Debounced broadcast function - prevents too many broadcasts
function debouncedBroadcast() {
    if (broadcastDebounceTimer) {
        clearTimeout(broadcastDebounceTimer);
    }

    broadcastDebounceTimer = setTimeout(function() {
        console.log('[Customer Display] MutationObserver triggered broadcast');
        broadcastToCustomerDisplay('cart_update', collectCartData());
    }, 100);
}

// Initialize MutationObserver for POS table and totals
function initCustomerDisplayObserver() {
    // Check if elements exist
    var posTable = document.querySelector('#pos_table');
    var totalsSection = document.querySelector('.pos_form_totals');

    if (!posTable || !totalsSection) {
        console.log('[Customer Display] POS table or totals section not found, retrying...');
        setTimeout(initCustomerDisplayObserver, 1000);
        return;
    }

    console.log('[Customer Display] Setting up MutationObserver...');

    var observer = new MutationObserver(function(mutations) {
        // Debounce broadcasts to avoid sending too many messages
        debouncedBroadcast();
    });

    // Observe changes to the POS table
    observer.observe(posTable, {
        childList: true,
        subtree: true,
        attributes: true,
        characterData: true
    });

    // Observe changes to the totals section
    observer.observe(totalsSection, {
        childList: true,
        subtree: true,
        characterData: true
    });

    console.log('[Customer Display] MutationObserver active');
}

$(document).ready(function() {
    console.log('pos.js loaded and ready at:', new Date().toLocaleString());
    customer_set = false;

    // Flag to prevent circular tax calculation updates
    var updating_tax_fields = false;

    // Initialize customer display observer
    initCustomerDisplayObserver();

    //Prevent enter key function except texarea
    $('form').on('keyup keypress', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13 && e.target.tagName != 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });

    //For edit pos form
    if ($('form#edit_pos_sell_form').length > 0) {
        pos_total_row();
        pos_form_obj = $('form#edit_pos_sell_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }
    if ($('form#edit_pos_sell_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }

    $('select#select_location_id').change(function() {
        reset_pos_form();

        var default_price_group = $(this).find(':selected').data('default_price_group')
        if (default_price_group) {
            if($("#price_group option[value='" + default_price_group + "']").length > 0) {
                $("#price_group").val(default_price_group);
                $("#price_group").change();
            }
        }

        //Set default invoice scheme for location
        if ($('#invoice_scheme_id').length) {
            if($('input[name="is_direct_sale"]').length > 0){
                //default scheme for sale screen
                var invoice_scheme_id = $(this).find(':selected').data('default_sale_invoice_scheme_id');
            } else {
                var invoice_scheme_id =  $(this).find(':selected').data('default_invoice_scheme_id');
            }
            
            $("#invoice_scheme_id").val(invoice_scheme_id).change();
        }

        //Set default invoice layout for location
        if ($('#invoice_layout_id').length) {
            let invoice_layout_id = $(this).find(':selected').data('default_invoice_layout_id');
            $("#invoice_layout_id").val(invoice_layout_id).change();
        }
        
        //Set default price group
        if ($('#default_price_group').length) {
            var dpg = default_price_group ?
            default_price_group : 0;
            $('#default_price_group').val(dpg);
        }

        set_payment_type_dropdown();

        if ($('#types_of_service_id').length && $('#types_of_service_id').val()) {
            $('#types_of_service_id').change();
        }
    });

    //get customer
    $('select#customer_id').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            var template = '';
            if (data.supplier_business_name) {
                template += data.supplier_business_name + "<br>";
            }
            template += data.text + "<br>" + LANG.mobile + ": " + data.mobile;

            if (typeof(data.total_rp) != "undefined") {
                var rp = data.total_rp ? data.total_rp : 0;
                template += "<br><i class='fa fa-gift text-success'></i> " + rp;
            }

            return  template;
        },
        minimumInputLength: 1,
        language: {
            noResults: function() {
                var name = $('#customer_id')
                    .data('select2')
                    .dropdown.$search.val();
                return (
                    '<button type="button" data-name="' +
                    name +
                    '" class="btn btn-link add_new_customer"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>&nbsp; ' +
                    __translate('add_name_as_new_customer', { name: name }) +
                    '</button>'
                );
            },
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });
    $('#customer_id').on('select2:select', function(e) {
        var data = e.params.data;
        if (data.pay_term_number) {
            $('input#pay_term_number').val(data.pay_term_number);
        } else {
            $('input#pay_term_number').val('');
        }

        if (data.pay_term_type) {
            $('#add_sell_form select[name="pay_term_type"]').val(data.pay_term_type);
            $('#edit_sell_form select[name="pay_term_type"]').val(data.pay_term_type);
        } else {
            $('#add_sell_form select[name="pay_term_type"]').val('');
            $('#edit_sell_form select[name="pay_term_type"]').val('');
        }
        
        update_shipping_address(data);
        $('#advance_balance_text').text(__currency_trans_from_en(data.balance), true);
        $('#advance_balance').val(data.balance);

        if (data.price_calculation_type == 'selling_price_group') {
            $('#price_group').val(data.selling_price_group_id);
            $('#price_group').change();
        } else {
            $('#price_group').val('');
            $('#price_group').change();
        }
        if ($('.contact_due_text').length) {
            get_contact_due(data.id);
        }
    });

    set_default_customer();

    if ($('#search_product').length) {
        //Add Product
        $('#search_product')
            .autocomplete({
                delay: 300,
                source: function(request, response) {
                    var price_group = '';
                    var search_fields = [];
                    $('.search_fields:checked').each(function(i){
                      search_fields[i] = $(this).val();
                    });

                    if ($('#price_group').length > 0) {
                        price_group = $('#price_group').val();
                    }
                    $.getJSON(
                        '/products/list',
                        {
                            price_group: price_group,
                            location_id: $('input#location_id').val(),
                            term: request.term,
                            not_for_selling: 0,
                            search_fields: search_fields
                        },
                        response
                    );
                },
                minLength: 1,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];

                        var is_overselling_allowed = false;
                        if($('input#is_overselling_allowed').length) {
                            is_overselling_allowed = true;
                        }
                        var for_so = false;
                        if ($('#sale_type').length && $('#sale_type').val() == 'sales_order') {
                            for_so = true;
                        }

                        if ((ui.item.enable_stock == 1 && ui.item.qty_available > 0) || 
                                (ui.item.enable_stock == 0) || is_overselling_allowed || for_so) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        toastr.error(LANG.no_products_found);
                        $('input#search_product').select();
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    var searched_term = $(this).val();
                    var is_overselling_allowed = false;
                    if($('input#is_overselling_allowed').length) {
                        is_overselling_allowed = true;
                    }
                    var for_so = false;
                    if ($('#sale_type').length && $('#sale_type').val() == 'sales_order') {
                        for_so = true;
                    }

                    var is_draft=false;
                    if($('input#status') && ($('input#status').val()=='quotation' || 
                    $('input#status').val()=='draft')) {
                        var is_draft=true;
                    }

                    if (ui.item.enable_stock != 1 || ui.item.qty_available > 0 || is_overselling_allowed || for_so || is_draft) {
                        $(this).val(null);

                        //Pre select lot number only if the searched term is same as the lot number
                        var purchase_line_id = ui.item.purchase_line_id && searched_term == ui.item.lot_number ? ui.item.purchase_line_id : null;
                        pos_product_row(ui.item.variation_id, purchase_line_id);
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
                var is_overselling_allowed = false;
                if($('input#is_overselling_allowed').length) {
                    is_overselling_allowed = true;
                }

                var for_so = false;
                if ($('#sale_type').length && $('#sale_type').val() == 'sales_order') {
                    for_so = true;
                }
                var is_draft=false;
                if($('input#status') && ($('input#status').val()=='quotation' || 
                $('input#status').val()=='draft')) {
                    var is_draft=true;
                }

            if (item.enable_stock == 1 && item.qty_available <= 0 && !is_overselling_allowed && !for_so && !is_draft) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                var selling_price = item.selling_price;
                if (item.variation_group_price) {
                    selling_price = item.variation_group_price;
                }
                string +=
                    ' (' +
                    item.sub_sku +
                    ')' +
                    '<br> Price: ' +
                    selling_price +
                    ' (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }

                var selling_price = item.selling_price;
                if (item.variation_group_price) {
                    selling_price = item.variation_group_price;
                }

                string += ' (' + item.sub_sku + ')' + '<br> Price: ' + selling_price;
                if (item.enable_stock == 1) {
                    var qty_available = __currency_trans_from_en(item.qty_available, false, false, __currency_precision, true);
                    string += ' - ' + qty_available + item.unit;
                }
                string += '</div>';

                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }

    // Enhanced barcode scanning for main POS interface
    if ($('#search_product').length) {
        onScan.attachTo($('#search_product')[0], {
            suffixKeyCodes: [13], // Enter key
            reactToPaste: true,
            avgTimeByChar: 25, // Fast scanning recognition
            timeBeforeScanTest: 30,
            minLength: 6, // Minimum barcode length
            onScan: function(sCode, iQty) {
                console.log('POS Barcode scanned: ' + sCode);
                // Clean and validate barcode
                sCode = sCode.trim().replace(/^0+/, '');
                if (sCode.length >= 6) {
                    $('#search_product').val(sCode);
                    $('#search_product').trigger('keydown', { which: 13 }); // Trigger search
                }
            },
            onScanError: function(oDebug) {
                console.log('POS Barcode scan error:', oDebug);
            }
        });
    }

    //Update line total and check for quantity not greater than max quantity
    $('table#pos_table tbody').on('change', 'input.pos_quantity', function(event) {
        // Prevent infinite loops by checking if we're already processing this element
        var $this = $(this);
        var isFromImeiModal = event.originalEvent && event.originalEvent.imeiModal;
        
        if ($this.data('processing-change') && !isFromImeiModal) {
            return;
        }
        
        $this.data('processing-change', true);
        
        if (sell_form_validator) {
            sell_form.valid();
        }
        if (pos_form_validator) {
            pos_form_validator.element($(this));
        }
        // var max_qty = parseFloat($(this).data('rule-max'));
        var entered_qty = __read_number($(this));
        
        var tr = $(this).parents('tr');

        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
        var line_total = entered_qty * unit_price_inc_tax;

        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

        //Change modifier quantity
        tr.find('.modifier_qty_text').each( function(){
            $(this).text(__currency_trans_from_en(entered_qty, false));
        });
        tr.find('.modifiers_quantity').each( function(){
            $(this).val(entered_qty);
        });

        pos_total_row();

        adjustComboQty(tr);

        // CRITICAL: Store the actual quantity value for later use
        $this.data('actual-quantity', entered_qty);
        tr.data('actual-row-quantity', entered_qty);

        // Clear processing flag
        $this.removeData('processing-change');
    });

    //If change in unit price update price including tax and line total
    $('table#pos_table tbody').on('change', 'input.pos_unit_price', function() {
        // Skip if we're already updating tax fields to prevent circular updates
        if (updating_tax_fields) return;

        var unit_price = __read_number($(this));
        var tr = $(this).parents('tr');

        // Clear manual inc price flag since exc price changed
        tr.removeData('manual-inc-price-change');

        //calculate discounted unit price
        var discounted_unit_price = calculate_discounted_unit_price(tr);

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');

        // CRITICAL FIX: Force fresh read of quantity value at the time of price change
        var quantity_input = tr.find('input.pos_quantity');

        // First try to get stored actual quantity
        var stored_quantity = quantity_input.data('actual-quantity') || tr.data('actual-row-quantity');
        var quantity;

        if (stored_quantity) {
            quantity = parseFloat(stored_quantity);
        } else {
            // Fallback: force re-read the current value from DOM
            quantity_input.trigger('blur'); // Ensure any formatting is applied
            quantity = __read_number(quantity_input);
        }

        updating_tax_fields = true;

        // Check if there's a stored original inc_tax value
        var $inc_tax_field = tr.find('input.pos_unit_price_inc_tax');
        var stored_inc_value = $inc_tax_field.data('original-inc-value');

        var unit_price_inc_tax;
        // If the stored value would round to the same exc_tax, use it to avoid rounding errors
        if (stored_inc_value) {
            var calculated_exc_from_stored = __get_principle(stored_inc_value, tax_rate);
            calculated_exc_from_stored = Math.round(calculated_exc_from_stored * 100) / 100;

            // If the stored inc value would calculate back to this exc value, use it
            if (Math.abs(calculated_exc_from_stored - discounted_unit_price) < 0.01) {
                unit_price_inc_tax = stored_inc_value;
            } else {
                unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
                // Clear stored value since exc_tax changed significantly
                $inc_tax_field.removeData('original-inc-value');
            }
        } else {
            unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        }

        var line_total = quantity * unit_price_inc_tax;

        __write_number($inc_tax_field, unit_price_inc_tax);
        updating_tax_fields = false;
        __write_number(tr.find('input.pos_line_total'), line_total);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        pos_each_row(tr);
        pos_total_row();
        round_row_to_iraqi_dinnar(tr);
    });

    //If change in tax rate then update unit price according to it.
    $('table#pos_table tbody').on('change', 'select.tax_id', function() {
        var tr = $(this).parents('tr');

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));

        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);
        __write_number(tr.find('input.pos_unit_price'), unit_price);
        pos_each_row(tr);
    });

    //If change in unit price including tax, update unit price
    $('table#pos_table tbody').on('change', 'input.pos_unit_price_inc_tax', function() {
        // Skip if we're already updating tax fields to prevent circular updates
        if (updating_tax_fields) return;

        var unit_price_inc_tax = __read_number($(this));

        // Store the original inc_tax value to preserve it when switching between fields
        var tr = $(this).parents('tr');
        tr.find('input.pos_unit_price_inc_tax').data('original-inc-value', unit_price_inc_tax);

        if (iraqi_selling_price_adjustment) {
            unit_price_inc_tax = round_to_iraqi_dinnar(unit_price_inc_tax);
            __write_number($(this), unit_price_inc_tax);
        }

        var tr = $(this).parents('tr');

        // Mark that inc price was manually changed to preserve it
        tr.data('manual-inc-price-change', true);
        var tax_rate = tr.find('select.tax_id').find(':selected').data('rate');

        // CRITICAL: Get the actual current quantity value from the input field
        var quantity_input = tr.find('input.pos_quantity');

        // FIX: Try to get the raw value first, as __read_number might not work properly with formatted inputs
        var raw_quantity_value = quantity_input.val();
        var dom_value = quantity_input[0].value;  // Direct DOM access
        var quantity = parseFloat(raw_quantity_value.replace(/,/g, '')) || 1;

        // CRITICAL FIX: Use the stored actual quantity from the quantity change handler
        var stored_quantity = quantity_input.data('actual-quantity') || tr.data('actual-row-quantity');
        if (stored_quantity && stored_quantity !== quantity) {
            quantity = parseFloat(stored_quantity);
        }

        // Calculate line total correctly
        var line_total = quantity * unit_price_inc_tax;
        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);

        // Round discounted_unit_price to 2 decimals to avoid showing 10.185 instead of 10.19
        discounted_unit_price = Math.round(discounted_unit_price * 100) / 100;

        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);

        updating_tax_fields = true;
        // Update base unit price with proper 2 decimal rounding
        __write_number(tr.find('input.pos_unit_price'), unit_price);
        updating_tax_fields = false;
        
        // Update item tax
        __write_number(tr.find('input.item_tax'), unit_price_inc_tax - discounted_unit_price);
        
        // CRITICAL: Prevent quantity reformatting during price changes
        tr.find('input.pos_quantity').data('preserve-decimals', true);
        
        // CRITICAL: Update line total WITHOUT triggering pos_line_total change event
        // Use direct DOM manipulation to avoid triggering jQuery change events
        var line_total_input = tr.find('input.pos_line_total')[0];
        var line_total_text = tr.find('span.pos_line_total_text')[0];
        
        
        line_total_input.value = __number_f(line_total, false, false, 2);
        line_total_text.textContent = __currency_trans_from_en(line_total, true);

        pos_total_row();

        // Clear the preserve flag after a short delay
        setTimeout(function() {
            tr.find('input.pos_quantity').removeData('preserve-decimals');
        }, 100);
    });

    //Change max quantity rule if lot number changes
    $('table#pos_table tbody').on('change', 'select.lot_number', function() {
        var qty_element = $(this)
            .closest('tr')
            .find('input.pos_quantity');

        var tr = $(this).closest('tr');
        var multiplier = 1;
        var unit_name = '';
        var sub_unit_length = tr.find('select.sub_unit').length;
        if (sub_unit_length > 0) {
            var select = tr.find('select.sub_unit');
            multiplier = parseFloat(select.find(':selected').data('multiplier'));
            unit_name = select.find(':selected').data('unit_name');
        }
        var allow_overselling = qty_element.data('allow-overselling');
        if ($(this).val() && !allow_overselling) {
            var lot_qty = $('option:selected', $(this)).data('qty_available');
            var max_err_msg = $('option:selected', $(this)).data('msg-max');

            if (sub_unit_length > 0) {
                lot_qty = lot_qty / multiplier;
                var lot_qty_formated = __number_f(lot_qty, false);
                max_err_msg = __translate('lot_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', lot_qty);
            qty_element.attr('data-msg-max-value', max_err_msg);

            qty_element.rules('add', {
                'max-value': lot_qty,
                messages: {
                    'max-value': max_err_msg,
                },
            });
        } else {
            var default_qty = qty_element.data('qty_available');
            var default_err_msg = qty_element.data('msg_max_default');
            if (sub_unit_length > 0) {
                default_qty = default_qty / multiplier;
                var lot_qty_formated = __number_f(default_qty, false);
                default_err_msg = __translate('pos_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', default_qty);
            qty_element.attr('data-msg-max-value', default_err_msg);

            qty_element.rules('add', {
                'max-value': default_qty,
                messages: {
                    'max-value': default_err_msg,
                },
            });
        }
        qty_element.trigger('change');
    });

    //Change in row discount type or discount amount
    $('table#pos_table tbody').on(
        'change',
        'select.row_discount_type, input.row_discount_amount',
        function() {
            var tr = $(this).parents('tr');

            //calculate discounted unit price
            var discounted_unit_price = calculate_discounted_unit_price(tr);

            var tax_rate = tr
                .find('select.tax_id')
                .find(':selected')
                .data('rate');
            var quantity = __read_number(tr.find('input.pos_quantity'));

            var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
            var line_total = quantity * unit_price_inc_tax;

            __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
            __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
            tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
            pos_each_row(tr);
            pos_total_row();
            round_row_to_iraqi_dinnar(tr);
        }
    );


    $('#updateDiscountButton').on('click', function() {

        var totalDiscountAmount_ = $('input.discount_amount_modal_').val();
        var discount_type_ = $('select.discount_type_modal_').val();

        console.log(discount_type_);
        
        var totalQuantity = 0;
        $('table#pos_table tbody tr').each(function() {
            var quantity = parseFloat($(this).find('.pos_quantity').val().replace(',', ''));
            totalQuantity += quantity;
        });

        var remainingDiscount = totalDiscountAmount_;
        $('table#pos_table tbody tr').each(function() {

            if(discount_type_ == "fixed"){
            var quantity = parseFloat($(this).find('.pos_quantity').val().replace(',', ''));
            var rowDiscount = (quantity / totalQuantity) * totalDiscountAmount_;

            if(quantity > 1){
                var newAmount = rowDiscount / quantity;
                $(this).find('.row_discount_amount').val(newAmount.toFixed(2));
            }else{
                $(this).find('.row_discount_amount').val(rowDiscount.toFixed(2));
            }
            remainingDiscount -= rowDiscount;

        }else if(discount_type_ == "percentage"){
            var quantity = parseFloat($(this).find('.pos_quantity').val().replace(',', ''));
            //var rowDiscount = (quantity / totalQuantity) * totalDiscountAmount_;
            var rowDiscount = totalDiscountAmount_;
            $(this).find('.row_discount_amount').val(rowDiscount);
            remainingDiscount -= rowDiscount; 
        }

            $('select.row_discount_type option').removeAttr('selected');
            $('select.row_discount_type option[value="' + discount_type_ + '"]').attr('selected', 'selected');
        });

        if (remainingDiscount > 0) {
            var firstRowDiscount = parseFloat($('table#pos_table tbody tr:first').find('.row_discount_amount').val());
            $('table#pos_table tbody tr:first').find('.row_discount_amount').val((firstRowDiscount + remainingDiscount).toFixed(2));
        }

         $('table#pos_table tbody tr').each(function() {
            update_item_discount_dynamic($(this));
        });

        
        $('#total_discount').text(__currency_trans_from_en(totalDiscountAmount_, false));
        
        $('div#posEditDiscountModal').modal('hide');

    });

    function update_item_discount_dynamic(tr) {
        var discounted_unit_price = calculate_discounted_unit_price(tr);
        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));
        var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        var line_total = quantity * unit_price_inc_tax;

        __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
        __write_number(tr.find('input.pos_line_total'), line_total, false, 2);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        pos_each_row(tr);
        pos_total_row();
        round_row_to_iraqi_dinnar(tr);
    }

    //Remove row on click on remove row
    $('table#pos_table tbody').on('click', 'i.pos_remove_row', function() {
        $(this)
            .parents('tr')
            .remove();
        pos_total_row();
    });

    //Cancel the invoice
    $('button#pos-cancel').click(function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(confirm => {
            if (confirm) {
                reset_pos_form();
            }
        });
    });

    //Save invoice as draft
    $('button#pos-draft').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=draft';
        var url = pos_form_obj.attr('action');

        disable_pos_form_actions();
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                enable_pos_form_actions();
                if (result.success == 1) {
                    // Explicitly reset plastic bags before form reset
                    if (typeof reset_plastic_bag_selection === 'function') {
                        reset_plastic_bag_selection();
                    }
                    reset_pos_form();
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Save invoice as Quotation
    $('button#pos-quotation').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=quotation';
        var url = pos_form_obj.attr('action');

        disable_pos_form_actions();
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                enable_pos_form_actions();
                if (result.success == 1) {
                    // Explicitly reset plastic bags before form reset
                    if (typeof reset_plastic_bag_selection === 'function') {
                        reset_plastic_bag_selection();
                    }
                    reset_pos_form();
                    toastr.success(result.msg);

                    //Check if enabled or not
                    if (result.receipt.is_enabled) {
                        pos_print(result.receipt);
                    }
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Finalize invoice, open payment modal
    $('button#pos-finalize').click(function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        $('#modal_payment').modal('show');
    });

    $('#modal_payment').one('shown.bs.modal', function() {
        $('#modal_payment')
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
        if ($('form#edit_pos_sell_form').length == 0) {
            $(this).find('#method_0').change();
        }
    });

    //Finalize without showing payment options
    $('button.pos-express-finalize').click(function() {
        console.log('🚨 CASH EXPRESS BUTTON CLICKED - pos.js v2025.07.22.2330 🚨');

        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        var pay_method = $(this).data('pay_method');

        //If pay method is credit sale submit form
        if (pay_method == 'credit_sale') {
            $('#is_credit_sale').val(1);
            pos_form_obj.submit();
            return true;
        } else {
            if ($('#is_credit_sale').length) {
                $('#is_credit_sale').val(0);
            }
        }

        //Check for remaining balance & add it in 1st payment row
        // Ensure we have the most recent total by calling pos_total_row first
        pos_total_row();
        var total_payable = __read_number($('input#final_total_input'));
        var total_paying = __read_number($('input#total_paying_input'));
        console.log('EXPRESS CASH BUTTON CLICKED - Total Payable:', total_payable, 'Total Paying:', total_paying);

        //Change payment method.
        var payment_method_dropdown = $('#payment_rows_div')
            .find('.payment_types_dropdown')
            .first();

        payment_method_dropdown.val(pay_method);
        payment_method_dropdown.change();

        if (pay_method == 'card') {
            if (total_payable > total_paying) {
                var bal_due = total_payable - total_paying;
                var first_row = $('#payment_rows_div').find('.payment-amount').first();
                var first_row_val = __read_number(first_row);
                first_row_val = first_row_val + bal_due;
                __write_number(first_row, first_row_val);
                first_row.trigger('change');
            }
            $('div#card_details_modal').modal('show');
        } else if (pay_method == 'suspend') {
            if (total_payable > total_paying) {
                var bal_due = total_payable - total_paying;
                var first_row = $('#payment_rows_div').find('.payment-amount').first();
                var first_row_val = __read_number(first_row);
                first_row_val = first_row_val + bal_due;
                __write_number(first_row, first_row_val);
                first_row.trigger('change');
            }
            $('div#confirmSuspendModal').modal('show');
        } else if (pay_method == 'cash') {
            // Show simplified cash payment modal (don't fill regular payment row)
            // Store the amount in a data attribute to be used when modal is shown
            $('#modal_cash_payment').data('initial-amount', total_payable);
            $('#modal_cash_payment').modal('show');
        } else {
            if (total_payable > total_paying) {
                var bal_due = total_payable - total_paying;
                var first_row = $('#payment_rows_div').find('.payment-amount').first();
                var first_row_val = __read_number(first_row);
                first_row_val = first_row_val + bal_due;
                __write_number(first_row, first_row_val);
                first_row.trigger('change');
            }
            pos_form_obj.submit();
        }
    });

    $('div#card_details_modal').on('shown.bs.modal', function(e) {
        $('input#card_number').focus();
    });

    $('div#confirmSuspendModal').on('shown.bs.modal', function(e) {
        $(this)
            .find('textarea')
            .focus();
    });

    // Cash payment modal handlers - Clean implementation
    var cash_payment_submitting = false;
    var cash_modal_is_open = false;

    // When modal opens, initialize values
    $('#modal_cash_payment').on('shown.bs.modal', function(e) {
        console.log('[Customer Display - Payment] 🔵 Cash modal OPENED');
        cash_modal_is_open = true;

        // Enable cash modal payment fields
        $('#modal_cash_payment input[name="payment[0][method]"]').prop('disabled', false);
        $('#modal_cash_payment input[name="payment[0][amount]"]').prop('disabled', false);
        $('#modal_cash_payment textarea[name="payment[0][note]"]').prop('disabled', false);
        $('#modal_cash_payment input[name^="payment[0][denominations]"]').prop('disabled', false);

        var total_payable = __read_number($('#final_total_input'));
        var total_items = parseFloat($('span.total_quantity').first().text()) || 0;

        console.log('[Customer Display - Payment] Total payable:', total_payable, 'Total items:', total_items);

        // Setup MutationObserver for payment modal sidebar
        var paymentModalSidebar = document.querySelector('#modal_cash_payment .col-md-3');
        console.log('[Customer Display - Payment] MutationObserver - Sidebar found:', !!paymentModalSidebar);
        console.log('[Customer Display - Payment] MutationObserver - Already exists:', !!window.paymentModalObserver);

        if (paymentModalSidebar && !window.paymentModalObserver) {
            window.paymentModalObserver = new MutationObserver(function(mutations) {
                console.log('[Customer Display - Payment] 🔄 MutationObserver detected change');
                console.log('[Customer Display - Payment] MutationObserver - Modal is open:', cash_modal_is_open);

                // Only broadcast if modal is actually open
                if (!cash_modal_is_open) {
                    console.log('[Customer Display - Payment] ⚠️ Skipping MutationObserver broadcast - modal is not open');
                    return;
                }

                // Debounce broadcasts to avoid sending too many messages
                if (paymentUpdateDebounceTimer) {
                    clearTimeout(paymentUpdateDebounceTimer);
                }

                paymentUpdateDebounceTimer = setTimeout(function() {
                    console.log('[Customer Display - Payment] 📡 Broadcasting payment_update from MutationObserver...');
                    var paymentData = collectPaymentData();
                    broadcastToCustomerDisplay('payment_update', paymentData);
                }, 150);
            });

            window.paymentModalObserver.observe(paymentModalSidebar, {
                childList: true,
                subtree: true,
                characterData: true
            });

            console.log('[Customer Display - Payment] ✅ MutationObserver active');
        }

        // Set initial values in sidebar
        $('#modal_cash_payment .total_quantity').text(total_items);
        $('#modal_cash_payment .total_payable_span').text(__currency_trans_from_en(total_payable, true));

        // Set amount to total payable
        __write_number($('#cash_amount_0'), total_payable);

        // Calculate and update sidebar
        update_cash_modal_sidebar();

        // Wait for sidebar values to be populated, then broadcast
        // Use MutationObserver to detect when values are actually updated
        var initialBroadcastDone = false;
        var sidebarObserver = new MutationObserver(function(mutations) {
            if (!initialBroadcastDone) {
                var totalPayableText = $('#modal_cash_payment span.total_payable_span').text();
                console.log('[Customer Display - Payment] Sidebar mutation detected, total_payable_span:', totalPayableText);
                console.log('[Customer Display - Payment] Sidebar mutation - Modal is open:', cash_modal_is_open);

                // Only broadcast if modal is still open
                if (!cash_modal_is_open) {
                    console.log('[Customer Display - Payment] ⚠️ Skipping initial broadcast - modal is not open');
                    sidebarObserver.disconnect();
                    return;
                }

                // Check if values are populated (not empty or zero)
                if (totalPayableText && totalPayableText !== '0' && totalPayableText.trim() !== '') {
                    initialBroadcastDone = true;
                    sidebarObserver.disconnect();

                    setTimeout(function() {
                        // Double-check modal is still open before broadcasting
                        if (!cash_modal_is_open) {
                            console.log('[Customer Display - Payment] ⚠️ Skipping delayed broadcast - modal closed');
                            return;
                        }
                        console.log('[Customer Display - Payment] 📡 Broadcasting payment_modal_open with real values...');
                        var paymentData = collectPaymentData();
                        broadcastToCustomerDisplay('payment_modal_open', paymentData);
                        console.log('[Customer Display - Payment] ✅ Initial broadcast sent');
                    }, 100);
                }
            }
        });

        // Observe the sidebar for changes
        var sidebar = document.querySelector('#modal_cash_payment .col-md-3');
        if (sidebar) {
            sidebarObserver.observe(sidebar, {
                childList: true,
                subtree: true,
                characterData: true
            });
            console.log('[Customer Display - Payment] Initial broadcast observer active');
        }

        // Focus the amount input
        setTimeout(function() {
            $('#cash_amount_0').focus().select();
        }, 100);
    });

    // Update sidebar when user types in amount field
    $(document).on('input keyup', '#cash_amount_0', function() {
        update_cash_modal_sidebar();
    });

    // Debounce timer for payment updates
    var paymentUpdateDebounceTimer = null;

    // Function to update cash modal sidebar
    function update_cash_modal_sidebar() {
        var total_payable = __read_number($('#final_total_input'));
        var total_paying = __read_number($('#cash_amount_0'));

        if (isNaN(total_paying) || total_paying < 0) {
            total_paying = 0;
        }

        var bal_due = total_payable - total_paying;
        var change_return = 0;

        if (bal_due < 0 || Math.abs(bal_due) < 0.05) {
            change_return = Math.abs(bal_due);
            bal_due = 0;
        }

        // Update sidebar values
        $('#modal_cash_payment .total_paying').text(__currency_trans_from_en(total_paying, true));
        $('#modal_cash_payment .change_return_span').text(__currency_trans_from_en(change_return, true));
        $('#modal_cash_payment .balance_due').text(__currency_trans_from_en(bal_due, true));
        $('#modal_cash_payment #cash_in_balance_due').val(bal_due);
        $('#modal_cash_payment #cash_total_paying_input').val(total_paying);

        __highlight(bal_due * -1, $('#modal_cash_payment .balance_due'));
        __highlight(change_return * -1, $('#modal_cash_payment .change_return_span'));

        // Broadcast updates to customer display (debounced) - only if modal is open
        if (cash_modal_is_open) {
            if (paymentUpdateDebounceTimer) {
                clearTimeout(paymentUpdateDebounceTimer);
            }

            paymentUpdateDebounceTimer = setTimeout(function() {
                console.log('[Customer Display - Payment] 📡 Broadcasting payment_update from sidebar update...');
                var paymentData = collectPaymentData();
                broadcastToCustomerDisplay('payment_update', paymentData);
            }, 150);
        } else {
            console.log('[Customer Display - Payment] ⚠️ Skipping payment_update broadcast - modal is not open');
        }
    }

    // Cash denomination calculation
    $(document).on('change', '#modal_cash_payment .cash_denomination', function() {
        var denomination = parseFloat($(this).data('denomination'));
        var count = parseFloat($(this).val()) || 0;
        var subtotal = denomination * count;

        // Update subtotal
        $(this).closest('tr').find('.denomination_subtotal').text(__currency_trans_from_en(subtotal, false));

        // Calculate total of all denominations
        var total = 0;
        $('#modal_cash_payment .cash_denomination').each(function() {
            var denom = parseFloat($(this).data('denomination'));
            var cnt = parseFloat($(this).val()) || 0;
            total += denom * cnt;
        });

        // Update total
        $('#modal_cash_payment .denomination_total').text(__currency_trans_from_en(total, false));
        $('#modal_cash_payment .denomination_total_amount').val(total);

        // Update amount field and sidebar
        __write_number($('#cash_amount_0'), total);
        update_cash_modal_sidebar();
    });

    // Handle Enter key to submit
    $(document).on('keypress', '#cash_amount_0', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#pos-cash-save').click();
        }
    });

    // Handle Space key to submit (not in text fields)
    $(document).on('keydown', '#modal_cash_payment', function(e) {
        if (e.which === 32 && !$(e.target).is('textarea, input[type="text"]')) {
            e.preventDefault();
            $('#pos-cash-save').click();
        }
    });

    // Finalize cash payment
    $(document).on('click', 'button#pos-cash-save', function(e) {
        e.preventDefault();

        if (cash_payment_submitting) {
            return false;
        }

        // Validate denomination if strict check enabled
        if ($('#modal_cash_payment .is_strict').length && $('#modal_cash_payment .is_strict').val() === '1') {
            var payment_amount = __read_number($('#cash_amount_0'));
            var total_denomination = parseFloat($('#modal_cash_payment .denomination_total_amount').val());
            if (payment_amount != total_denomination) {
                $('#modal_cash_payment .cash_denomination_error').removeClass('hide');
                toastr.error($('#modal_cash_payment .cash_denomination_error').text());
                return false;
            }
        }

        cash_payment_submitting = true;

        // Copy values to main form
        var cash_amount = __read_number($('#cash_amount_0'));
        var cash_note = $('#cash_note_0').val();

        var first_row = $('#payment_rows_div').find('.payment-amount').first();
        __write_number(first_row, cash_amount);
        first_row.trigger('change');

        // Copy note
        var first_note = $('#payment_rows_div').find('textarea[name="payment[0][note]"]').first();
        if (first_note.length && cash_note) {
            first_note.val(cash_note);
        }

        // Copy denominations
        if ($('#modal_cash_payment .cash_denomination').length) {
            $('#modal_cash_payment .cash_denomination').each(function() {
                var denomination = $(this).data('denomination');
                var count = $(this).val();
                var main_denomination = $('#payment_rows_div').find('input[name="payment[0][denominations][' + denomination + ']"]');
                if (main_denomination.length) {
                    main_denomination.val(count);
                }
            });
        }

        $('#modal_cash_payment').modal('hide');

        setTimeout(function() {
            pos_form_obj.submit();
        }, 100);
    });

    // Reset flag when modal closes
    $('#modal_cash_payment').on('hidden.bs.modal', function() {
        console.log('[Customer Display - Payment] 🔴 Cash modal CLOSED');
        cash_modal_is_open = false;

        // Broadcast payment modal close to customer display
        console.log('[Customer Display - Payment] 📡 Broadcasting payment_modal_close...');
        broadcastToCustomerDisplay('payment_modal_close', {});

        // Disconnect MutationObserver
        if (window.paymentModalObserver) {
            window.paymentModalObserver.disconnect();
            window.paymentModalObserver = null;
            console.log('[Customer Display - Payment] ✅ MutationObserver disconnected');
        }

        // Disable cash modal payment fields to prevent submission with other payment methods
        $('#modal_cash_payment input[name="payment[0][method]"]').prop('disabled', true);
        $('#modal_cash_payment input[name="payment[0][amount]"]').prop('disabled', true);
        $('#modal_cash_payment textarea[name="payment[0][note]"]').prop('disabled', true);
        $('#modal_cash_payment input[name^="payment[0][denominations]"]').prop('disabled', true);

        setTimeout(function() {
            cash_payment_submitting = false;
        }, 500);
    });

    //on save card details
    $('button#pos-save-card').click(function() {
        $('input#card_number_0').val($('#card_number').val());
        $('input#card_holder_name_0').val($('#card_holder_name').val());
        $('input#card_transaction_number_0').val($('#card_transaction_number').val());
        $('select#card_type_0').val($('#card_type').val());
        $('input#card_month_0').val($('#card_month').val());
        $('input#card_year_0').val($('#card_year').val());
        $('input#card_security_0').val($('#card_security').val());

        $('div#card_details_modal').modal('hide');
        pos_form_obj.submit();
    });

    $('button#pos-suspend').click(function() {
        $('input#is_suspend').val(1);
        $('div#confirmSuspendModal').modal('hide');
        pos_form_obj.submit();
        $('input#is_suspend').val(0);
    });

    //fix select2 input issue on modal
    $('#modal_payment')
        .find('.select2')
        .each(function() {
            $(this).select2({
                dropdownParent: $('#modal_payment'),
            });
        });

    $('button#add-payment-row').click(function() {
        var row_index = $('#payment_row_index').val();
        var location_id = $('input#location_id').val();
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get_payment_row',
            data: { row_index: row_index, location_id: location_id },
            dataType: 'html',
            success: function(result) {
                if (result) {
                    var appended = $('#payment_rows_div').append(result);

                    var total_payable = __read_number($('input#final_total_input'));
                    var total_paying = __read_number($('input#total_paying_input'));
                    var b_due = total_payable - total_paying;
                    $(appended)
                        .find('input.payment-amount')
                        .focus();
                    $(appended)
                        .find('input.payment-amount')
                        .last()
                        .val(__currency_trans_from_en(b_due, false))
                        .change()
                        .select();
                    __select2($(appended).find('.select2'));
                    $(appended).find('#method_' + row_index).change();
                    $('#payment_row_index').val(parseInt(row_index) + 1);
                }
            },
        });
    });

    $(document).on('click', '.remove_payment_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('.payment_row')
                    .remove();
                calculate_balance_due();
            }
        });
    });

    pos_form_validator = pos_form_obj.validate({
        submitHandler: function(form) {
            // var total_payble = __read_number($('input#final_total_input'));
            // var total_paying = __read_number($('input#total_paying_input'));
            var cnf = true;

            //Ignore if the difference is less than 0.5
            if ($('input#in_balance_due').val() >= 0.5) {
                cnf = confirm(LANG.paid_amount_is_less_than_payable);
                // if( total_payble > total_paying ){
                // 	cnf = confirm( LANG.paid_amount_is_less_than_payable );
                // } else if(total_payble < total_paying) {
                // 	alert( LANG.paid_amount_is_more_than_payable );
                // 	cnf = false;
                // }
            }

            var total_advance_payments = 0;
            $('#payment_rows_div').find('select.payment_types_dropdown').each( function(){
                if ($(this).val() == 'advance') {
                    total_advance_payments++
                };
            });

            if (total_advance_payments > 1) {
                alert(LANG.advance_payment_cannot_be_more_than_once);
                return false;
            }

            var is_msp_valid = true;
            //Validate minimum selling price if hidden
            $('.pos_unit_price_inc_tax').each( function(){
                if (!$(this).is(":visible") && $(this).data('rule-min-value')) {
                    var val = __read_number($(this));
                    var error_msg_td = $(this).closest('tr').find('.pos_line_total_text').closest('td');
                    if (val > $(this).data('rule-min-value')) {
                        is_msp_valid = false;
                        error_msg_td.append( '<label class="error">' + $(this).data('msg-min-value') + '</label>');
                    } else {
                        error_msg_td.find('label.error').remove();
                    }
                }
            });

            if (!is_msp_valid) {
                return false;
            }

            if (cnf) {
                disable_pos_form_actions();

                var data = $(form).serialize();
                data = data + '&status=final';
                var url = $(form).attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            if (result.whatsapp_link) {
                                window.open(result.whatsapp_link);
                            }
                            $('#modal_payment').modal('hide');
                            toastr.success(result.msg);

                            // Explicitly reset plastic bags before form reset
                            if (typeof reset_plastic_bag_selection === 'function') {
                                reset_plastic_bag_selection();
                            }

                            reset_pos_form();

                            //Check if enabled or not
                            if (result.receipt.is_enabled) {
                                pos_print(result.receipt);
                            }
                        } else {
                            toastr.error(result.msg);
                        }

                        enable_pos_form_actions();
                    },
                });
            }
            return false;
        },
    });

    $(document).on('change', '.payment-amount', function() {
        calculate_balance_due();
    });

    //Update discount
    $('button#posEditDiscountModalUpdate').click(function() {

        //if discount amount is not valid return false
        if (!$("#discount_amount_modal").valid()) {
            return false;
        }
        //Close modal
        $('div#posEditDiscountModal').modal('hide');

        //Update values
        $('input#discount_type').val($('select#discount_type_modal').val());
        __write_number($('input#discount_amount'), __read_number($('input#discount_amount_modal')));

        if ($('#reward_point_enabled').length) {
            var reward_validation = isValidatRewardPoint();
            if (!reward_validation['is_valid']) {
                toastr.error(reward_validation['msg']);
                $('#rp_redeemed_modal').val(0);
                $('#rp_redeemed_modal').change();
            }
            updateRedeemedAmount();
        }

        pos_total_row();
    });

    //Shipping
    $('button#posShippingModalUpdate').click(function() {
        //Close modal
        $('div#posShippingModal').modal('hide');

        //update shipping details
        $('input#shipping_details').val($('#shipping_details_modal').val());

        $('input#shipping_address').val($('#shipping_address_modal').val());
        $('input#shipping_status').val($('#shipping_status_modal').val());
        $('input#delivered_to').val($('#delivered_to_modal').val());

        //Update shipping charges
        __write_number(
            $('input#shipping_charges'),
            __read_number($('input#shipping_charges_modal'))
        );

        //$('input#shipping_charges').val(__read_number($('input#shipping_charges_modal')));

        pos_total_row();
    });

    //Plasticbag
    $('button#posPlasticbagModalUpdate').click(function() {
        //Close modal
        $('div#posPlasticbagModal').modal('hide');

        //Update shipping charges
        __write_number(
            $('input#plasticbag_charges'),
            parseFloat((__read_number($('input#plasticbag_quantity')))*(__read_number($('input#plasticbag_per_piece'))))
        );
        $("#plasticbag_charges_amount").html(parseFloat(__read_number($('input#plasticbag_charges'))));

        pos_total_row();
    });

    $('#posShippingModal').on('shown.bs.modal', function() {
        $('#posShippingModal')
            .find('#shipping_details_modal')
            .filter(':visible:first')
            .focus()
            .select();
    });

    $(document).on('shown.bs.modal', '.row_edit_product_price_model', function() {
        $('.row_edit_product_price_model')
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
    });

    //Update Order tax
    $('button#posEditOrderTaxModalUpdate').click(function() {
        //Close modal
        $('div#posEditOrderTaxModal').modal('hide');

        var tax_obj = $('select#order_tax_modal');
        var tax_id = tax_obj.val();
        var tax_rate = tax_obj.find(':selected').data('rate');

        $('input#tax_rate_id').val(tax_id);

        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });

    $(document).on('click', '.add_new_customer', function() {
        $('#customer_id').select2('close');
        var name = $(this).data('name');
        $('.contact_modal')
            .find('input#name')
            .val(name);
        $('.contact_modal')
            .find('select#contact_type')
            .val('customer')
            .closest('div.contact_type_div')
            .addClass('hide');
        $('.contact_modal').modal('show');
    });
    $('form#quick_add_contact')
        .submit(function(e) {
            e.preventDefault();
        })
        .validate({
            rules: {
                contact_id: {
                    remote: {
                        url: '/contacts/check-contacts-id',
                        type: 'post',
                        data: {
                            contact_id: function() {
                                return $('#contact_id').val();
                            },
                            hidden_id: function() {
                                if ($('#hidden_id').length) {
                                    return $('#hidden_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
            },
            messages: {
                contact_id: {
                    remote: LANG.contact_id_already_exists,
                },
            },
            submitHandler: function(form) {
                $.ajax({
                    method: 'POST',
                    url: base_path + '/check-mobile',
                    dataType: 'json',
                    data: {
                        contact_id: function() {
                            return $('#hidden_id').val();
                        },
                        mobile_number: function() {
                            return $('#mobile').val();
                        },
                    },
                    beforeSend: function(xhr) {
                        __disable_submit_button($(form).find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.is_mobile_exists == true) {
                            swal({
                                title: LANG.sure,
                                text: result.msg,
                                icon: 'warning',
                                buttons: true,
                                dangerMode: true,
                            }).then(willContinue => {
                                if (willContinue) {
                                    submitQuickContactForm(form);
                                } else {
                                    $('#mobile').select();
                                }
                            });
                            
                        } else {
                            submitQuickContactForm(form);
                        }
                    },
                });
            },
        });
    $('.contact_modal').on('hidden.bs.modal', function() {
        $('form#quick_add_contact')
            .find('button[type="submit"]')
            .removeAttr('disabled');
        $('form#quick_add_contact')[0].reset();
    });

    //Updates for add sell
    $('select#discount_type, input#discount_amount, input#shipping_charges, \
        input#rp_redeemed_amount').change(function() {
        pos_total_row();
    });
    $('select#tax_rate_id').change(function() {
        var tax_rate = $(this)
            .find(':selected')
            .data('rate');
        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });
    //Datetime picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    //Direct sell submit
    sell_form = $('form#add_sell_form');
    if ($('form#edit_sell_form').length) {
        sell_form = $('form#edit_sell_form');
        pos_total_row();
    }
    sell_form_validator = sell_form.validate();

    $('button#submit-sell, button#save-and-print').click(function(e) {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_msp_valid = true;
        //Validate minimum selling price if hidden
        $('.pos_unit_price_inc_tax').each( function(){
            if (!$(this).is(":visible") && $(this).data('rule-min-value')) {
                var val = __read_number($(this));
                var error_msg_td = $(this).closest('tr').find('.pos_line_total_text').closest('td');
                if (val > $(this).data('rule-min-value')) {
                    is_msp_valid = false;
                    error_msg_td.append( '<label class="error">' + $(this).data('msg-min-value') + '</label>');
                } else {
                    error_msg_td.find('label.error').remove();
                }
            }
        });

        if (!is_msp_valid) {
            return false;
        }

        if ($(this).attr('id') == 'save-and-print') {
            $('#is_save_and_print').val(1);           
        } else {
            $('#is_save_and_print').val(0);
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        if ($('.enable_cash_denomination_for_payment_methods').length) {
            var payment_row = $('.enable_cash_denomination_for_payment_methods').closest('.payment_row');
            var is_valid = true;
            var payment_type = payment_row.find('.payment_types_dropdown').val();
            var denomination_for_payment_types = JSON.parse($('.enable_cash_denomination_for_payment_methods').val());
            if (denomination_for_payment_types.includes(payment_type) && payment_row.find('.is_strict').length && payment_row.find('.is_strict').val() === '1' ) {
                var payment_amount = __read_number(payment_row.find('.payment-amount'));
                var total_denomination = payment_row.find('input.denomination_total_amount').val();
                if (payment_amount != total_denomination ) {
                    is_valid = false;
                }
            }

            if (!is_valid) {
                payment_row.find('.cash_denomination_error').removeClass('hide');
                toastr.error(payment_row.find('.cash_denomination_error').text());
                e.preventDefault();
                return false;
            } else {
                payment_row.find('.cash_denomination_error').addClass('hide');
            }
        }

        if (sell_form.valid()) {
            // Broadcast sale complete to customer display
            var cartData = collectCartData();
            broadcastToCustomerDisplay('sale_complete', { total: cartData.total });

            window.onbeforeunload = null;
            $(this).attr('disabled', true);
            sell_form.submit();
        }
    });

    //REPAIR MODULE:check if repair module field is present send data to filter product
    var is_enabled_stock = null;
    if ($("#is_enabled_stock").length) {
        is_enabled_stock = $("#is_enabled_stock").val();
    }

    var device_model_id = null;
    if ($("#repair_model_id").length) {
        device_model_id = $("#repair_model_id").val();
    }

    //Show product list.
    get_product_suggestion_list(
        $('select#product_category').val(),
        $('select#product_brand').val(),
        $('input#location_id').val(),
        null,
        is_enabled_stock,
        device_model_id
    );
    $('select#product_category, select#product_brand, select#select_location_id').on('change', function(e) {
        $('input#suggestion_page').val(1);
        var location_id = $('input#location_id').val();
        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                $('select#product_category').val(),
                $('select#product_brand').val(),
                $('input#location_id').val(),
                null
            );
        }

        get_featured_products();
    });

    $(document).on('click', 'div.product_box', function() {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row($(this).data('variation_id'));
        }
    });

    $(document).on('shown.bs.modal', '.row_description_modal', function() {
        $(this)
            .find('textarea')
            .first()
            .focus();
    });

    //Press enter on search product to jump into last quantty and vice-versa
    $('#search_product').keydown(function(e) {
        var key = e.which;
        if (key == 9) {
            // the tab key code
            e.preventDefault();
            if ($('#pos_table tbody tr').length > 0) {
                $('#pos_table tbody tr:last')
                    .find('input.pos_quantity')
                    .focus()
                    .select();
            }
        }
    });
    $('#pos_table').on('keypress', 'input.pos_quantity', function(e) {
        var key = e.which;
        if (key == 13) {
            // the enter key code
            $('#search_product').focus();
        }
    });

    $('#exchange_rate').change(function() {
        var curr_exchange_rate = 1;
        if ($(this).val()) {
            curr_exchange_rate = __read_number($(this));
        }
        var total_payable = __read_number($('input#final_total_input'));
        var shown_total = total_payable * curr_exchange_rate;
        $('span#total_payable').text(__currency_trans_from_en(shown_total, false));
    });

    $('select#price_group').change(function() {
        $('input#hidden_price_group').val($(this).val());
    });

    //Quick add product
    $(document).on('click', 'button.pos_add_quick_product', function() {
        var url = $(this).data('href');
        var container = $(this).data('container');
        $.ajax({
            url: url + '?product_for=pos',
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                $('.os_exp_date').datepicker({
                    autoclose: true,
                    format: 'dd-mm-yyyy',
                    clearBtn: true,
                });
            },
        });
    });

    $(document).on('change', 'form#quick_add_product_form input#single_dpp', function() {
        var unit_price = __read_number($(this));
        $('table#quick_product_opening_stock_table tbody tr').each(function() {
            var input = $(this).find('input.unit_price');
            __write_number(input, unit_price);
            input.change();
        });
    });

    $(document).on('quickProductAdded', function(e) {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
        } else {
            pos_product_row(e.variation.id);
        }
    });

    $('div.view_modal').on('show.bs.modal', function() {
        __currency_convert_recursively($(this));
    });

    $('table#pos_table').on('change', 'select.sub_unit', function() {
        console.log("Iam the one");
        var tr = $(this).closest('tr');
        var base_unit_selling_price = tr.find('input.hidden_base_unit_sell_price').val();

        var selected_option = $(this).find(':selected');

        var multiplier = parseFloat(selected_option.data('multiplier'));

        var allow_decimal = parseInt(selected_option.data('allow_decimal'));

        tr.find('input.base_unit_multiplier').val(multiplier);

        var custom_unit = selected_option.data('custom_unit');

        var custom_unit_price = selected_option.data('custom_unit_price');
        var unit_sp = 0;
        
        if(custom_unit == "1"){
            unit_sp = base_unit_selling_price * custom_unit_price;
        }else{
            unit_sp = base_unit_selling_price * multiplier;
        }

        var sp_element = tr.find('input.pos_unit_price');
        __write_number(sp_element, unit_sp);

        sp_element.change();

        var qty_element = tr.find('input.pos_quantity');
        var base_max_avlbl = qty_element.data('qty_available');
        var error_msg_line = 'pos_max_qty_error';

        if (tr.find('select.lot_number').length > 0) {
            var lot_select = tr.find('select.lot_number');
            if (lot_select.val()) {
                base_max_avlbl = lot_select.find(':selected').data('qty_available');
                error_msg_line = 'lot_max_qty_error';
            }
        }

        qty_element.attr('data-decimal', allow_decimal);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        qty_element.rules('add', {
            abs_digit: abs_digit,
        });

        if (base_max_avlbl) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            qty_element.rules('add', {
                'max-value': max_avlbl,
                messages: {
                    'max-value': max_err_msg,
                },
            });
            qty_element.trigger('change');
        }
        adjustComboQty(tr);
    });

    //Confirmation before page load.
    window.onbeforeunload = function() {
        if($('form#edit_pos_sell_form').length == 0){
            if($('table#pos_table tbody tr').length > 0) {
                return LANG.sure;
            } else {
                return null;
            }
        }
    }
    /* $(window).resize(function() {
        console.log("TEST");
        var win_height = $(window).height();
        div_height = __calculate_amount('percentage', 63, win_height);
        $('div.pos_product_div').css('min-height', div_height + 'px');
        $('div.pos_product_div').css('max-height', div_height + 'px');
    }); */

    /* $(window).resize(function() {
        var win_height = $(window).height();
        div_height = __calculate_amount('percentage', 63, win_height);
        $('#product_list_body').css('min-height', div_height + 'px');
        $('#product_list_body').css('max-height', div_height + 'px');
    }); */

    //Used for weighing scale barcode
    $('#weighing_scale_modal').on('shown.bs.modal', function (e) {

        //Attach the scan event
        onScan.attachTo(document, {
            suffixKeyCodes: [13], // enter-key expected at the end of a scan
            reactToPaste: true, // Compatibility to built-in scanners in paste-mode (as opposed to keyboard-mode)
            avgTimeByChar: 30, // Faster character timing for quicker recognition
            timeBeforeScanTest: 40, // Reduce delay before scan test
            minLength: 3, // Minimum barcode length
            onScan: function(sCode, iQty) {
                console.log('Scanned: ' + iQty + 'x ' + sCode); 
                // Clean barcode input - remove common prefixes/suffixes
                sCode = sCode.trim().replace(/^0+/, '');
                if (sCode.length >= 6) { // Valid barcode length check
                    $('input#weighing_scale_barcode').val(sCode);
                    $('button#weighing_scale_submit').trigger('click');
                }
            },
            onScanError: function(oDebug) {
                console.log('Barcode scan error:', oDebug); 
            },
            minLength: 3
            // onKeyDetect: function(iKeyCode){ // output all potentially relevant key events - great for debugging!
            //     console.log('Pressed: ' + iKeyCode);
            // }
        });

        $('input#weighing_scale_barcode').focus();
    });

    $('#weighing_scale_modal').on('hide.bs.modal', function (e) {
        //Detach from the document once modal is closed.
        onScan.detachFrom(document);
    });

    $('button#weighing_scale_submit').click(function(){

        var price_group = '';
        if ($('#price_group').length > 0) {
            price_group = $('#price_group').val();
        }

        if($('#weighing_scale_barcode').val().length > 0){
            pos_product_row(null, null, $('#weighing_scale_barcode').val());
            $('#weighing_scale_modal').modal('hide');
            $('input#weighing_scale_barcode').val('');
        } else{
            $('input#weighing_scale_barcode').focus();
        }
    });

    $('#show_featured_products').click( function(){
        if (!$('#featured_products_box').is(':visible')) {
            $('#featured_products_box').fadeIn();
        } else {
            $('#featured_products_box').fadeOut();
        }
    });
    validate_discount_field();
    set_payment_type_dropdown();
    if ($('#__is_mobile').length) {
        $('.pos_form_totals').css('margin-bottom', $('.pos-form-actions').height() - 30);
    }

    setInterval(function () {
        if ($('span.curr_datetime').length) {
            $('span.curr_datetime').html(__current_datetime());
        }
    }, 60000);

    set_search_fields();
});

function set_payment_type_dropdown() {
    var payment_settings = $('#location_id').data('default_payment_accounts');
    payment_settings = payment_settings ? payment_settings : [];
    enabled_payment_types = [];
    for (var key in payment_settings) {
        if (payment_settings[key] && payment_settings[key]['is_enabled']) {
            enabled_payment_types.push(key);
        }
    }
    if (enabled_payment_types.length) {
        $(".payment_types_dropdown > option").each(function() {
            //skip if advance
            if ($(this).val() && $(this).val() != 'advance') {
                if (enabled_payment_types.indexOf($(this).val()) != -1) {
                    $(this).removeClass('hide');
                } else {
                    $(this).addClass('hide');
                }
            }
        });
    }
}

function get_featured_products() {
    var location_id = $('#location_id').val();
    if (location_id && $('#featured_products_box').length > 0) {
        $.ajax({
            method: 'GET',
            url: '/sells/pos/get-featured-products/' + location_id,
            dataType: 'html',
            success: function(result) {
                if (result) {
                    $('#feature_product_div').removeClass('hide');
                    $('#featured_products_box').html(result);
                } else {
                    $('#feature_product_div').addClass('hide');
                    $('#featured_products_box').html('');
                }
            },
        });
    } else {
        $('#feature_product_div').addClass('hide');
        $('#featured_products_box').html('');
    }
}

function get_product_suggestion_list(category_id, brand_id, location_id, url = null, is_enabled_stock = null, repair_model_id = null) {
    if($('div#product_list_body').length == 0) {
        return false;
    }

    if (url == null) {
        url = '/sells/pos/get-product-suggestion';
    }
    $('#suggestion_page_loader').fadeIn(700);
    var page = $('input#suggestion_page').val();
    if (page == 1) {
        $('div#product_list_body').html('');
    }
    if ($('div#product_list_body').find('input#no_products_found').length > 0) {
        $('#suggestion_page_loader').fadeOut(700);
        return false;
    }
    $.ajax({
        method: 'GET',
        url: url,
        data: {
            category_id: category_id,
            brand_id: brand_id,
            location_id: location_id,
            page: page,
            is_enabled_stock: is_enabled_stock,
            repair_model_id: repair_model_id
        },
        dataType: 'html',
        success: function(result) {
            $('div#product_list_body').append(result);
            $('#suggestion_page_loader').fadeOut(700);
        },
    });
}

//Get recent transactions
function get_recent_transactions(status, element_obj) {
    if (element_obj.length == 0) {
        return false;
    }
    var transaction_sub_type = $("#transaction_sub_type").val();
    $.ajax({
        method: 'GET',
        url: '/sells/pos/get-recent-transactions',
        data: { status: status , transaction_sub_type: transaction_sub_type},
        dataType: 'html',
        success: function(result) {
            element_obj.html(result);
            __currency_convert_recursively(element_obj);
        },
    });
}

//variation_id is null when weighing_scale_barcode is used.
function pos_product_row(variation_id = null, purchase_line_id = null, weighing_scale_barcode = null, quantity = 1) {

    //Get item addition method
    var item_addtn_method = 0;
    var add_via_ajax = true;

    if (variation_id != null && $('#item_addition_method').length) {
        item_addtn_method = $('#item_addition_method').val();
    }

    if (item_addtn_method == 0) {
        add_via_ajax = true;
    } else {
        var is_added = false;

        //Search for variation id in each row of pos table
        $('#pos_table tbody')
            .find('tr')
            .each(function() {
                var row_v_id = $(this)
                    .find('.row_variation_id')
                    .val();
                var enable_sr_no = $(this)
                    .find('.enable_sr_no')
                    .val();
                var modifiers_exist = false;
                if ($(this).find('input.modifiers_exist').length > 0) {
                    modifiers_exist = true;
                }

                if (
                    row_v_id == variation_id &&
                    enable_sr_no !== '1' &&
                    !modifiers_exist &&
                    !is_added
                ) {
                    add_via_ajax = false;
                    is_added = true;

                    //Increment product quantity
                    qty_element = $(this).find('.pos_quantity');
                    var qty = __read_number(qty_element);
                    __write_number(qty_element, qty + 1);
                    qty_element.change();

                    round_row_to_iraqi_dinnar($(this));

                    $('input#search_product')
                        .focus()
                        .select();
                }
        });
    }

    if (add_via_ajax) {
        var product_row = $('input#product_row_count').val();
        var location_id = $('input#location_id').val();
        var customer_id = $('select#customer_id').val();
        var is_direct_sell = false;
        if (
            $('input[name="is_direct_sale"]').length > 0 &&
            $('input[name="is_direct_sale"]').val() == 1
        ) {
            is_direct_sell = true;
        }

        var disable_qty_alert = false;

        if ($('#disable_qty_alert').length) {
            disable_qty_alert = true;
        }

        var is_sales_order = $('#sale_type').length && $('#sale_type').val() == 'sales_order' ? true : false;

        var price_group = '';
        if ($('#price_group').length > 0) {
            price_group = parseInt($('#price_group').val());
        }

        //If default price group present
        if ($('#default_price_group').length > 0 && 
            price_group === '') {
            price_group = $('#default_price_group').val();
        }

        //If types of service selected give more priority
        if ($('#types_of_service_price_group').length > 0 && 
            $('#types_of_service_price_group').val()) {
            price_group = $('#types_of_service_price_group').val();
        }

        var is_draft=false;
        if($('input#status') && ($('input#status').val()=='quotation' || 
        $('input#status').val()=='draft')) {
            is_draft=true;
        }
        
        $.ajax({
            method: 'GET',
            url: '/sells/pos/get_product_row/' + variation_id + '/' + location_id,
            async: false,
            data: {
                product_row: product_row,
                customer_id: customer_id,
                is_direct_sell: is_direct_sell,
                price_group: price_group,
                purchase_line_id: purchase_line_id,
                weighing_scale_barcode: weighing_scale_barcode,
                quantity: quantity,
                is_sales_order: is_sales_order,
                disable_qty_alert: disable_qty_alert,
                is_draft: is_draft
            },
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    $('table#pos_table tbody')
                        .append(result.html_content)
                        .find('input.pos_quantity');
                    //increment row count
                    $('input#product_row_count').val(parseInt(product_row) + 1);
                    var this_row = $('table#pos_table tbody')
                        .find('tr')
                        .last();
                    pos_each_row(this_row);

                    //For initial discount if present
                    var line_total = __read_number(this_row.find('input.pos_line_total'));
                    this_row.find('span.pos_line_total_text').text(line_total);

                    pos_total_row();

                    //Check if multipler is present then multiply it when a new row is added.
                    if(__getUnitMultiplier(this_row) > 1){
                        this_row.find('select.sub_unit').trigger('change');
                    }

                    // Modal opening is handled by Bootstrap data-toggle on product name click
                    // Removed duplicate modal.show() call that was causing flashing behavior
                    // if (result.enable_sr_no == '1') {
                    //     var new_row = $('table#pos_table tbody')
                    //         .find('tr')
                    //         .last();
                    //     new_row.find('.row_edit_product_price_model').modal('show');
                    // }

                    round_row_to_iraqi_dinnar(this_row);
                    __currency_convert_recursively(this_row);

                    $('input#search_product')
                        .focus()
                        .select();

                    //Used in restaurant module
                    if (result.html_modifier) {
                        $('table#pos_table tbody')
                            .find('tr')
                            .last()
                            .find('td:first')
                            .append(result.html_modifier);
                    }

                    //scroll bottom of items list
                    $(".pos_product_div").animate({ scrollTop: $('.pos_product_div').prop("scrollHeight")}, 1000);
                } else {
                    toastr.error(result.msg);
                    $('input#search_product')
                        .focus()
                        .select();
                }
            },
        });
    }
}

//Update values for each row
function pos_each_row(row_obj) {
    var unit_price = __read_number(row_obj.find('input.pos_unit_price'));

    //console.log("TEST2");

    var discounted_unit_price = calculate_discounted_unit_price(row_obj);
    var tax_rate = row_obj
        .find('select.tax_id')
        .find(':selected')
        .data('rate');

    // Check if this is from a manual inc price change OR if row was just added - if so, don't overwrite it
    // Check sell_price_tax setting (includes vs excludes)
    var sell_price_tax = $('#sell_price_tax').val();

    // Get the current inc_tax value from input
    var current_inc_tax = __read_number(row_obj.find('input.pos_unit_price_inc_tax'));

    // Only recalculate if:
    // 1. Not manually changed
    // 2. sell_price_tax = 'excludes' (exclusive) OR current value matches what would be calculated
    var should_recalculate = !row_obj.data('manual-inc-price-change');

    // If sell_price_tax is 'includes' (inclusive pricing), preserve the loaded inc_tax value
    if (sell_price_tax === 'includes' && current_inc_tax > 0) {
        // For inclusive pricing, the inc_tax from database is the master value
        // Calculate what exc_tax should be instead
        var calculated_from_current = discounted_unit_price * (1 + tax_rate / 100);
        var diff = Math.abs(current_inc_tax - calculated_from_current);

        // Only recalculate if the difference is significant (user changed exc_tax)
        if (diff > 0.02) {
            // User changed exc_tax, so recalculate inc_tax
            var unit_price_inc_tax = discounted_unit_price + __calculate_amount('percentage', tax_rate, discounted_unit_price);
            __write_number(row_obj.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
        } else {
            // Preserve the database inc_tax value
            var unit_price_inc_tax = current_inc_tax;
        }
    } else if (!should_recalculate) {
        // Manual change - use existing value
        var unit_price_inc_tax = current_inc_tax;
    } else {
        // Exclusive pricing or recalculation needed - calculate from exc_tax
        var unit_price_inc_tax = discounted_unit_price + __calculate_amount('percentage', tax_rate, discounted_unit_price);
        __write_number(row_obj.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
    }

    var discount = __read_number(row_obj.find('input.row_discount_amount'));

    if (discount > 0) {
        var qty = __read_number(row_obj.find('input.pos_quantity'));
        var line_total = qty * unit_price_inc_tax;
        __write_number(row_obj.find('input.pos_line_total'), line_total);
    }

    //var unit_price_inc_tax = __read_number(row_obj.find('input.pos_unit_price_inc_tax'));

    __write_number(row_obj.find('input.item_tax'), unit_price_inc_tax - discounted_unit_price);
    
}

function pos_total_row() {
    var total_quantity = 0;
    var price_total = get_subtotal();
    $('table#pos_table tbody tr').each(function() {
        total_quantity = total_quantity + __read_number($(this).find('input.pos_quantity'));
    });

    //updating shipping charges
    $('span#shipping_charges_amount').text(
        __currency_trans_from_en(__read_number($('input#shipping_charges_modal')), false)
    );

    $('span.total_quantity').each(function() {
        $(this).html(__number_f(total_quantity));
    });

    //$('span.unit_price_total').html(unit_price_total);
    $('span.price_total').html(__currency_trans_from_en(price_total, false));
    calculate_billing_details(price_total);
}

function get_subtotal() {
    var price_total = 0;

    $('table#pos_table tbody tr').each(function() {
        price_total = price_total + __read_number($(this).find('input.pos_line_total'));
    });

    //Go through the modifier prices.
    $('input.modifiers_price').each(function() {
        var modifier_price = __read_number($(this));
        var modifier_quantity = $(this).closest('.product_modifier').find('.modifiers_quantity').val();
        var modifier_subtotal = modifier_price * modifier_quantity;
        price_total = price_total + modifier_subtotal;
    });

    return price_total;
}

function calculate_billing_details(price_total) {
    var discount = pos_discount(price_total);
    if ($('#reward_point_enabled').length) {
        total_customer_reward = $('#rp_redeemed_amount').val();
        discount = parseFloat(discount) + parseFloat(total_customer_reward);

        if ($('input[name="is_direct_sale"]').length <= 0) {
            $('span#total_discount').text(__currency_trans_from_en(discount, false));
        }
    }

    var order_tax = pos_order_tax(price_total, discount);

    //Add shipping charges.
    var shipping_charges = __read_number($('input#shipping_charges'));

    var additional_expense = 0;
    //calculate additional expenses
    if ($('input#additional_expense_value_1').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_1'));
    }
    if ($('input#additional_expense_value_2').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_2'))
    }
    if ($('input#additional_expense_value_3').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_3'))
    }
    if ($('input#additional_expense_value_4').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_4'))
    }

    //Add packaging charge
    var packing_charge = 0;
    if ($('#types_of_service_id').length > 0 && 
            $('#types_of_service_id').val()) {
        packing_charge = __calculate_amount($('#packing_charge_type').val(), 
            __read_number($('input#packing_charge')), price_total);

        $('#packing_charge_text').text(__currency_trans_from_en(packing_charge, false));
    }

    var plasticbag_charge = 0;
    // Check if we have the new plastic bag management system (with plasticbag_charges input)
    if ($('input#plasticbag_charges').length > 0) {
        // Use the new plastic bag system - get charges directly from the input
        plasticbag_charge = __read_number($('input#plasticbag_charges'));
        $("#plasticbag_charges_amount").html(__currency_trans_from_en(plasticbag_charge, false));
    } else if(__read_number($('input#plasticbag_quantity')) > 0) {
        // Fall back to old system if new system not present
        plasticbag_charge = __read_number($('input#plasticbag_quantity'))*__read_number($('input#plasticbag_per_piece'));
        __write_number($('input#plasticbag_charges'), plasticbag_charge);
        $("#plasticbag_charges_amount").html(parseFloat(plasticbag_charge));
    }

    var total_payable = price_total + order_tax - discount + shipping_charges + packing_charge + additional_expense + plasticbag_charge;

    var rounding_multiple = $('#amount_rounding_method').val() ? parseFloat($('#amount_rounding_method').val()) : 0;
    var round_off_data = __round(total_payable, rounding_multiple);
    var total_payable_rounded = round_off_data.number;

    var round_off_amount = round_off_data.diff;
    if (round_off_amount != 0) {
        $('span#round_off_text').text(__currency_trans_from_en(round_off_amount, false));
    } else {
        $('span#round_off_text').text(0);
    }
    $('input#round_off_amount').val(round_off_amount);

    __write_number($('input#final_total_input'), total_payable_rounded);
    var curr_exchange_rate = 1;
    if ($('#exchange_rate').length > 0 && $('#exchange_rate').val()) {
        curr_exchange_rate = __read_number($('#exchange_rate'));
    }
    var shown_total = total_payable_rounded * curr_exchange_rate;
    $('span#total_payable').text(__currency_trans_from_en(shown_total, false));

    $('span.total_payable_span').text(__currency_trans_from_en(total_payable_rounded, true));

    //Check if edit form then don't update price.
    if ($('form#edit_pos_sell_form').length == 0 && $('form#edit_sell_form').length == 0) {
        __write_number($('.payment-amount').first(), total_payable_rounded);
    }

    $(document).trigger('invoice_total_calculated');

    calculate_balance_due();
}

function pos_discount(total_amount) {
    var calculation_type = $('#discount_type').val();
    var calculation_amount = __read_number($('#discount_amount'));

    var discount = __calculate_amount(calculation_type, calculation_amount, total_amount);

    $('span#total_discount').text(__currency_trans_from_en(discount, false));

    return discount;
}

function pos_order_tax(price_total, discount) {
    var tax_rate_id = $('#tax_rate_id').val();
    var calculation_type = 'percentage';
    var calculation_amount = __read_number($('#tax_calculation_amount'));
    var total_amount = price_total - discount;

    if (tax_rate_id) {
        var order_tax = __calculate_amount(calculation_type, calculation_amount, total_amount);
    } else {
        var order_tax = 0;
    }

    $('span#order_tax').text(__currency_trans_from_en(order_tax, false));

    return order_tax;
}

function calculate_balance_due() {
    var total_payable = __read_number($('#final_total_input'));
    var total_paying = 0;
    $('#payment_rows_div')
        .find('.payment-amount')
        .each(function() {
            if (parseFloat($(this).val())) {
                total_paying += __read_number($(this));
            }
        });
    var bal_due = total_payable - total_paying;
    var change_return = 0;

    //change_return
    if (bal_due < 0 || Math.abs(bal_due) < 0.05) {
        __write_number($('input#change_return'), bal_due * -1);
        $('span.change_return_span').text(__currency_trans_from_en(bal_due * -1, true));
        change_return = bal_due * -1;
        bal_due = 0;
    } else {
        __write_number($('input#change_return'), 0);
        $('span.change_return_span').text(__currency_trans_from_en(0, true));
        change_return = 0;
        
    }

    if (change_return !== 0) {
        $('#change_return_payment_data').removeClass('hide');
    } else {
        $('#change_return_payment_data').addClass('hide');
    }

    __write_number($('input#total_paying_input'), total_paying);
    $('span.total_paying').text(__currency_trans_from_en(total_paying, true));

    __write_number($('input#in_balance_due'), bal_due);
    $('span.balance_due').text(__currency_trans_from_en(bal_due, true));

    __highlight(bal_due * -1, $('span.balance_due'));
    __highlight(change_return * -1, $('span.change_return_span'));
}

function isValidPosForm() {
    flag = true;
    $('span.error').remove();

    if ($('select#customer_id').val() == null) {
        flag = false;
        error = '<span class="error">' + LANG.required + '</span>';
        $(error).insertAfter($('select#customer_id').parent('div'));
    }

    if ($('tr.product_row').length == 0) {
        flag = false;
        error = '<span class="error">' + LANG.no_products + '</span>';
        $(error).insertAfter($('input#search_product').parent('div'));
    }

    return flag;
}

function reset_pos_form(){

	//If on edit page then redirect to Add POS page
	if($('form#edit_pos_sell_form').length > 0){
		setTimeout(function() {
			window.location = $("input#pos_redirect_url").val();
		}, 4000);
		return true;
	}
	
    //reset all repair defects tags
    if ($("#repair_defects").length > 0) {
        tagify_repair_defects.removeAllTags();
    }

	if(pos_form_obj[0]){
		pos_form_obj[0].reset();
	}
	if(sell_form[0]){
		sell_form[0].reset();
	}
	set_default_customer();
	set_location();

	$('tr.product_row').remove();
	$('span.total_quantity, span.price_total, span#total_discount, span#order_tax, span#total_payable, span#shipping_charges_amount').text(0);
	$('span.total_payable_span', 'span.total_paying', 'span.balance_due').text(0);

	$('#modal_payment').find('.remove_payment_row').each( function(){
		$(this).closest('.payment_row').remove();
	});

    if ($('#is_credit_sale').length) {
        $('#is_credit_sale').val(0);
    }

	//Reset discount
	__write_number($('input#discount_amount'), $('input#discount_amount').data('default'));
	$('input#discount_type').val($('input#discount_type').data('default'));

	//Reset tax rate
	$('input#tax_rate_id').val($('input#tax_rate_id').data('default'));
	__write_number($('input#tax_calculation_amount'), $('input#tax_calculation_amount').data('default'));

	$('select.payment_types_dropdown').val('cash').trigger('change');
	$('#price_group').trigger('change');

	//Reset shipping
	__write_number($('input#shipping_charges'), $('input#shipping_charges').data('default'));
	$('input#shipping_details').val($('input#shipping_details').data('default'));
    $('input#shipping_address, input#shipping_status, input#delivered_to').val('');
    
    //Reset plastic bags
    if (typeof reset_plastic_bag_selection === 'function') {
        reset_plastic_bag_selection();
    }
	if($('input#is_recurring').length > 0){
		$('input#is_recurring').iCheck('update');
	};
    if($('#invoice_layout_id').length > 0){
        $('#invoice_layout_id').trigger('change');
    };
    $('span#round_off_text').text(0);

    //repair module extra  fields reset
    if ($('#repair_device_id').length > 0) {
        $('#repair_device_id').val('').trigger('change');
    }

    //Status is hidden in sales order
    if ($('#status').length > 0 && $('#status').is(":visible")) {
        $('#status').val('').trigger('change');
    }
    if ($('#transaction_date').length > 0) {
        $('#transaction_date').data("DateTimePicker").date(moment());
    }
    if ($('.paid_on').length > 0) {
        $('.paid_on').data("DateTimePicker").date(moment());
    }
    if ($('#commission_agent').length > 0) {
        $('#commission_agent').val('').trigger('change');
    } 

    //reset contact due
    $('.contact_due_text').find('span').text('');
    $('.contact_due_text').addClass('hide');

    $(document).trigger('sell_form_reset');
}

function set_default_customer() {
    var default_customer_id = $('#default_customer_id').val();
    var default_customer_name = $('#default_customer_name').val();
    var default_customer_balance = $('#default_customer_balance').val();
    var default_customer_address = $('#default_customer_address').val();
    var exists = default_customer_id ? $('select#customer_id option[value=' + default_customer_id + ']').length : 0;
    if (exists == 0 && default_customer_id) {
        $('select#customer_id').append(
            $('<option>', { value: default_customer_id, text: default_customer_name })
        );
    }
    $('#advance_balance_text').text(__currency_trans_from_en(default_customer_balance), true);
    $('#advance_balance').val(default_customer_balance);
    $('#shipping_address_modal').val(default_customer_address);
    if (default_customer_address) {
        $('#shipping_address').val(default_customer_address);
    }
    $('select#customer_id')
        .val(default_customer_id)
        .trigger('change');

    if ($('#default_selling_price_group').length) {
        $('#price_group').val($('#default_selling_price_group').val());
        $('#price_group').change();
    }

    //initialize tags input (tagify)
    if ($("textarea#repair_defects").length > 0 && !customer_set) {
        let suggestions = [];
        if ($("input#pos_repair_defects_suggestion").length > 0 && $("input#pos_repair_defects_suggestion").val().length > 2) {
            suggestions = JSON.parse($("input#pos_repair_defects_suggestion").val());    
        }
        let repair_defects = document.querySelector('textarea#repair_defects');
        tagify_repair_defects = new Tagify(repair_defects, {
                  whitelist: suggestions,
                  maxTags: 100,
                  dropdown: {
                    maxItems: 100,           // <- mixumum allowed rendered suggestions
                    classname: "tags-look", // <- custom classname for this dropdown, so it could be targeted
                    enabled: 0,             // <- show suggestions on focus
                    closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
                  }
                });
    }

    customer_set = true;
}

//Set the location and initialize printer
function set_location() {
    if ($('select#select_location_id').length == 1) {
        $('input#location_id').val($('select#select_location_id').val());
        $('input#location_id').data(
            'receipt_printer_type',
            $('select#select_location_id')
                .find(':selected')
                .data('receipt_printer_type')
        );
        $('input#location_id').data(
            'default_payment_accounts',
            $('select#select_location_id')
                .find(':selected')
                .data('default_payment_accounts')
        );

        $('input#location_id').attr(
            'data-default_price_group',
            $('select#select_location_id')
                .find(':selected')
                .data('default_price_group')
        );
    }

    if ($('input#location_id').val()) {
        $('input#search_product')
            .prop('disabled', false)
            .focus();
    } else {
        $('input#search_product').prop('disabled', true);
    }

    initialize_printer();
}

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

$('body').on('click', 'label', function(e) {
    var field_id = $(this).attr('for');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

$('body').on('focus', 'select', function(e) {
    var field_id = $(this).attr('id');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

function round_row_to_iraqi_dinnar(row) {
    if (iraqi_selling_price_adjustment) {
        var element = row.find('input.pos_unit_price_inc_tax');
        var unit_price = round_to_iraqi_dinnar(__read_number(element));
        __write_number(element, unit_price);
        element.change();
    }
}

function pos_print(receipt) {
    //If printer type then connect with websocket
    if (receipt.print_type == 'printer') {
        var content = receipt;
        content.type = 'print-receipt';

        //Check if ready or not, then print.
        if (socket != null && socket.readyState == 1) {
            socket.send(JSON.stringify(content));
        } else {
            initializeSocket();
            setTimeout(function() {
                socket.send(JSON.stringify(content));
            }, 700);
        }

    } else if (receipt.html_content != '') {
        var title = document.title;
        if (typeof receipt.print_title != 'undefined') {
            document.title = receipt.print_title;
        }

        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        __print_receipt('receipt_section');

        setTimeout(function() {
            document.title = title;
        }, 1200);
    }
}

function calculate_discounted_unit_price(row) {
    //console.log("TEST3");
    var this_unit_price = __read_number(row.find('input.pos_unit_price'));
    var row_discounted_unit_price = this_unit_price;
    var row_discount_type = row.find('select.row_discount_type').val();
    var row_discount_amount = __read_number(row.find('input.row_discount_amount'));
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            row_discounted_unit_price = this_unit_price - row_discount_amount;
        } else {
            row_discounted_unit_price = __substract_percent(this_unit_price, row_discount_amount);
        }
    }

    return row_discounted_unit_price;
}

function get_unit_price_from_discounted_unit_price(row, discounted_unit_price) {
    var this_unit_price = discounted_unit_price;
    var row_discount_type = row.find('select.row_discount_type').val();
    var row_discount_amount = __read_number(row.find('input.row_discount_amount'));
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            this_unit_price = discounted_unit_price + row_discount_amount;
        } else {
            this_unit_price = __get_principle(discounted_unit_price, row_discount_amount, true);
        }
    }

    return this_unit_price;
}

//Update quantity if line subtotal changes
$('table#pos_table tbody').on('change', 'input.pos_line_total', function() {
    //console.log("TEST4");
    var subtotal = __read_number($(this));
    var tr = $(this).parents('tr');
    var quantity_element = tr.find('input.pos_quantity');
    
    // Don't update quantity if preserve-decimals flag is set (during price changes)
    if (quantity_element.data('preserve-decimals')) {
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(subtotal, true));
        pos_total_row();
        return;
    }
    
    var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
    var quantity = subtotal / unit_price_inc_tax;
    __write_number(quantity_element, quantity);

    if (sell_form_validator) {
        sell_form_validator.element(quantity_element);
    }
    if (pos_form_validator) {
        pos_form_validator.element(quantity_element);
    }
    tr.find('span.pos_line_total_text').text(__currency_trans_from_en(subtotal, true));

    pos_total_row();
});

$('div#product_list_body').on('scroll', function() {
    if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
        var page = parseInt($('#suggestion_page').val());
        page += 1;
        $('#suggestion_page').val(page);
        var location_id = $('input#location_id').val();
        var category_id = $('select#product_category').val();
        var brand_id = $('select#product_brand').val();

        var is_enabled_stock = null;
        if ($("#is_enabled_stock").length) {
            is_enabled_stock = $("#is_enabled_stock").val();
        }

        var device_model_id = null;
        if ($("#repair_model_id").length) {
            device_model_id = $("#repair_model_id").val();
        }

        get_product_suggestion_list(category_id, brand_id, location_id, null, is_enabled_stock, device_model_id);
    }
});

$(document).on('ifChecked', '#is_recurring', function() {
    $('#recurringInvoiceModal').modal('show');
});

$(document).on('shown.bs.modal', '#recurringInvoiceModal', function() {
    $('input#recur_interval').focus();
});

$(document).on('click', '#select_all_service_staff', function() {
    var val = $('#res_waiter_id').val();
    $('#pos_table tbody')
        .find('select.order_line_service_staff')
        .each(function() {
            $(this)
                .val(val)
                .change();
        });
});

$(document).on('click', '.print-invoice-link', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('href') + "?check_location=true",
        dataType: 'json',
        success: function(result) {
            if (result.success == 1) {
                //Check if enabled or not
                if (result.receipt.is_enabled) {
                    pos_print(result.receipt);
                }
            } else {
                toastr.error(result.msg);
            }

        },
    });
});

function getCustomerRewardPoints() {
    if ($('#reward_point_enabled').length <= 0) {
        return false;
    }
    var is_edit = $('form#edit_sell_form').length || 
    $('form#edit_pos_sell_form').length ? true : false;
    if (is_edit && !customer_set) {
        return false;
    }

    var customer_id = $('#customer_id').val();

    $.ajax({
        method: 'POST',
        url: '/sells/pos/get-reward-details',
        data: { 
            customer_id: customer_id
        },
        dataType: 'json',
        success: function(result) {
            $('#available_rp').text(result.points);
            $('#rp_redeemed_modal').data('max_points', result.points);
            updateRedeemedAmount();
            $('#rp_redeemed_amount').change()
        },
    });
}

function updateRedeemedAmount(argument) {
    var points = $('#rp_redeemed_modal').val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($('#rp_redeemed_modal').data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text').text(__currency_trans_from_en(redeemed_amount, true));
    $('#rp_redeemed').val(points);
    $('#rp_redeemed_amount').val(redeemed_amount);
}

$(document).on('change', 'select#customer_id', function(){
    var default_customer_id = $('#default_customer_id').val();
    if ($(this).val() == default_customer_id) {
        //Disable reward points for walkin customers
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').val('');
            $('#rp_redeemed_modal').change();
            $('#rp_redeemed_modal').attr('disabled', true);
            $('#available_rp').text('');
            updateRedeemedAmount();
            pos_total_row();
        }
    } else {
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').removeAttr('disabled');
        }
        getCustomerRewardPoints();
    }

    get_sales_orders();
});

$(document).on('change', '#rp_redeemed_modal', function(){
    var points = $(this).val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($(this).data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text').text(__currency_trans_from_en(redeemed_amount, true));
    var reward_validation = isValidatRewardPoint();
    if (!reward_validation['is_valid']) {
        toastr.error(reward_validation['msg']);
        $('#rp_redeemed_modal').select();
    }
});

$(document).on('change', '.direct_sell_rp_input', function(){
    updateRedeemedAmount();
    pos_total_row();
});

function isValidatRewardPoint() {
    var element = $('#rp_redeemed_modal');
    var points = element.val().trim();
    points = points == '' ? 0 : parseInt(points);

    var max_points = parseInt(element.data('max_points'));
    var is_valid = true;
    var msg = '';

    if (points == 0) {
        return {
            is_valid: is_valid,
            msg: msg
        }
    }

    var rp_name = $('input#rp_name').val();
    if (points > max_points) {
        is_valid = false;
        msg = __translate('max_rp_reached_error', {max_points: max_points, rp_name: rp_name});
    }

    var min_order_total_required = parseFloat(element.data('min_order_total'));

    var order_total = __read_number($('#final_total_input'));

    if (order_total < min_order_total_required) {
        is_valid = false;
        msg = __translate('min_order_total_error', {min_order: __currency_trans_from_en(min_order_total_required, true), rp_name: rp_name});
    }

    var output = {
        is_valid: is_valid,
        msg: msg,
    }

    return output;
}

function adjustComboQty(tr){
    if(tr.find('input.product_type').val() == 'combo'){
        var qty = __read_number(tr.find('input.pos_quantity'));
        var multiplier = __getUnitMultiplier(tr);

        tr.find('input.combo_product_qty').each(function(){
            $(this).val($(this).data('unit_quantity') * qty * multiplier);
        });
    }
}

$(document).on('change', '#types_of_service_id', function(){
    var types_of_service_id = $(this).val();
    var location_id = $('#location_id').val();

    if(types_of_service_id) {
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get-types-of-service-details',
            data: { 
                types_of_service_id: types_of_service_id,
                location_id: location_id
            },
            dataType: 'json',
            success: function(result) {
                //reset form if price group is changed
                var prev_price_group = $('#types_of_service_price_group').val();
                if(result.price_group_id) {
                    $('#types_of_service_price_group').val(result.price_group_id);
                    $('#price_group_text').removeClass('hide');
                    $('#price_group_text span').text(result.price_group_name);
                } else {
                    $('#types_of_service_price_group').val('');
                    $('#price_group_text').addClass('hide');
                    $('#price_group_text span').text('');
                }
                $('#types_of_service_id').val(types_of_service_id);
                $('.types_of_service_modal').html(result.modal_html);
                
                if (prev_price_group != result.price_group_id) {
                    if ($('form#edit_pos_sell_form').length > 0) {
                        $('table#pos_table tbody').html('');
                        pos_total_row();
                    } else {
                        reset_pos_form();
                    }
                } else {
                    pos_total_row();
                }

                //$('.types_of_service_modal').modal('show');
            },
        });
    } else {
        $('.types_of_service_modal').html('');
        $('#types_of_service_price_group').val('');
        $('#price_group_text').addClass('hide');
        $('#price_group_text span').text('');
        $('#packing_charge_text').text('');
        if ($('form#edit_pos_sell_form').length > 0) {
            $('table#pos_table tbody').html('');
            pos_total_row();
        } else {
            reset_pos_form();
        }
    }
});

$(document).on('change', 'input#packing_charge, #additional_expense_value_1, #additional_expense_value_2, \
        #additional_expense_value_3, #additional_expense_value_4', function() {
    pos_total_row();
});

$(document).on('click', '.service_modal_btn', function(e) {
    if ($('#types_of_service_id').val()) {
        $('.types_of_service_modal').modal('show');
    }
});

$(document).on('change', '.payment_types_dropdown', function(e) {
    var default_accounts = $('select#select_location_id').length ? 
                $('select#select_location_id')
                .find(':selected')
                .data('default_payment_accounts') : $('#location_id').data('default_payment_accounts');
    var payment_type = $(this).val();
    var payment_row = $(this).closest('.payment_row');
    if (payment_type && payment_type != 'advance') {
        var default_account = default_accounts && default_accounts[payment_type]['account'] ? 
            default_accounts[payment_type]['account'] : '';
        var row_index = payment_row.find('.payment_row_index').val();

        var account_dropdown = payment_row.find('select#account_' + row_index);
        if (account_dropdown.length && default_accounts) {
            account_dropdown.val(default_account);
            account_dropdown.change();
        }
    }

    //Validate max amount and disable account if advance 
    amount_element = payment_row.find('.payment-amount');
    account_dropdown = payment_row.find('.account-dropdown');
    if (payment_type == 'advance') {
        max_value = $('#advance_balance').val();
        msg = $('#advance_balance').data('error-msg');
        amount_element.rules('add', {
            'max-value': max_value,
            messages: {
                'max-value': msg,
            },
        });
        if (account_dropdown) {
            account_dropdown.prop('disabled', true);
            account_dropdown.closest('.form-group').addClass('hide');
        }
    } else {
        amount_element.rules("remove", "max-value");
        if (account_dropdown) {
            account_dropdown.prop('disabled', false);
            account_dropdown.closest('.form-group').removeClass('hide');
        }
    }

    // Show/hide cash denomination div based on payment method
    if ($(this).closest('.payment_row').find('.enable_cash_denomination_for_payment_methods').length) {
        var payment_methods = JSON.parse($(this).closest('.payment_row').find('.enable_cash_denomination_for_payment_methods').val());
        if (payment_methods.indexOf(payment_type) >= 0) {
            $(this).closest('.payment_row').find('.cash_denomination_div').removeClass('hide');
        } else {
            $(this).closest('.payment_row').find('.cash_denomination_div').addClass('hide');
        }
    }
});

$(document).on('show.bs.modal', '#recent_transactions_modal', function () {
    get_recent_transactions('final', $('div#tab_final'));
});
$(document).on('shown.bs.tab', 'a[href="#tab_quotation"]', function () {
    get_recent_transactions('quotation', $('div#tab_quotation'));
});
$(document).on('shown.bs.tab', 'a[href="#tab_draft"]', function () {
    get_recent_transactions('draft', $('div#tab_draft'));
});

function disable_pos_form_actions(){
    if (!window.navigator.onLine) {
        return false;
    }

    $('div.pos-processing').show();
    $('#pos-save').attr('disabled', 'true');
    $('div.pos-form-actions').find('button').attr('disabled', 'true');
}

function enable_pos_form_actions(){
    $('div.pos-processing').hide();
    $('#pos-save').removeAttr('disabled');
    $('div.pos-form-actions').find('button').removeAttr('disabled');
}

$(document).on('change', '#recur_interval_type', function() {
    if ($(this).val() == 'months') {
        $('.subscription_repeat_on_div').removeClass('hide');
    } else {
        $('.subscription_repeat_on_div').addClass('hide');
    }
});

function validate_discount_field() {
    discount_element = $('#discount_amount_modal');
    discount_type_element = $('#discount_type_modal');

    if ($('#add_sell_form').length || $('#edit_sell_form').length) {
        discount_element = $('#discount_amount');
        discount_type_element = $('#discount_type');
    }
    var max_value = parseFloat(discount_element.data('max-discount'));
    if (discount_element.val() != '' && !isNaN(max_value)) {
        if (discount_type_element.val() == 'fixed') {
            var subtotal = get_subtotal();
            //get max discount amount
            max_value = __calculate_amount('percentage', max_value, subtotal)
        }

        discount_element.rules('add', {
            'max-value': max_value,
            messages: {
                'max-value': discount_element.data('max-discount-error_msg'),
            },
        });
    } else {
        discount_element.rules("remove", "max-value");      
    }
    discount_element.trigger('change');
}

$(document).on('change', '#discount_type_modal, #discount_type', function() {
    validate_discount_field();
});

function update_shipping_address(data) {
    if ($('#shipping_address_div').length) {
        var shipping_address = '';
        if (data.supplier_business_name) {
            shipping_address += data.supplier_business_name;
        }
        if (data.name) {
            shipping_address += ',<br>' + data.name;
        }
        if (data.text) {
            shipping_address += ',<br>' + data.text;
        }
        shipping_address += ',<br>' + data.shipping_address ;
        $('#shipping_address_div').html(shipping_address);
    }
    if ($('#billing_address_div').length) {
        var address = [];
        if (data.supplier_business_name) {
            address.push(data.supplier_business_name);
        }
        if (data.name) {
            address.push('<br>' + data.name);
        }
        if (data.text) {
            address.push('<br>' + data.text);
        }
        if (data.address_line_1) {
            address.push('<br>' + data.address_line_1);
        }
        if (data.address_line_2) {
            address.push('<br>' + data.address_line_2);
        }
        if (data.city) {
            address.push('<br>' + data.city);
        }
        if (data.state) {
            address.push(data.state);
        }
        if (data.country) {
            address.push(data.country);
        }
        if (data.zip_code) {
            address.push('<br>' + data.zip_code);
        }
        var billing_address = address.join(', ');
        $('#billing_address_div').html(billing_address);
    }

    if ($('#shipping_custom_field_1').length) {
        let shipping_custom_field_1 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_1 : '';
        $('#shipping_custom_field_1').val(shipping_custom_field_1);
    }

    if ($('#shipping_custom_field_2').length) {
        let shipping_custom_field_2 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_2 : '';
        $('#shipping_custom_field_2').val(shipping_custom_field_2);
    }

    if ($('#shipping_custom_field_3').length) {
        let shipping_custom_field_3 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_3 : '';
        $('#shipping_custom_field_3').val(shipping_custom_field_3);
    }

    if ($('#shipping_custom_field_4').length) {
        let shipping_custom_field_4 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_4 : '';
        $('#shipping_custom_field_4').val(shipping_custom_field_4);
    }

    if ($('#shipping_custom_field_5').length) {
        let shipping_custom_field_5 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_5 : '';
        $('#shipping_custom_field_5').val(shipping_custom_field_5);
    }
    
    //update export fields
    if (data.is_export) {
        $('#is_export').prop('checked', true);
        $('div.export_div').show();
        if ($('#export_custom_field_1').length) {
            $('#export_custom_field_1').val(data.export_custom_field_1);
        }
        if ($('#export_custom_field_2').length) {
            $('#export_custom_field_2').val(data.export_custom_field_2);
        }
        if ($('#export_custom_field_3').length) {
            $('#export_custom_field_3').val(data.export_custom_field_3);
        }
        if ($('#export_custom_field_4').length) {
            $('#export_custom_field_4').val(data.export_custom_field_4);
        }
        if ($('#export_custom_field_5').length) {
            $('#export_custom_field_5').val(data.export_custom_field_5);
        }
        if ($('#export_custom_field_6').length) {
            $('#export_custom_field_6').val(data.export_custom_field_6);
        }
    } else {
        $('#export_custom_field_1, #export_custom_field_2, #export_custom_field_3, #export_custom_field_4, #export_custom_field_5, #export_custom_field_6').val('');
        $('#is_export').prop('checked', false);
        $('div.export_div').hide();
    }
    
    $('#shipping_address_modal').val(data.shipping_address);
    $('#shipping_address').val(data.shipping_address);
}

function get_sales_orders() {
    if ($('#sales_order_ids').length) {
        if ($('#sales_order_ids').hasClass('not_loaded')) {
            $('#sales_order_ids').removeClass('not_loaded');
            return false;
        }
        var customer_id = $('select#customer_id').val();
        var location_id = $('input#location_id').val();
        $.ajax({
            url: '/get-sales-orders/' + customer_id + '?location_id=' + location_id,
            dataType: 'json',
            success: function(data) {
                $('#sales_order_ids').select2('destroy').empty().select2({data: data});
                $('table#pos_table tbody').find('tr').each( function(){
                    if (typeof($(this).data('so_id')) !== 'undefined') {
                        $(this).remove();
                    }
                });
                pos_total_row();
            },
        });
    }
}

$("#sales_order_ids").on("select2:select", function (e) {
    var sales_order_id = e.params.data.id;
    var product_row = $('input#product_row_count').val();
    var location_id = $('input#location_id').val();
    $.ajax({
        method: 'GET',
        url: '/get-sales-order-lines',
        async: false,
        data: {
            product_row: product_row,
            sales_order_id: sales_order_id
        },
        dataType: 'json',
        success: function(result) {
            if (result.html) {
                var html = result.html;
                $(html).find('tr').each(function(){
                    $('table#pos_table tbody')
                    .append($(this))
                    .find('input.pos_quantity');
                    
                    var this_row = $('table#pos_table tbody')
                        .find('tr')
                        .last();
                    pos_each_row(this_row);

                    product_row = parseInt(product_row) + 1;

                    //For initial discount if present
                    var line_total = __read_number(this_row.find('input.pos_line_total'));
                    this_row.find('span.pos_line_total_text').text(line_total);

                    //Check if multipler is present then multiply it when a new row is added.
                    if(__getUnitMultiplier(this_row) > 1){
                        this_row.find('select.sub_unit').trigger('change');
                    }

                    round_row_to_iraqi_dinnar(this_row);
                    __currency_convert_recursively(this_row);
                });

                set_so_values(result.sales_order);

                //increment row count
                $('input#product_row_count').val(product_row);
                
                pos_total_row();
            
            } else {
                toastr.error(result.msg);
                $('input#search_product')
                    .focus()
                    .select();
            }
        },
    });
});

function set_so_values(so) {
    $('textarea[name="sale_note"]').val(so.additional_notes);
    if ($('#shipping_details').is(':visible')) {
        $('#shipping_details').val(so.shipping_details);
    }
    $('#shipping_address').val(so.shipping_address);
    $('#delivered_to').val(so.delivered_to);
    $('#shipping_charges').val( __number_f(so.shipping_charges));
    $('#shipping_status').val(so.shipping_status);
    if ($('#shipping_custom_field_1').length) {
        $('#shipping_custom_field_1').val(so.shipping_custom_field_1);
    }
    if ($('#shipping_custom_field_2').length) {
        $('#shipping_custom_field_2').val(so.shipping_custom_field_2);
    }
    if ($('#shipping_custom_field_3').length) {
        $('#shipping_custom_field_3').val(so.shipping_custom_field_3);
    }
    if ($('#shipping_custom_field_4').length) {
        $('#shipping_custom_field_4').val(so.shipping_custom_field_4);
    }
    if ($('#shipping_custom_field_5').length) {
        $('#shipping_custom_field_5').val(so.shipping_custom_field_5);
    }
}

$("#sales_order_ids").on("select2:unselect", function (e) {
    var sales_order_id = e.params.data.id;
    $('table#pos_table tbody').find('tr').each( function(){
        if (typeof($(this).data('so_id')) !== 'undefined' 
            && $(this).data('so_id') == sales_order_id) {
            $(this).remove();
        pos_total_row();
        }
    });
});

$(document).on('click', '#add_expense', function(){
    $.ajax({
        url: '/expenses/create',
        data: { 
            location_id: $('#select_location_id').val()
        },
        dataType: 'html',
        success: function(result) {
            $('#expense_modal').html(result);
            $('#expense_modal').modal('show');
        },
    });
});

$(document).on('shown.bs.modal', '#expense_modal', function(){
    $('#expense_transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
    $('#expense_modal .paid_on').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
    $(this).find('.select2').select2();
    $('#add_expense_modal_form').validate();
});

$(document).on('hidden.bs.modal', '#expense_modal', function(){
    $(this).html('');
});

$(document).on('submit', 'form#add_expense_modal_form', function(e) {
    e.preventDefault();
    var data = $(this).serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        success: function(result) {
            if (result.success == true) {
                $('#expense_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

function get_contact_due(id) {
    $.ajax({
        method: 'get',
        url: /get-contact-due/ + id,
        dataType: 'text',
        success: function(result) {
            if (result != '') {
                $('.contact_due_text').find('span').text(result);
                $('.contact_due_text').removeClass('hide');
            } else {
                $('.contact_due_text').find('span').text('');
                $('.contact_due_text').addClass('hide');
            }
        },
    });
}

function submitQuickContactForm(form) {
    var data = $(form).serialize();
    $.ajax({
        method: 'POST',
        url: $(form).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button($(form).find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                var name = result.data.name;

                if (result.data.supplier_business_name) {
                    name += result.data.supplier_business_name;
                }
                
                $('select#customer_id').append(
                    $('<option>', { value: result.data.id, text: name })
                );
                $('select#customer_id')
                    .val(result.data.id)
                    .trigger('change');
                $('div.contact_modal').modal('hide');
                update_shipping_address(result.data)
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

$(document).on('click', '#send_for_sell_return', function(e) {
    var invoice_no = $('#send_for_sell_return_invoice_no').val();

    if (invoice_no) {
        $.ajax({
            method: 'get',
            url: /validate-invoice-to-return/ + encodeURI(invoice_no),
            dataType: 'json',
            success: function(result) {
                if (result.success == true) {
                    window.location = result.redirect_url ;
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    }
})

$(document).on('ifChanged', 'input[name="search_fields[]"]', function(event) {
    var search_fields = [];
    $('input[name="search_fields[]"]:checked').each(function() {
       search_fields.push($(this).val());
    });

    localStorage.setItem('pos_search_fields', search_fields);
});

function set_search_fields() {
    if ($('input[name="search_fields[]"]').length == 0) {
        return false;
    }

    var pos_search_fields = localStorage.getItem('pos_search_fields');

    /* if (pos_search_fields === null) {
        pos_search_fields = ['name', 'sku', 'lot'];
    } */

    if (pos_search_fields === null) {
        pos_search_fields = ['name', 'sku', 'lot', 'barcode'];

    } else {
        pos_search_fields = JSON.parse(pos_search_fields);
    }

    if (!pos_search_fields.includes('barcode')) {
        pos_search_fields.push('barcode');
        localStorage.setItem('pos_search_fields', JSON.stringify(pos_search_fields));
    }

    $('input[name="search_fields[]"]').each(function() {
        if (pos_search_fields.indexOf($(this).val()) >= 0) {
            $(this).iCheck('check');
        } else {
            $(this).iCheck('uncheck');
        }
    });
}

$(document).on('click', '#show_service_staff_availability', function(){
    loadServiceStaffAvailability();
})
$(document).on('click', '#refresh_service_staff_availability_status', function(){
    loadServiceStaffAvailability(false);
})
$(document).on('click', 'button.pause_resume_timer', function(e){
    $('.view_modal').find('.overlay').removeClass('hide');
    $.ajax({
        method: 'get',
        url: $(this).attr('data-href'),
        dataType: 'json',
        success: function(result) {
            loadServiceStaffAvailability(false);
        },
    });
})

$(document).on('click', '.mark_as_available', function(e){
    e.preventDefault()
    $('.view_modal').find('.overlay').removeClass('hide');
    $.ajax({
        method: 'get',
        url: $(this).attr('href'),
        dataType: 'json',
        success: function(result) {
            loadServiceStaffAvailability(false);
        },
    });
})
var service_staff_availability_interval = null;

function loadServiceStaffAvailability(show = true) {
    var location_id = $('[name="location_id"]').val();
    $.ajax({
        method: 'get',
        url: $('#show_service_staff_availability').attr('data-href'),
        dataType: 'html',
        data: {location_id: location_id},
        success: function(result) {
            $('.view_modal').html(result);
            if (show) {
                $('.view_modal').modal('show')

                //auto refresh service staff availabilty if modal is open
                service_staff_availability_interval = setInterval(function () {
                    loadServiceStaffAvailability(false);
                }, 60000);
            }
        },
    });
}

$(document).on('hidden.bs.modal', '.view_modal', function(){
    if (service_staff_availability_interval !== null) {
        clearInterval(service_staff_availability_interval);
    }
    service_staff_availability_interval = null;
});

// ================ IMEI FUNCTIONALITY FOR POS MODAL ================

// Update required IMEI count when quantity changes (real-time update disabled to prevent conflicts)
$(document).on('input change', '.modal_quantity', function() {
    var row_index = $(this).data('row-index');
    var new_quantity = parseInt($(this).val()) || 0;
    var modal = $('#row_edit_product_price_modal_' + row_index);
    
    // Update required IMEI count display only (no real-time POS sync)
    modal.find('.required_imei_count').text(new_quantity);
    
    // Validate selected IMEIs count
    validateImeiSelection(row_index);
});

// Update button click handler for modal
$(document).on('click', '.update-modal-quantities', function() {
    console.log('=== UPDATE BUTTON CLICKED ===');
    
    var $button = $(this);
    var row_index = $button.data('row-index');
    console.log('Row index:', row_index);
    
    var modal = $('#row_edit_product_price_modal_' + row_index);
    var modal_quantity = modal.find('.modal_quantity').val();
    var main_quantity_input = $('#pos_table').find('tr[data-row_index="' + row_index + '"]').find('.pos_quantity');
    
    console.log('Modal quantity:', modal_quantity);
    console.log('Main quantity input found:', main_quantity_input.length);
    console.log('Main quantity before:', main_quantity_input.val());
    
    if (main_quantity_input.length === 0) {
        console.error('Could not find main quantity input for row', row_index);
        toastr.error('Could not find quantity input');
        return;
    }
    
    // Temporarily remove processing flag to allow update
    main_quantity_input.removeData('processing-change');
    console.log('Removed processing flag');
    
    // Convert to numbers for proper comparison (handles formatting differences)
    var main_qty = parseFloat(main_quantity_input.val() || 0);
    var modal_qty = parseFloat(modal_quantity || 0);
    
    console.log('Main qty (parsed):', main_qty);
    console.log('Modal qty (parsed):', modal_qty);
    
    // Always update regardless of comparison to ensure sync
    console.log('Force updating quantity from', main_quantity_input.val(), 'to', modal_quantity);
    main_quantity_input.val(modal_quantity);
    console.log('New value set:', main_quantity_input.val());
    
    // Trigger change event directly
    console.log('Triggering change event...');
    main_quantity_input.trigger('change');
    console.log('Change event triggered');
    
    // Show success feedback
    toastr.success('Quantity updated successfully');
    
    // Auto-close modal after successful update
    setTimeout(function() {
        console.log('Closing modal...');
        modal.modal('hide');
    }, 1000);
    
    console.log('=== UPDATE BUTTON FINISHED ===');
});

// Search available IMEIs
$(document).on('click', '.search_available_imeis', function() {
    var row_index = $(this).data('row-index');
    var modal = $('#row_edit_product_price_modal_' + row_index);
    var search_term = modal.find('.imei_search_input').val();
    var product_id = modal.find('.imei_search_input').data('product-id');
    var variation_id = modal.find('.imei_search_input').data('variation-id');
    var location_id = modal.find('.imei_search_input').data('location-id');
    
    // Show loading
    var container = modal.find('.available_imeis_container');
    container.html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading available IMEIs...</div>');
    
    $.ajax({
        url: '/pos/search-available-imeis',
        method: 'POST',
        data: {
            product_id: product_id,
            variation_id: variation_id,
            location_id: location_id,
            search_term: search_term,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                displayAvailableImeis(row_index, response.data);
            } else {
                container.html('<div class="text-danger">Error loading IMEIs</div>');
            }
        },
        error: function(xhr) {
            console.error('IMEI search error:', xhr);
            container.html('<div class="text-danger">Error loading IMEIs</div>');
        }
    });
});

// Display available IMEIs in selectable format
function displayAvailableImeis(row_index, imeis) {
    var modal = $('#row_edit_product_price_modal_' + row_index);
    var container = modal.find('.available_imeis_container');
    
    if (imeis.length === 0) {
        container.html('<div class="text-muted text-center" style="padding: 20px;">No available IMEIs found</div>');
        return;
    }
    
    var html = '<div class="available-imeis-list">';
    html += '<div style="margin-bottom: 10px;"><strong>Available IMEIs (Click to select):</strong></div>';
    
    imeis.forEach(function(imei) {
        var purchase_info = '';
        if (imei.purchase_date) {
            purchase_info = '<small class="text-muted">(' + imei.purchase_date;
            if (imei.purchase_price) {
                purchase_info += ', $' + imei.purchase_price;
            }
            purchase_info += ')</small>';
        }
        
        var location_info = '';
        if (imei.location_name) {
            location_info = '<small class="text-info"><i class="fa fa-map-marker"></i> ' + imei.location_name + '</small>';
        }
        
        html += '<div class="imei-item" style="padding: 8px; border: 1px solid #ddd; margin: 3px 0; cursor: pointer; border-radius: 4px;" ' +
                'data-imei-id="' + imei.id + '" data-imei="' + imei.imei + '" data-location-id="' + imei.location_id + '" data-row-index="' + row_index + '">' +
                '<span class="imei-text"><strong>' + imei.imei + '</strong></span><br>' +
                location_info + ' ' + purchase_info +
                '</div>';
    });
    
    html += '</div>';
    container.html(html);
}

// Handle IMEI selection from available list
$(document).on('click', '.imei-item', function() {
    var row_index = $(this).data('row-index');
    var imei_id = $(this).data('imei-id');
    var imei = $(this).data('imei');
    var modal = $('#row_edit_product_price_modal_' + row_index);
    
    // Check if already selected
    var selected_container = modal.find('.selected_imeis_list');
    if (selected_container.find('[data-imei-id="' + imei_id + '"]').length > 0) {
        toastr.warning('IMEI already selected');
        return;
    }
    
    // Check quantity limit
    var required_quantity = parseInt(modal.find('.required_imei_count').text()) || 0;
    var current_selected = selected_container.find('.selected-imei-item').length;
    
    if (current_selected >= required_quantity) {
        toastr.warning('Cannot select more IMEIs than quantity (' + required_quantity + ')');
        return;
    }
    
    // Add to selected list
    if (selected_container.find('.no_selection_message').length) {
        selected_container.empty();
    }
    
    var selected_html = '<div class="selected-imei-item" style="padding: 5px; background: #e8f5e8; border: 1px solid #4CAF50; margin: 2px 0; border-radius: 3px; display: flex; justify-content: space-between; align-items: center;" ' +
                       'data-imei-id="' + imei_id + '" data-imei="' + imei + '">' +
                       '<span>' + imei + '</span>' +
                       '<button type="button" class="btn btn-xs btn-danger remove-selected-imei" style="padding: 2px 6px;">' +
                       '<i class="fa fa-times"></i>' +
                       '</button>' +
                       '</div>';
    
    selected_container.append(selected_html);
    
    // Visual feedback on source item
    $(this).css('background-color', '#d4edda').css('border-color', '#4CAF50');
    
    // Update counts and hidden field
    updateImeiSelection(row_index);
    
    toastr.success('IMEI selected: ' + imei);
});

// Remove selected IMEI
$(document).on('click', '.remove-selected-imei', function(e) {
    e.stopPropagation();
    var imei_item = $(this).closest('.selected-imei-item');
    var imei_id = imei_item.data('imei-id');
    var row_index = imei_item.closest('.modal').attr('id').replace('row_edit_product_price_modal_', '');
    
    // Remove visual selection from available list
    $('#row_edit_product_price_modal_' + row_index)
        .find('.imei-item[data-imei-id="' + imei_id + '"]')
        .css('background-color', '')
        .css('border-color', '#ddd');
    
    // Remove from selected list
    imei_item.remove();
    
    // Update counts and hidden field
    updateImeiSelection(row_index);
    
    // Show "no selection" message if empty
    var modal = $('#row_edit_product_price_modal_' + row_index);
    if (modal.find('.selected-imei-item').length === 0) {
        modal.find('.selected_imeis_list').html('<div class="no_selection_message text-muted">No IMEIs selected</div>');
    }
});

// Update IMEI selection counts and hidden field
function updateImeiSelection(row_index) {
    var modal = $('#row_edit_product_price_modal_' + row_index);
    var selected_items = modal.find('.selected-imei-item');
    var selected_count = selected_items.length;
    var required_count = parseInt(modal.find('.required_imei_count').text()) || 0;
    
    // Update count display
    modal.find('.selected_imei_count').text(selected_count);
    
    // Update hidden field with selected IMEI IDs
    var selected_imei_ids = [];
    selected_items.each(function() {
        selected_imei_ids.push($(this).data('imei-id'));
    });
    modal.find('.selected_imeis_input').val(selected_imei_ids.join(','));
    
    // Update summary styling based on validation
    var summary = modal.find('.imei_selection_summary');
    if (selected_count === required_count) {
        summary.removeClass('text-warning text-danger').addClass('text-success');
    } else if (selected_count > required_count) {
        summary.removeClass('text-success text-warning').addClass('text-danger');
    } else {
        summary.removeClass('text-success text-danger').addClass('text-warning');
    }
}

// Validate IMEI selection against quantity
function validateImeiSelection(row_index) {
    var modal = $('#row_edit_product_price_modal_' + row_index);
    var selected_count = modal.find('.selected-imei-item').length;
    var required_count = parseInt(modal.find('.required_imei_count').text()) || 0;
    
    if (selected_count > required_count) {
        // Remove excess selections (oldest first)
        var excess = selected_count - required_count;
        modal.find('.selected-imei-item').slice(0, excess).each(function() {
            $(this).find('.remove-selected-imei').click();
        });
        
        if (excess > 0) {
            toastr.info('Removed ' + excess + ' IMEI(s) to match new quantity');
        }
    }
}

// Search IMEIs as user types
$(document).on('keyup', '.imei_search_input', function() {
    var $this = $(this);
    clearTimeout($this.data('timeout'));
    
    $this.data('timeout', setTimeout(function() {
        var row_index = $this.data('row-index');
        $('#row_edit_product_price_modal_' + row_index + ' .search_available_imeis').click();
    }, 500));
});

// Clear search when modal opens
$(document).on('show.bs.modal', '.row_edit_product_price_model', function() {
    var modal = $(this);
    var modal_id = modal.attr('id');
    var row_index = modal_id.replace('row_edit_product_price_modal_', '');
    
    // Reset IMEI search
    modal.find('.imei_search_input').val('');
    modal.find('.available_imeis_container').html('<div class="no_imeis_message text-muted" style="padding: 10px; text-align: center;">Click search to load available IMEIs</div>');
    
    // Sync quantity with main form
    var main_quantity = $('#pos_table').find('tr[data-row_index="' + row_index + '"]').find('.pos_quantity').val() || 1;
    modal.find('.modal_quantity').val(main_quantity);
    modal.find('.required_imei_count').text(main_quantity);
});

// Sync quantity back to main form when modal is hidden
$(document).on('hidden.bs.modal', '.row_edit_product_price_model', function() {
    var modal = $(this);
    var modal_id = modal.attr('id');
    var row_index = modal_id.replace('row_edit_product_price_modal_', '');
    
    // Get modal quantity and sync to main form
    var modal_quantity = modal.find('.modal_quantity').val() || 1;
    var main_quantity_input = $('#pos_table').find('tr[data-row_index="' + row_index + '"]').find('.pos_quantity');
    
    // Only update if different
    if (main_quantity_input.val() != modal_quantity) {
        main_quantity_input.val(modal_quantity);
        // Trigger change event with special flag to bypass processing protection
        var changeEvent = $.Event('change');
        changeEvent.originalEvent = { imeiModal: true };
        main_quantity_input.trigger(changeEvent);
    }
});