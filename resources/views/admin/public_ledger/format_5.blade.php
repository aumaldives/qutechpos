@extends('layouts.app')
@section('title', 'Public Ledger - Format 5 (Due Invoices Only)')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Public Ledger Management - Format 5
        <small>Due Invoices Only (No Date Filter)</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> @lang('lang_v1.home')</a></li>
        <li class="active">Public Ledger Format 5</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget')
        @slot('icon')
            fa fa-exclamation-triangle
        @endslot
        @slot('title')
            Public Ledger Links - Format 5 (Due Invoices Only)
        @endslot

        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>Note:</strong> Format 5 shows only due invoices (unpaid or partially paid) with no date filter applied.
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('contact_filter', 'Contact:') !!}
                    {!! Form::select('contact_filter', $contacts, null, ['class' => 'form-control select2', 'id' => 'contact_filter', 'placeholder' => 'Select Contact...']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('location_filter', 'Business Location:') !!}
                    {!! Form::select('location_filter', $business_locations, null, ['class' => 'form-control select2', 'id' => 'location_filter']) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label><br>
                    <button type="button" class="btn btn-primary" id="filter_links">
                        <i class="fa fa-search"></i> Filter Links
                    </button>
                    <button type="button" class="btn btn-warning" id="preview_data">
                        <i class="fa fa-eye"></i> Preview Due Amounts
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="format5_links_table">
                        <thead>
                            <tr>
                                <th>Contact</th>
                                <th>Token</th>
                                <th>Public URL</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    @endcomponent

    <!-- Preview Modal -->
    <div class="modal fade" id="preview_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Format 5 Data Preview - Due Invoices Only</h4>
                </div>
                <div class="modal-body">
                    <div id="preview_content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('preview_location', 'Location Filter:') !!}
                                    {!! Form::select('preview_location', $business_locations, null, ['class' => 'form-control', 'id' => 'preview_location']) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-warning" id="load_preview">
                                        <i class="fa fa-refresh"></i> Load Due Invoices Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="preview_results" style="display: none;">
                            <hr>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> <strong>Due Invoices Summary</strong>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-condensed">
                                        <tr>
                                            <td><strong>Contact:</strong></td>
                                            <td id="preview_contact_name">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Due Invoices:</strong></td>
                                            <td id="preview_total_due_invoices" class="text-danger">-</td>
                                        </tr>
                                        <tr class="bg-danger">
                                            <td><strong>Total Amount Due:</strong></td>
                                            <td id="preview_total_amount_due" class="text-danger"><strong>-</strong></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-condensed">
                                        <tr>
                                            <td><strong>Total Invoice Amount:</strong></td>
                                            <td id="preview_total_invoice_amount">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Paid Amount:</strong></td>
                                            <td id="preview_total_paid_amount" class="text-success">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Payment Completion:</strong></td>
                                            <td id="preview_payment_percentage">-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize DataTable
    var format5_table = $('#format5_links_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.public-ledger.format5') }}",
            data: function (d) {
                d.contact_id = $('#contact_filter').val();
            }
        },
        columns: [
            {data: 'contact_name', name: 'c.name'},
            {data: 'token', name: 'pll.token', 
                render: function(data, type, row) {
                    return data.substring(0, 10) + '...';
                }
            },
            {data: 'public_url', name: 'public_url', 
                render: function(data, type, row) {
                    return '<a href="' + data + '" target="_blank" class="btn btn-xs btn-link"><i class="fa fa-external-link"></i> View</a>';
                }
            },
            {data: 'status', name: 'pll.is_active'},
            {data: 'created_at', name: 'pll.created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    // Filter links
    $('#filter_links').click(function() {
        format5_table.draw();
    });

    // Copy link to clipboard
    $(document).on('click', '.copy-link', function() {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('Link copied to clipboard!');
        });
    });

    // Toggle status
    $(document).on('click', '.toggle-status', function() {
        var id = $(this).data('id');
        var status = $(this).data('status');
        
        $.ajax({
            url: "{{ route('admin.public-ledger.toggle-status') }}",
            type: 'POST',
            data: {
                id: id,
                status: status,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    format5_table.draw();
                } else {
                    toastr.error(response.msg);
                }
            }
        });
    });

    // Delete link
    $(document).on('click', '.delete-link', function() {
        var id = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this public link?')) {
            $.ajax({
                url: "{{ route('admin.public-ledger.delete-link') }}",
                type: 'DELETE',
                data: {
                    id: id,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        format5_table.draw();
                    } else {
                        toastr.error(response.msg);
                    }
                }
            });
        }
    });

    // Preview data
    $('#preview_data').click(function() {
        $('#preview_modal').modal('show');
    });

    // Load preview
    $('#load_preview').click(function() {
        var contact_id = $('#contact_filter').val();
        if (!contact_id) {
            toastr.error('Please select a contact first');
            return;
        }

        $.ajax({
            url: "{{ route('admin.public-ledger.format5-preview') }}",
            type: 'GET',
            data: {
                contact_id: contact_id,
                location_id: $('#preview_location').val()
            },
            success: function(response) {
                if (response.error) {
                    toastr.error(response.error);
                } else {
                    $('#preview_contact_name').text(response.contact_name);
                    $('#preview_total_due_invoices').text(response.total_due_invoices);
                    $('#preview_total_amount_due').text(__currency_trans_from_en(response.total_amount_due, true));
                    $('#preview_total_invoice_amount').text(__currency_trans_from_en(response.total_invoice_amount, true));
                    $('#preview_total_paid_amount').text(__currency_trans_from_en(response.total_paid_amount, true));
                    
                    // Calculate payment percentage
                    var percentage = response.total_invoice_amount > 0 ? 
                        ((response.total_paid_amount / response.total_invoice_amount) * 100).toFixed(1) : 0;
                    $('#preview_payment_percentage').text(percentage + '%');
                    
                    $('#preview_results').show();
                }
            }
        });
    });
});
</script>
@endsection