@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Transaction Reports')

@section('content')
@include('superadmin::layouts.nav')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        <i class="fas fa-file-invoice-dollar"></i> Transaction Reports
        <small>Subscription payment transactions with MVR conversion</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> Report Filters & Options
                    </h3>
                </div>
                <div class="box-body">
                    <form id="reportForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="report_type">Report Type:</label>
                                    <select id="report_type" name="type" class="form-control">
                                        <option value="quarterly">Current Quarter</option>
                                        <option value="q1">Quarter 1 (Jan-Mar)</option>
                                        <option value="q2">Quarter 2 (Apr-Jun)</option>
                                        <option value="q3">Quarter 3 (Jul-Sep)</option>
                                        <option value="q4">Quarter 4 (Oct-Dec)</option>
                                        <option value="custom">Custom Date Range</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="report_year">Year:</label>
                                    <select id="report_year" name="year" class="form-control">
                                        @for ($year = date('Y'); $year >= 2020; $year--)
                                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-3" id="custom_start_date" style="display: none;">
                                <div class="form-group">
                                    <label for="start_date">Start Date:</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-3" id="custom_end_date" style="display: none;">
                                <div class="form-group">
                                    <label for="end_date">End Date:</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" id="loadData" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Load Data
                                </button>
                                <button type="button" id="exportReport" class="btn btn-success">
                                    <i class="fa fa-download"></i> Export to Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Panel -->
    <div class="row" id="statsPanel" style="display: none;">
        <div class="col-md-3">
            <div class="small-box bg-blue">
                <div class="inner">
                    <h3 id="totalTransactions">0</h3>
                    <p>Total Transactions</p>
                </div>
                <div class="icon">
                    <i class="fa fa-list"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="totalRevenue">0.00</h3>
                    <p>Total Revenue (MVR)</p>
                </div>
                <div class="icon">
                    <i class="fa fa-money"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="avgTransaction">0.00</h3>
                    <p>Average Transaction (MVR)</p>
                </div>
                <div class="icon">
                    <i class="fa fa-calculator"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="reportPeriod">-</h3>
                    <p>Report Period</p>
                </div>
                <div class="icon">
                    <i class="fa fa-calendar"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row" id="dataTablePanel" style="display: none;">
        <div class="col-xs-12">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-table"></i> Transaction Data Preview
                    </h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table id="transactionTable" class="table table-striped table-bordered">
                            <thead>
                                <tr class="bg-primary">
                                    <th>Customer TIN</th>
                                    <th>Customer Name</th>
                                    <th>Invoice No</th>
                                    <th>Invoice Date</th>
                                    <th>Payment Amount (MVR)</th>
                                    <th>Zero-Rated Supplies</th>
                                </tr>
                            </thead>
                            <tbody id="transactionTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@section('javascript')
<script>
// Ensure reports work with or without jQuery
(function() {
    function initializeTransactionReports() {
    // Handle report type change
    $('#report_type').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#custom_start_date, #custom_end_date').show();
            $('#report_year').closest('.col-md-3').hide();
        } else {
            $('#custom_start_date, #custom_end_date').hide();
            $('#report_year').closest('.col-md-3').show();
        }
    });

    // Load data
    $('#loadData').on('click', function() {
        var formData = $('#reportForm').serialize();
        
        $('#loadData').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
        
        $.ajax({
            url: '{{ route("superadmin.reports.transaction.data") }}',
            type: 'GET',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Update statistics
                    $('#totalTransactions').text(response.stats.total_transactions);
                    $('#totalRevenue').text(response.stats.total_revenue);
                    $('#avgTransaction').text(response.stats.average_transaction);
                    $('#reportPeriod').text(response.stats.period);
                    
                    // Show statistics panel
                    $('#statsPanel').show();
                    
                    // Populate table
                    var tableBody = $('#transactionTableBody');
                    tableBody.empty();
                    
                    if (response.data.length > 0) {
                        $.each(response.data, function(index, row) {
                            tableBody.append(
                                '<tr>' +
                                '<td>' + row.customer_tin + '</td>' +
                                '<td>' + row.customer_name + '</td>' +
                                '<td>' + row.invoice_no + '</td>' +
                                '<td>' + row.invoice_date + '</td>' +
                                '<td class="text-right">' + row.payment_amount + '</td>' +
                                '<td class="text-center">' + row.zero_rated + '</td>' +
                                '</tr>'
                            );
                        });
                        $('#dataTablePanel').show();
                    } else {
                        tableBody.append('<tr><td colspan="6" class="text-center">No transactions found for the selected period</td></tr>');
                        $('#dataTablePanel').show();
                    }
                }
            },
            error: function() {
                alert('Error loading data. Please try again.');
            },
            complete: function() {
                $('#loadData').prop('disabled', false).html('<i class="fa fa-search"></i> Load Data');
            }
        });
    });

    // Export report
    $('#exportReport').on('click', function() {
        var formData = $('#reportForm').serialize();
        window.open('{{ route("superadmin.reports.transaction.export") }}?' + formData, '_blank');
    });

        // Load current quarter data by default
        $('#loadData').click();
    }

    // Initialize when DOM is ready
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initializeTransactionReports);
    } else {
        // Fallback initialization without jQuery
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Transaction reports initialized without jQuery');
            // Basic functionality without jQuery would go here if needed
        });
    }
})();
</script>
@endsection
@endsection