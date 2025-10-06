@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | MIRA Tax Reports')

@section('content')
@include('superadmin::layouts.nav')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        <i class="fas fa-file-excel"></i> MIRA Tax Reports
        <small>Generate quarterly tax reports for MIRA compliance</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>MIRA Tax Reports:</strong> Generate quarterly tax reports for MIRA compliance. Reports include subscription payments from approved businesses with amounts converted to MVR using stored exchange rates.
            </div>
        </div>
        
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> Report Filters
                    </h3>
                </div>
                <div class="box-body">
                    <form id="miraReportForm">
                        <div class="row">
                            <div class="col-md-4">
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
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="report_year">Year:</label>
                                    <select id="report_year" name="year" class="form-control">
                                        @for ($year = date('Y'); $year >= 2020; $year--)
                                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row" id="custom_date_range" style="display: none;">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="start_date">Start Date:</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="end_date">End Date:</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" id="generate_mira_report" class="btn btn-primary btn-lg">
                                    <i class="fas fa-download"></i> Generate & Download MIRA Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-xs-12">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-info-circle"></i> Report Information
                    </h3>
                </div>
                <div class="box-body">
                    <h5>Report Structure:</h5>
                    <ul class="list-unstyled">
                        <li><strong>Sheet 1 - TaxInvoices:</strong> Contains subscription payments from businesses with Tax ID numbers</li>
                        <li><strong>Sheet 2 - OtherTransactions:</strong> Contains aggregated revenue from businesses without Tax ID numbers</li>
                    </ul>
                    
                    <h5>Data Included:</h5>
                    <ul class="list-unstyled">
                        <li>✓ Only approved subscriptions (excludes free trials)</li>
                        <li>✓ Amounts converted to MVR using historical exchange rates</li>
                        <li>✓ GST calculations using reverse calculation method</li>
                        <li>✓ Business TIN and activity numbers from settings</li>
                        <li>✓ One invoice payment per row</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

@section('javascript')
<script>
// Ensure jQuery is available
(function() {
    function initializeReports() {
        // Handle report type change
        $('#report_type').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#custom_date_range').show();
            } else {
                $('#custom_date_range').hide();
            }
        });

        // Generate MIRA report
        $('#generate_mira_report').on('click', function() {
            var formData = $('#miraReportForm').serialize();
            window.open('{{ route("superadmin.reports.tax.export") }}?' + formData, '_blank');
        });
    }

    // Initialize when DOM is ready
    if (typeof jQuery !== 'undefined') {
        $(document).ready(initializeReports);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            // Fallback if jQuery is not available
            document.getElementById('report_type').addEventListener('change', function() {
                var customRange = document.getElementById('custom_date_range');
                if (this.value === 'custom') {
                    customRange.style.display = 'block';
                } else {
                    customRange.style.display = 'none';
                }
            });

            document.getElementById('generate_mira_report').addEventListener('click', function() {
                var form = document.getElementById('miraReportForm');
                var formData = new FormData(form);
                var params = new URLSearchParams(formData).toString();
                window.open('{{ route("superadmin.reports.tax.export") }}?' + params, '_blank');
            });
        });
    }
})();
</script>
@endsection
@endsection