// Make plastic_bag_data globally accessible
var plastic_bag_data = {};

$(document).ready(function() {
    
    // Load plastic bag types when modal is opened
    $('#posPlasticbagModal').on('show.bs.modal', function() {
        loadPlasticBagTypes();
    });

    function loadPlasticBagTypes() {
        $.ajax({
            url: window.plastic_bag_api_url || '/plastic-bag/get-plastic-bag-types-for-pos',
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(result) {
                if (result.success) {
                    buildPlasticBagRows(result.plastic_bag_types);
                } else {
                    toastr.error(result.msg || 'Error loading plastic bag types');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error loading plastic bag types');
            }
        });
    }

    function buildPlasticBagRows(plastic_bag_types) {
        var rows = '';
        plastic_bag_types.forEach(function(type) {
            var existing_qty = plastic_bag_data[type.id] ? plastic_bag_data[type.id].quantity : 0;
            
            rows += '<tr data-type-id="' + type.id + '">';
            rows += '<td><strong>' + type.name + '</strong><br><small class="text-muted">Stock: ' + parseFloat(type.stock_quantity).toFixed(0) + ' bags</small></td>';
            rows += '<td><span class="display_currency" data-currency_symbol="true">' + parseFloat(type.price).toFixed(2) + '</span></td>';
            rows += '<td>';
            rows += '<input type="number" class="form-control plastic-bag-qty" min="0" max="' + type.stock_quantity + '" ';
            rows += 'value="' + existing_qty + '" data-type-id="' + type.id + '" data-price="' + type.price + '">';
            rows += '</td>';
            rows += '<td class="line-total">0.00</td>';
            rows += '</tr>';
        });
        
        $('#plastic_bag_rows').html(rows);
        calculatePlasticBagTotal();
    }

    // Handle quantity changes
    $(document).on('input', '.plastic-bag-qty', function() {
        calculatePlasticBagTotal();
    });

    function calculatePlasticBagTotal() {
        var total = 0;
        
        $('.plastic-bag-qty').each(function() {
            var qty = parseInt($(this).val()) || 0;
            var price = parseFloat($(this).data('price')) || 0;
            var line_total = qty * price;
            
            $(this).closest('tr').find('.line-total').text(parseFloat(line_total).toFixed(2));
            total += line_total;
            
            // Store in plastic_bag_data
            var type_id = $(this).data('type-id');
            if (qty > 0) {
                plastic_bag_data[type_id] = {
                    quantity: qty,
                    price: price,
                    total: line_total
                };
            } else {
                delete plastic_bag_data[type_id];
            }
        });
        
        $('#total_plastic_bag_amount').text(parseFloat(total).toFixed(2));
    }

    // Update POS totals when plastic bag modal is updated
    $('#posPlasticbagModalUpdate').on('click', function() {
        var total_plastic_charges = 0;
        var plastic_bag_summary = [];
        
        for (var type_id in plastic_bag_data) {
            if (plastic_bag_data.hasOwnProperty(type_id)) {
                var item = plastic_bag_data[type_id];
                total_plastic_charges += item.total;
                plastic_bag_summary.push(item.quantity + ' bags');
            }
        }
        
        // Update the hidden input for plastic bag charges
        __write_number($('input#plasticbag_charges'), total_plastic_charges);
        
        // Update the display
        $("#plasticbag_charges_amount").html(__currency_trans_from_en(total_plastic_charges, false));
        
        // Store plastic bag data in a hidden input
        var plastic_bag_json = JSON.stringify(plastic_bag_data);
        if ($('#pos_plastic_bag_data').length) {
            $('#pos_plastic_bag_data').val(plastic_bag_json);
        } else {
            $('form#add_pos_sell_form, form#edit_pos_sell_form').append(
                '<input type="hidden" id="pos_plastic_bag_data" name="pos_plastic_bag_data" value="' + 
                plastic_bag_json.replace(/"/g, '&quot;') + '">'
            );
        }
        
        // Close modal
        $('div#posPlasticbagModal').modal('hide');
        
        // Recalculate POS totals
        pos_total_row();
        
        // Force recalculate balance due to ensure payment amounts are updated
        if (typeof calculate_balance_due === 'function') {
            calculate_balance_due();
        }
        
        toastr.success('Plastic bag selection updated');
    });

    // Function to reset plastic bag selection after sale completion
    function reset_plastic_bag_selection() {
        // Clear all plastic bag data globally
        window.plastic_bag_data = {};
        plastic_bag_data = {};
        
        // Clear any existing data that might be restored
        if (typeof existing_plastic_bag_data !== 'undefined') {
            window.existing_plastic_bag_data = undefined;
            existing_plastic_bag_data = undefined;
        }
        
        // Reset the hidden input
        $('#pos_plastic_bag_data').remove();
        $('input[name="pos_plastic_bag_data"]').remove();
        
        // Reset the display amounts
        __write_number($('input#plasticbag_charges'), 0);
        $("#plasticbag_charges_amount").html(__currency_trans_from_en(0, false));
        
        // Reset any quantity inputs in the modal to 0
        $('.plastic-bag-qty').val(0);
        $('#total_plastic_bag_amount').text('0.00');
        
        // Force refresh of plastic bag modal content if it exists
        if ($('#plastic_bag_rows').length > 0) {
            $('#plastic_bag_rows').empty();
        }
        
        // Call pos_total_row to recalculate totals
        if (typeof pos_total_row === 'function') {
            pos_total_row();
        }
        
        console.log('Plastic bag selection reset completed - data cleared:', plastic_bag_data);
    }

    // Make reset function and data globally available for POS form reset
    window.reset_plastic_bag_selection = reset_plastic_bag_selection;
    window.plastic_bag_data = plastic_bag_data;
    
    // Test function to manually trigger reset (for debugging)
    window.test_plastic_bag_reset = function() {
        console.log('Before reset:', plastic_bag_data);
        reset_plastic_bag_selection();
        console.log('After reset:', plastic_bag_data);
        console.log('Charges amount:', $("#plasticbag_charges_amount").text());
        console.log('Hidden input exists:', $('#pos_plastic_bag_data').length > 0);
    };

    // Make our plastic bag system compatible with the original pos_total_row function
    // The original function expects plasticbag_quantity and plasticbag_per_piece
    // But we need to bypass that logic and use our plasticbag_charges directly
});

// Initialize plastic bag data when editing existing transaction
if (typeof pos_edit_plastic_bags !== 'undefined' && pos_edit_plastic_bags) {
    $(document).ready(function() {
        // This will be populated from the server side when editing
        // Only restore if we're actually editing (not after a reset)
        if (typeof existing_plastic_bag_data !== 'undefined' && existing_plastic_bag_data !== undefined) {
            plastic_bag_data = existing_plastic_bag_data;
            window.plastic_bag_data = existing_plastic_bag_data;
            console.log('Restored plastic bag data for editing:', existing_plastic_bag_data);
        }
    });
}