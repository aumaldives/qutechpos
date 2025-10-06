@extends('layouts.app')
@section('title', 'Public Ledger - Format 4 (Consolidated Invoices)')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Public Ledger Management - Format 4
        <small>Consolidated Invoices with Date Range Filter</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{route('home')}}"><i class="fa fa-dashboard"></i> @lang('lang_v1.home')</a></li>
        <li class="active">Public Ledger Format 4</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget')
        @slot('icon')
            fa fa-link
        @endslot
        @slot('title')
            Public Ledger Links - Format 4 (Consolidated Invoices)
        @endslot

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
                    <button type="button" class="btn btn-success" id="preview_data">
                        <i class="fa fa-eye"></i> Preview Data
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="format4_links_table">
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
                    <h4 class="modal-title">Format 4 Data Preview - Consolidated Invoices</h4>
                </div>
                <div class="modal-body">
                    <div id="preview_content">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('preview_start_date', 'Start Date:') !!}
                                    {!! Form::text('preview_start_date', null, ['class' => 'form-control', 'id' => 'preview_start_date', 'placeholder' => 'Select start date...']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('preview_end_date', 'End Date:') !!}
                                    {!! Form::text('preview_end_date', null, ['class' => 'form-control', 'id' => 'preview_end_date', 'placeholder' => 'Select end date...']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    {!! Form::label('preview_location', 'Location:') !!}
                                    {!! Form::select('preview_location', $business_locations, null, ['class' => 'form-control', 'id' => 'preview_location']) !!}
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-primary" id="load_preview">
                                        <i class="fa fa-refresh"></i> Load Preview
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="preview_results" style="display: none;">
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-condensed">
                                        <tr>
                                            <td><strong>Contact:</strong></td>
                                            <td id="preview_contact_name">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date Range:</strong></td>
                                            <td id="preview_date_range">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Invoices:</strong></td>
                                            <td id="preview_total_invoices">-</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-condensed">
                                        <tr>
                                            <td><strong>Grand Total:</strong></td>
                                            <td id="preview_grand_total">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total Tax:</strong></td>
                                            <td id="preview_total_tax">-</td>
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
    // Initialize date pickers
    $('#preview_start_date, #preview_end_date').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
    });

    // Initialize DataTable
    var format4_table = $('#format4_links_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.public-ledger.format4') }}",
            data: function (d) {
                d.contact_id = $('#contact_filter').val();
                d.location_id = $('#location_filter').val();
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
        format4_table.draw();
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
                    format4_table.draw();
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
                        format4_table.draw();
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
            url: "{{ route('admin.public-ledger.format4-preview') }}",
            type: 'GET',
            data: {
                contact_id: contact_id,
                start_date: $('#preview_start_date').val(),
                end_date: $('#preview_end_date').val(),
                location_id: $('#preview_location').val()
            },
            success: function(response) {
                if (response.error) {
                    toastr.error(response.error);
                } else {
                    $('#preview_contact_name').text(response.contact_name);
                    $('#preview_date_range').text(response.date_range);
                    $('#preview_total_invoices').text(response.total_invoices);
                    $('#preview_grand_total').text(__currency_trans_from_en(response.grand_total, true));
                    $('#preview_total_tax').text(__currency_trans_from_en(response.total_tax, true));
                    $('#preview_results').show();
                }
            }
        });
    });
});
</script>
@endsection