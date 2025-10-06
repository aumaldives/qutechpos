$(document).ready(function() {
    $(document).on('change', '.purchase_quantity', function() {
        update_table_total($(this).closest('table'));
    });
    $(document).on('change', '.unit_price', function() {
        update_table_total($(this).closest('table'));
    });

    // Initialize Flatpickr for expiry date fields (fallback for when not loaded in blade template)
    if (typeof flatpickr !== 'undefined') {
        $('.os_exp_date').each(function() {
            if (!this._flatpickr) {  // Check if already initialized
                flatpickr(this, {
                    dateFormat: convertDateFormatToFlatpickr(datepicker_date_format),
                    allowInput: false,
                    clickOpens: true,
                    altInput: true,
                    altFormat: datepicker_date_format.replace(/dd/g, 'd').replace(/mm/g, 'm').replace(/yyyy/g, 'Y')
                });
            }
        });
    } else {
        // Fallback to bootstrap datepicker if Flatpickr is not available
        $('.os_exp_date').datepicker({
            autoclose: true,
            format: datepicker_date_format,
        });
    }

    $(document).on('click', '.add_stock_row', function() {
        var tr = $(this).data('row-html');
        var key = parseInt($(this).data('sub-key'));
        tr = tr.replace(/\__subkey__/g, key);
        $(this).data('sub-key', key + 1);

        var $newRow = $(tr).insertAfter($(this).closest('tr'));

        // Initialize Flatpickr for expiry date in the new row
        $newRow.find('.os_exp_date').each(function() {
            if (typeof flatpickr !== 'undefined') {
                flatpickr(this, {
                    dateFormat: convertDateFormatToFlatpickr(datepicker_date_format),
                    allowInput: false,
                    clickOpens: true,
                    altInput: true,
                    altFormat: datepicker_date_format.replace(/dd/g, 'd').replace(/mm/g, 'm').replace(/yyyy/g, 'Y')
                });
            } else {
                // Fallback to bootstrap datepicker if Flatpickr is not available
                $(this).datepicker({
                    autoclose: true,
                    format: datepicker_date_format,
                });
            }
        });

        // Initialize datetimepicker for date field in the new row
        $newRow.find('.os_date').datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true,
        });
    });

    // Convert bootstrap datepicker format to Flatpickr format
    function convertDateFormatToFlatpickr(format) {
        return format.replace(/dd/g, 'd').replace(/mm/g, 'm').replace(/yyyy/g, 'Y');
    }

    // JavaScript handler removed - now using direct links in ProductController
});

// Modal code commented out since we now redirect instead of showing modal
// //Re-initialize data picker on modal opening
// $('#opening_stock_modal').on('shown.bs.modal', function(e) {
//     // Initialize Flatpickr for expiry date fields in modal
//     $('#opening_stock_modal .os_exp_date').each(function() {
//         if (typeof flatpickr !== 'undefined') {
//             flatpickr(this, {
//                 dateFormat: convertDateFormatToFlatpickr(datepicker_date_format),
//                 allowInput: false,
//                 clickOpens: true,
//                 altInput: true,
//                 altFormat: datepicker_date_format.replace(/dd/g, 'd').replace(/mm/g, 'm').replace(/yyyy/g, 'Y')
//             });
//         } else {
//             // Fallback to bootstrap datepicker if Flatpickr is not available
//             $(this).datepicker({
//                 autoclose: true,
//                 format: datepicker_date_format,
//             });
//         }
//     });
//
//     $('#opening_stock_modal .os_date').datetimepicker({
//         format: moment_date_format + ' ' + moment_time_format,
//         ignoreReadonly: true,
//         widgetPositioning: {
//             horizontal: 'right',
//             vertical: 'bottom'
//         }
//     });
// });

$(document).on('click', 'button#add_opening_stock_btn', function(e) {
    e.preventDefault();
    var btn = $(this);
    var data = $('form#add_opening_stock_form').serialize();

    $.ajax({
        method: 'POST',
        url: $('form#add_opening_stock_form').attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button(btn);
        },
        success: function(result) {
            if (result.success == true) {
                $('#opening_stock_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
    return false;
});

function update_table_total(table) {
    var total_subtotal = 0;
    table.find('tbody tr').each(function() {
        var qty = __read_number($(this).find('.purchase_quantity'));
        var unit_price = __read_number($(this).find('.unit_price'));
        var row_subtotal = qty * unit_price;
        $(this)
            .find('.row_subtotal_before_tax')
            .text(__number_f(row_subtotal));
        total_subtotal += row_subtotal;
    });
    table.find('tfoot tr #total_subtotal').text(__currency_trans_from_en(total_subtotal, true));
    table.find('tfoot tr #total_subtotal_hidden').val(total_subtotal);
}
