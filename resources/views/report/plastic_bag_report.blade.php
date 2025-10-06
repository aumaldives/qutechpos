@extends('layouts.app')
@section('title', 'Plastic Bag Report')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Plastic Bag Report</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_locations'), 'id' => 'location_filter']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('date_range_picker', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'date_range_picker', 'readonly']); !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('period_filter', 'Period:') !!}
                        {!! Form::select('period', [
                            '' => 'Custom',
                            'q1' => '1st Quarter',
                            'q2' => '2nd Quarter', 
                            'q3' => '3rd Quarter',
                            'q4' => '4th Quarter',
                            'last_year' => 'Last Year',
                            'this_year' => 'This Year'
                        ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'period_filter']); !!}
                    </div>
                </div>
                <div class="col-sm-12">
                    <button type="button" class="btn btn-primary pull-right" id="apply_filters">@lang('report.apply_filters')</button>
                    <div class="pull-right" style="margin-right: 10px;" id="export_buttons" style="display: none;">
                        <button type="button" class="btn btn-success" id="export_excel">
                            <i class="fa fa-file-excel-o"></i> Export Excel
                        </button>
                        <button type="button" class="btn btn-danger" id="export_pdf">
                            <i class="fa fa-file-pdf-o"></i> Export PDF
                        </button>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row" id="summary_section" style="display: none;">
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-shopping-bag"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Bags Sold</span>
                    <span class="info-box-number" id="total_bags">0</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-dollar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Revenue</span>
                    <span class="info-box-number" id="total_revenue">$0.00</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-list-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Transactions</span>
                    <span class="info-box-number" id="total_transactions">0</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-calculator"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Avg Bags per Sale</span>
                    <span class="info-box-number" id="avg_bags">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Location Summary -->
    <div class="row" id="location_summary_section" style="display: none;">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Location Summary'])
                <table class="table table-bordered table-striped" id="location_summary_table">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Bag Types</th>
                            <th>Total Bags</th>
                            <th>Total Charges</th>
                            <th>Transaction Count</th>
                        </tr>
                    </thead>
                    <tbody id="location_summary_body">
                    </tbody>
                </table>
            @endcomponent
        </div>
    </div>

    <!-- Detailed Transactions -->
    <div class="row" id="detailed_section" style="display: none;">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Plastic Bag Transactions'])
                <table class="table table-bordered table-striped" id="plastic_bag_transactions_table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Location</th>
                            <th>Bags Sold</th>
                            <th>Charges</th>
                        </tr>
                    </thead>
                    <tbody id="transactions_body">
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="3"><strong>Total:</strong></td>
                            <td id="footer_total_bags"><strong>0</strong></td>
                            <td id="footer_total_charges"><strong>$0.00</strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <!-- Pagination Controls -->
                <div class="row" id="pagination_section">
                    <div class="col-md-6">
                        <div class="dataTables_info" id="pagination_info">
                            <!-- Pagination info will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dataTables_paginate paging_simple_numbers" id="pagination_controls">
                            <!-- Pagination buttons will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- No Data Message -->
    <div class="row" id="no_data_section" style="display: none;">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="text-center">
                    <h4>No plastic bag sales found for the selected criteria</h4>
                    <p>Try adjusting your filters and try again.</p>
                </div>
            @endcomponent
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    // Initialize date range picker
    $('#date_range_picker').daterangepicker({
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            format: 'MM/DD/YYYY'
        }
    });

    // Period filter change handler
    $('#period_filter').on('change', function() {
        let period = $(this).val();
        let currentYear = moment().year();
        let dateRange = '';
        
        switch(period) {
            case 'q1':
                dateRange = moment([currentYear, 0, 1]).format('MM/DD/YYYY') + ' - ' + moment([currentYear, 2, 31]).format('MM/DD/YYYY');
                break;
            case 'q2':
                dateRange = moment([currentYear, 3, 1]).format('MM/DD/YYYY') + ' - ' + moment([currentYear, 5, 30]).format('MM/DD/YYYY');
                break;
            case 'q3':
                dateRange = moment([currentYear, 6, 1]).format('MM/DD/YYYY') + ' - ' + moment([currentYear, 8, 30]).format('MM/DD/YYYY');
                break;
            case 'q4':
                dateRange = moment([currentYear, 9, 1]).format('MM/DD/YYYY') + ' - ' + moment([currentYear, 11, 31]).format('MM/DD/YYYY');
                break;
            case 'last_year':
                dateRange = moment([currentYear-1, 0, 1]).format('MM/DD/YYYY') + ' - ' + moment([currentYear-1, 11, 31]).format('MM/DD/YYYY');
                break;
            case 'this_year':
                dateRange = moment([currentYear, 0, 1]).format('MM/DD/YYYY') + ' - ' + moment([currentYear, 11, 31]).format('MM/DD/YYYY');
                break;
        }
        
        if (dateRange) {
            $('#date_range_picker').val(dateRange);
        }
    });

    // Apply filters
    $('#apply_filters').on('click', function() {
        let startDate = '';
        let endDate = '';
        let locationId = $('#location_filter').val();
        
        if ($('#date_range_picker').val()) {
            let dates = $('#date_range_picker').val().split(' - ');
            startDate = moment(dates[0], 'MM/DD/YYYY').format('YYYY-MM-DD');
            endDate = moment(dates[1], 'MM/DD/YYYY').format('YYYY-MM-DD');
        }
        
        currentFilters = { startDate, endDate, locationId };
        loadPlasticBagReport(startDate, endDate, locationId, 1);
    });

    // Export handlers
    $('#export_excel').on('click', function() {
        if (currentFilters) {
            exportReport('excel');
        }
    });

    $('#export_pdf').on('click', function() {
        if (currentFilters) {
            exportReport('pdf');
        }
    });

    // Store current filters for export and pagination
    let currentFilters = null;

    function loadPlasticBagReport(startDate, endDate, locationId, page = 1) {
        $.ajax({
            url: '/reports/plastic-bag-report',
            method: 'GET',
            data: {
                start_date: startDate,
                end_date: endDate,
                location_id: locationId,
                page: page,
                per_page: 10
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                if (data.total_bags > 0) {
                    // Update summary cards
                    $('#total_bags').text(data.total_bags);
                    $('#total_revenue').html('<span class="display_currency" data-currency_symbol="true">' + parseFloat(data.total_charges).toFixed(2) + '</span>');
                    $('#total_transactions').text(data.transaction_count);
                    $('#avg_bags').text((data.total_bags / data.transaction_count).toFixed(1));
                    
                    // Show summary section
                    $('#summary_section').show();
                    
                    // Update location summary with bag types column
                    if (data.location_summary && data.location_summary.length > 0) {
                        let locationHtml = '';
                        data.location_summary.forEach(function(location) {
                            locationHtml += '<tr>';
                            locationHtml += '<td>' + location.location + '</td>';
                            locationHtml += '<td>' + (location.bag_types || '') + '</td>';
                            locationHtml += '<td>' + location.total_bags + '</td>';
                            locationHtml += '<td><span class="display_currency" data-currency_symbol="true">' + parseFloat(location.total_charges).toFixed(2) + '</span></td>';
                            locationHtml += '<td>' + location.transaction_count + '</td>';
                            locationHtml += '</tr>';
                        });
                        $('#location_summary_body').html(locationHtml);
                        $('#location_summary_section').show();
                    } else {
                        $('#location_summary_section').hide();
                    }
                    
                    // Update transactions table
                    let transactionHtml = '';
                    let totalBags = 0;
                    let totalCharges = 0;
                    
                    data.transactions.forEach(function(transaction) {
                        transactionHtml += '<tr>';
                        transactionHtml += '<td>' + transaction.date + '</td>';
                        transactionHtml += '<td>' + transaction.invoice_no + '</td>';
                        transactionHtml += '<td>' + transaction.location + '</td>';
                        transactionHtml += '<td>' + transaction.bags + '</td>';
                        transactionHtml += '<td><span class="display_currency" data-currency_symbol="true">' + parseFloat(transaction.charges).toFixed(2) + '</span></td>';
                        transactionHtml += '</tr>';
                        
                        totalBags += parseInt(transaction.bags);
                        totalCharges += parseFloat(transaction.charges);
                    });
                    
                    $('#transactions_body').html(transactionHtml);
                    $('#footer_total_bags').html('<strong>' + totalBags + '</strong>');
                    $('#footer_total_charges').html('<strong><span class="display_currency" data-currency_symbol="true">' + totalCharges.toFixed(2) + '</span></strong>');
                    
                    // Update pagination
                    if (data.pagination) {
                        updatePagination(data.pagination);
                    }
                    
                    $('#detailed_section').show();
                    $('#no_data_section').hide();
                    $('#export_buttons').show();
                    
                    // Convert currencies
                    __currency_convert_recursively($('body'));
                } else {
                    // Hide all sections and show no data message
                    $('#summary_section, #location_summary_section, #detailed_section, #export_buttons').hide();
                    $('#no_data_section').show();
                }
            },
            error: function() {
                toastr.error('Error loading plastic bag report');
            }
        });
    }

    function updatePagination(pagination) {
        // Update info
        let start = ((pagination.current_page - 1) * pagination.per_page) + 1;
        let end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
        $('#pagination_info').html(`Showing ${start} to ${end} of ${pagination.total} entries`);
        
        // Update controls
        let controls = '';
        
        // Previous button
        if (pagination.current_page > 1) {
            controls += `<a href="#" class="paginate_button previous" data-page="${pagination.current_page - 1}">Previous</a>`;
        } else {
            controls += '<span class="paginate_button previous disabled">Previous</span>';
        }
        
        // Page numbers
        let startPage = Math.max(1, pagination.current_page - 2);
        let endPage = Math.min(pagination.last_page, pagination.current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.current_page) {
                controls += `<span class="paginate_button current">${i}</span>`;
            } else {
                controls += `<a href="#" class="paginate_button" data-page="${i}">${i}</a>`;
            }
        }
        
        // Next button
        if (pagination.current_page < pagination.last_page) {
            controls += `<a href="#" class="paginate_button next" data-page="${pagination.current_page + 1}">Next</a>`;
        } else {
            controls += '<span class="paginate_button next disabled">Next</span>';
        }
        
        $('#pagination_controls').html(controls);
        
        // Bind pagination events
        $('.paginate_button:not(.disabled):not(.current)').on('click', function(e) {
            e.preventDefault();
            let page = $(this).data('page');
            if (page && currentFilters) {
                loadPlasticBagReport(currentFilters.startDate, currentFilters.endDate, currentFilters.locationId, page);
            }
        });
    }

    function exportReport(format) {
        window.location.href = '/reports/plastic-bag-report?' + 
            'start_date=' + currentFilters.startDate + 
            '&end_date=' + currentFilters.endDate + 
            '&location_id=' + (currentFilters.locationId || '') + 
            '&export=' + format;
    }
});
</script>
@endsection