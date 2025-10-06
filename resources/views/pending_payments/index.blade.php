@extends('layouts.app')

@section('title', __('lang_v1.pending_bank_payments'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.pending_bank_payments')
        <small>@lang('lang_v1.manage_pending_bank_payments')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">@lang('lang_v1.all_pending_bank_payments')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="status_filter">@lang('lang_v1.status'):</label>
                        <select class="form-control" id="status_filter">
                            <option value="">@lang('lang_v1.all')</option>
                            <option value="pending" selected>@lang('lang_v1.pending')</option>
                            <option value="processed">@lang('lang_v1.processed')</option>
                            <option value="rejected">@lang('lang_v1.rejected')</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="pending_payments_table">
                    <thead>
                        <tr>
                            <th class="text-center">@lang('lang_v1.invoice')</th>
                            <th class="text-center">@lang('lang_v1.customer')</th>
                            <th class="text-center">@lang('lang_v1.bank_info')</th>
                            <th class="text-center">@lang('lang_v1.amount')</th>
                            <th class="text-center">@lang('lang_v1.status')</th>
                            <th class="text-center">@lang('lang_v1.submitted_at')</th>
                            <th class="text-center">@lang('lang_v1.actions')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Details Modal -->
    <div class="modal fade" id="payment_details_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">@lang('lang_v1.payment_details')</h4>
                </div>
                <div class="modal-body" id="payment_details_content">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-3x"></i>
                        <p>@lang('lang_v1.loading')</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('lang_v1.close')</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div class="modal fade" id="rejection_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">@lang('lang_v1.reject_payment')</h4>
                </div>
                <div class="modal-body">
                    <form id="rejection_form">
                        <div class="form-group">
                            <label for="rejection_reason">@lang('lang_v1.rejection_reason') <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required placeholder="@lang('lang_v1.enter_rejection_reason')"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('lang_v1.cancel')</button>
                    <button type="button" class="btn btn-danger" id="confirm_rejection">@lang('lang_v1.reject_payment')</button>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#pending_payments_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('pending-payments.index') }}",
            data: function(d) {
                d.status = $('#status_filter').val();
            }
        },
        columns: [
            {data: 'invoice_info', name: 'invoice_no', orderable: false, searchable: true, className: 'text-center'},
            {data: 'customer_name', name: 'pbp.customer_name', orderable: false, searchable: true, className: 'text-center'},
            {data: 'bank_info', name: 'bank_name', orderable: false, searchable: false, className: 'text-center'},
            {data: 'amount_formatted', name: 'amount', searchable: false, className: 'text-center'},
            {data: 'status_formatted', name: 'status', searchable: false, className: 'text-center'},
            {data: 'submitted_formatted', name: 'submitted_at', searchable: false, className: 'text-center'},
            {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
        ],
        order: [[5, 'desc']],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#pending_payments_table'));
        }
    });

    // Status filter change event
    $('#status_filter').change(function() {
        table.ajax.reload();
    });

    // View payment details
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();
        var payment_id = $(this).data('id');
        
        $('#payment_details_modal').modal('show');
        $('#payment_details_content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>@lang("lang_v1.loading")</p></div>');
        
        $.get("{{ route('pending-payments.show', ':id') }}".replace(':id', payment_id))
        .done(function(data) {
            if (data.success) {
                var payment = data.payment;
                var content = '<div class="row">';
                
                content += '<div class="col-md-6">';
                content += '<h4>@lang("lang_v1.payment_information")</h4>';
                content += '<table class="table table-condensed">';
                // Show invoice numbers - single or multiple
                if (payment.payment_type === 'multi' && payment.invoice_numbers) {
                    content += '<tr><td><strong>@lang("lang_v1.invoices"):</strong></td><td>' + payment.invoice_numbers + ' (' + payment.invoice_count + ' invoices)</td></tr>';
                } else {
                    content += '<tr><td><strong>@lang("lang_v1.invoice"):</strong></td><td>' + (payment.invoice_no || 'N/A') + '</td></tr>';
                }
                
                content += '<tr><td><strong>@lang("lang_v1.amount"):</strong></td><td>' + __currency_trans_from_en(payment.amount, true) + '</td></tr>';
                content += '<tr><td><strong>@lang("lang_v1.status"):</strong></td><td><span class="label label-' + getStatusColor(payment.status) + '">' + payment.status.charAt(0).toUpperCase() + payment.status.slice(1) + '</span></td></tr>';
                content += '<tr><td><strong>@lang("lang_v1.submitted_at"):</strong></td><td>' + moment(payment.submitted_at).format('MMM DD, YYYY h:mm A') + '</td></tr>';
                
                if (payment.processed_at) {
                    content += '<tr><td><strong>@lang("lang_v1.processed_at"):</strong></td><td>' + moment(payment.processed_at).format('MMM DD, YYYY h:mm A') + '</td></tr>';
                }
                
                if (payment.processed_by_name) {
                    content += '<tr><td><strong>@lang("lang_v1.processed_by"):</strong></td><td>' + payment.processed_by_name + '</td></tr>';
                }
                
                if (payment.rejection_reason) {
                    content += '<tr><td><strong>@lang("lang_v1.rejection_reason"):</strong></td><td>' + payment.rejection_reason + '</td></tr>';
                }
                
                content += '</table>';
                content += '</div>';
                
                content += '<div class="col-md-6">';
                content += '<h4>@lang("lang_v1.bank_information")</h4>';
                content += '<table class="table table-condensed">';
                
                // Show bank with logo
                content += '<tr><td><strong>@lang("lang_v1.bank"):</strong></td><td>';
                if (payment.bank_logo) {
                    content += '<img src="' + payment.bank_logo + '" alt="' + payment.bank_name + '" style="height: 30px; margin-right: 10px; vertical-align: middle;">';
                }
                content += payment.bank_name + '</td></tr>';
                
                content += '<tr><td><strong>@lang("lang_v1.account_name"):</strong></td><td>' + payment.account_name + '</td></tr>';
                content += '<tr><td><strong>@lang("lang_v1.account_number"):</strong></td><td>' + payment.account_number + '</td></tr>';
                
                if (payment.receipt_file_path) {
                    // Build the correct URL based on the file path
                    var receiptUrl;
                    if (payment.receipt_file_path.startsWith('uploads/')) {
                        receiptUrl = "{{ asset('') }}" + payment.receipt_file_path;
                    } else if (payment.receipt_file_path.startsWith('public_payments/')) {
                        receiptUrl = "{{ asset('') }}" + payment.receipt_file_path;
                    } else {
                        receiptUrl = "{{ asset('storage') }}" + '/' + payment.receipt_file_path;
                    }
                    content += '<tr><td><strong>@lang("lang_v1.receipt"):</strong></td><td><a href="' + receiptUrl + '" target="_blank" class="btn btn-xs btn-primary"><i class="fa fa-file"></i> @lang("lang_v1.view_receipt")</a></td></tr>';
                }
                
                content += '</table>';
                content += '</div>';
                content += '</div>';
                
                // Show invoice allocation details for multi-invoice payments
                if (payment.payment_type === 'multi' && data.allocations && data.allocations.length > 0) {
                    content += '<div class="row" style="margin-top: 20px;">';
                    content += '<div class="col-md-12">';
                    content += '<h4>@lang("lang_v1.invoice_allocation")</h4>';
                    content += '<table class="table table-bordered table-condensed">';
                    content += '<thead><tr>';
                    content += '<th>@lang("lang_v1.invoice")</th>';
                    content += '<th>@lang("lang_v1.date")</th>';
                    content += '<th>@lang("lang_v1.total_amount")</th>';
                    content += '<th>@lang("lang_v1.paid_amount")</th>';
                    content += '<th>@lang("lang_v1.due_amount")</th>';
                    content += '<th>@lang("lang_v1.applied_amount")</th>';
                    content += '</tr></thead><tbody>';
                    
                    data.allocations.forEach(function(allocation) {
                        content += '<tr>';
                        content += '<td>' + allocation.invoice_no + '</td>';
                        content += '<td>' + moment(allocation.transaction_date).format('MMM DD, YYYY') + '</td>';
                        content += '<td>' + __currency_trans_from_en(allocation.final_total, true) + '</td>';
                        content += '<td>' + __currency_trans_from_en(allocation.total_paid, true) + '</td>';
                        content += '<td>' + __currency_trans_from_en(allocation.due_amount, true) + '</td>';
                        content += '<td class="text-success"><strong>' + __currency_trans_from_en(allocation.applied_amount, true) + '</strong></td>';
                        content += '</tr>';
                    });
                    
                    content += '</tbody></table>';
                    content += '</div>';
                    content += '</div>';
                }
                
                $('#payment_details_content').html(content);
            } else {
                $('#payment_details_content').html('<div class="alert alert-danger">' + data.msg + '</div>');
            }
        })
        .fail(function() {
            $('#payment_details_content').html('<div class="alert alert-danger">@lang("lang_v1.something_went_wrong")</div>');
        });
    });

    // Approve payment
    $(document).on('click', '.approve-payment', function(e) {
        e.preventDefault();
        var payment_id = $(this).data('id');
        
        swal({
            title: "@lang('lang_v1.are_you_sure')",
            text: "@lang('lang_v1.approve_payment_confirmation')",
            icon: "warning",
            buttons: true,
            dangerMode: false,
        })
        .then((willApprove) => {
            if (willApprove) {
                $.post("{{ route('pending-payments.approve', ':id') }}".replace(':id', payment_id))
                .done(function(data) {
                    if (data.success) {
                        toastr.success(data.msg);
                        table.ajax.reload();
                    } else {
                        toastr.error(data.msg);
                    }
                })
                .fail(function() {
                    toastr.error('@lang("lang_v1.something_went_wrong")');
                });
            }
        });
    });

    // Show rejection modal
    $(document).on('click', '.reject-payment', function(e) {
        e.preventDefault();
        var payment_id = $(this).data('id');
        $('#rejection_modal').data('payment-id', payment_id).modal('show');
        $('#rejection_reason').val('');
    });

    // Confirm rejection
    $('#confirm_rejection').click(function() {
        var payment_id = $('#rejection_modal').data('payment-id');
        var rejection_reason = $('#rejection_reason').val().trim();
        
        if (rejection_reason === '') {
            toastr.error('@lang("lang_v1.rejection_reason_required")');
            return;
        }
        
        $.post("{{ route('pending-payments.reject', ':id') }}".replace(':id', payment_id), {
            rejection_reason: rejection_reason,
            _token: '{{ csrf_token() }}'
        })
        .done(function(data) {
            if (data.success) {
                toastr.success(data.msg);
                $('#rejection_modal').modal('hide');
                table.ajax.reload();
            } else {
                toastr.error(data.msg);
            }
        })
        .fail(function() {
            toastr.error('@lang("lang_v1.something_went_wrong")');
        });
    });

    // View receipt image popup
    $(document).on('click', '.view-receipt-image', function(e) {
        e.preventDefault();
        var imageUrl = $(this).data('image');
        
        // Create image modal
        var modal = $('<div class="modal fade" id="receipt-image-modal" tabindex="-1" role="dialog">' +
            '<div class="modal-dialog modal-lg" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                            '<span aria-hidden="true">&times;</span>' +
                        '</button>' +
                        '<h4 class="modal-title">@lang("lang_v1.view_receipt")</h4>' +
                    '</div>' +
                    '<div class="modal-body text-center">' +
                        '<img src="' + imageUrl + '" class="img-responsive center-block" style="max-width: 100%; height: auto; display: block; margin: 0 auto;" />' +
                    '</div>' +
                    '<div class="modal-footer">' +
                        '<a href="' + imageUrl + '" target="_blank" class="btn btn-primary">@lang("lang_v1.open_in_new_tab")</a>' +
                        '<button type="button" class="btn btn-default" data-dismiss="modal">@lang("lang_v1.close")</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        // Remove existing modal if any
        $('#receipt-image-modal').remove();
        
        // Add modal to body and show
        $('body').append(modal);
        $('#receipt-image-modal').modal('show');
        
        // Remove modal when hidden
        $('#receipt-image-modal').on('hidden.bs.modal', function() {
            $(this).remove();
        });
    });

    // Multi-invoice payment handlers
    
    // View multi-invoice payment details
    $(document).on('click', '.view-multi-details', function(e) {
        e.preventDefault();
        var payment_id = $(this).data('id');
        
        $('#payment_details_modal').modal('show');
        $('#payment_details_content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>@lang("lang_v1.loading")</p></div>');
        
        $.get("{{ route('pending-payments.show-multi', ':id') }}".replace(':id', payment_id))
        .done(function(data) {
            if (data.success) {
                var payment = data.payment;
                var content = '<div class="row">';
                
                content += '<div class="col-md-6">';
                content += '<h4>@lang("lang_v1.payment_information")</h4>';
                content += '<table class="table table-condensed">';
                
                // Show invoice numbers for multi-invoice payments
                if (payment.invoice_numbers) {
                    content += '<tr><td><strong>@lang("lang_v1.invoices"):</strong></td><td>' + payment.invoice_numbers + '</td></tr>';
                } else {
                    content += '<tr><td><strong>@lang("lang_v1.invoices"):</strong></td><td>Multiple invoices</td></tr>';
                }
                
                content += '<tr><td><strong>@lang("lang_v1.amount"):</strong></td><td>' + __currency_trans_from_en(payment.total_amount, true) + '</td></tr>';
                content += '<tr><td><strong>@lang("lang_v1.status"):</strong></td><td><span class="label label-' + getStatusColor(payment.status) + '">' + payment.status.charAt(0).toUpperCase() + payment.status.slice(1) + '</span></td></tr>';
                content += '<tr><td><strong>@lang("lang_v1.submitted_at"):</strong></td><td>' + moment(payment.submitted_at).format('MMM DD, YYYY h:mm A') + '</td></tr>';
                
                if (payment.processed_at) {
                    content += '<tr><td><strong>@lang("lang_v1.processed_at"):</strong></td><td>' + moment(payment.processed_at).format('MMM DD, YYYY h:mm A') + '</td></tr>';
                }
                
                if (payment.processed_by_name) {
                    content += '<tr><td><strong>@lang("lang_v1.processed_by"):</strong></td><td>' + payment.processed_by_name + '</td></tr>';
                }
                
                if (payment.rejection_reason) {
                    content += '<tr><td><strong>@lang("lang_v1.rejection_reason"):</strong></td><td>' + payment.rejection_reason + '</td></tr>';
                }
                
                content += '</table>';
                content += '</div>';
                
                content += '<div class="col-md-6">';
                content += '<h4>@lang("lang_v1.bank_information")</h4>';
                content += '<table class="table table-condensed">';
                
                // Show bank with logo
                content += '<tr><td><strong>@lang("lang_v1.bank"):</strong></td><td>';
                if (payment.bank_logo) {
                    content += '<img src="' + payment.bank_logo + '" alt="' + payment.bank_name + '" style="height: 30px; margin-right: 10px; vertical-align: middle;">';
                }
                content += (payment.bank_name || 'N/A') + '</td></tr>';
                
                content += '<tr><td><strong>@lang("lang_v1.account_name"):</strong></td><td>' + (payment.account_name || 'N/A') + '</td></tr>';
                content += '<tr><td><strong>@lang("lang_v1.account_number"):</strong></td><td>' + (payment.account_number || 'N/A') + '</td></tr>';
                
                if (payment.receipt_file_path) {
                    // Build the correct URL based on the file path
                    var receiptUrl;
                    if (payment.receipt_file_path.startsWith('uploads/')) {
                        receiptUrl = "{{ asset('') }}" + payment.receipt_file_path;
                    } else if (payment.receipt_file_path.startsWith('public_payments/')) {
                        receiptUrl = "{{ asset('') }}" + payment.receipt_file_path;
                    } else {
                        receiptUrl = "{{ asset('storage') }}" + '/' + payment.receipt_file_path;
                    }
                    content += '<tr><td><strong>@lang("lang_v1.receipt"):</strong></td><td><a href="' + receiptUrl + '" target="_blank" class="btn btn-xs btn-primary"><i class="fa fa-file"></i> @lang("lang_v1.view_receipt")</a></td></tr>';
                }
                
                content += '</table>';
                content += '</div>';
                content += '</div>';
                
                // Show invoice allocation details
                if (data.allocations && data.allocations.length > 0) {
                    content += '<div class="row" style="margin-top: 20px;">';
                    content += '<div class="col-md-12">';
                    content += '<h4>@lang("lang_v1.invoice_allocation")</h4>';
                    content += '<table class="table table-bordered table-condensed">';
                    content += '<thead><tr>';
                    content += '<th>@lang("lang_v1.invoice")</th>';
                    content += '<th>@lang("lang_v1.date")</th>';
                    content += '<th>@lang("lang_v1.total_amount")</th>';
                    content += '<th>@lang("lang_v1.paid_amount")</th>';
                    content += '<th>@lang("lang_v1.due_amount")</th>';
                    content += '<th>@lang("lang_v1.applied_amount")</th>';
                    content += '</tr></thead><tbody>';
                    
                    data.allocations.forEach(function(allocation) {
                        content += '<tr>';
                        content += '<td>' + allocation.invoice_no + '</td>';
                        content += '<td>' + moment(allocation.transaction_date).format('MMM DD, YYYY') + '</td>';
                        content += '<td>' + __currency_trans_from_en(allocation.final_total, true) + '</td>';
                        content += '<td>' + __currency_trans_from_en(allocation.total_paid, true) + '</td>';
                        content += '<td>' + __currency_trans_from_en(allocation.due_amount, true) + '</td>';
                        content += '<td class="text-success"><strong>' + __currency_trans_from_en(allocation.applied_amount, true) + '</strong></td>';
                        content += '</tr>';
                    });
                    
                    content += '</tbody></table>';
                    content += '</div>';
                    content += '</div>';
                }
                
                $('#payment_details_content').html(content);
            } else {
                $('#payment_details_content').html('<div class="alert alert-danger">' + data.msg + '</div>');
            }
        })
        .fail(function() {
            $('#payment_details_content').html('<div class="alert alert-danger">@lang("lang_v1.something_went_wrong")</div>');
        });
    });

    // Approve multi-invoice payment
    $(document).on('click', '.approve-multi-payment', function(e) {
        e.preventDefault();
        var payment_id = $(this).data('id');
        
        swal({
            title: "@lang('lang_v1.are_you_sure')",
            text: "This will approve the multi-invoice payment and create transaction payment records. Are you sure?",
            icon: "warning",
            buttons: true,
            dangerMode: false,
        })
        .then((willApprove) => {
            if (willApprove) {
                $.post("{{ route('pending-payments.approve-multi', ':id') }}".replace(':id', payment_id))
                .done(function(data) {
                    if (data.success) {
                        toastr.success(data.msg);
                        table.ajax.reload();
                    } else {
                        toastr.error(data.msg);
                    }
                })
                .fail(function() {
                    toastr.error('@lang("lang_v1.something_went_wrong")');
                });
            }
        });
    });

    // Reject multi-invoice payment
    $(document).on('click', '.reject-multi-payment', function(e) {
        e.preventDefault();
        var payment_id = $(this).data('id');
        $('#rejection_modal').data('payment-id', payment_id).data('payment-type', 'multi').modal('show');
        $('#rejection_reason').val('');
    });

    // Update the rejection confirmation to handle both single and multi
    $('#confirm_rejection').off('click').on('click', function() {
        var payment_id = $('#rejection_modal').data('payment-id');
        var payment_type = $('#rejection_modal').data('payment-type') || 'single';
        var rejection_reason = $('#rejection_reason').val().trim();
        
        if (rejection_reason === '') {
            toastr.error('@lang("lang_v1.rejection_reason_required")');
            return;
        }
        
        var url = payment_type === 'multi' 
            ? "{{ route('pending-payments.reject-multi', ':id') }}".replace(':id', payment_id)
            : "{{ route('pending-payments.reject', ':id') }}".replace(':id', payment_id);
        
        $.post(url, {
            rejection_reason: rejection_reason,
            _token: '{{ csrf_token() }}'
        })
        .done(function(data) {
            if (data.success) {
                toastr.success(data.msg);
                $('#rejection_modal').modal('hide');
                table.ajax.reload();
            } else {
                toastr.error(data.msg);
            }
        })
        .fail(function() {
            toastr.error('@lang("lang_v1.something_went_wrong")');
        });
    });

    // Helper function to get status color
    function getStatusColor(status) {
        var colors = {
            'pending': 'warning',
            'approved': 'info',
            'processed': 'success',
            'rejected': 'danger'
        };
        return colors[status] || 'default';
    }
});
</script>
@endsection