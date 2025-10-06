<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-12">
            <h3 class="text-primary">
                <i class="fas fa-file-excel"></i> MIRA Tax Reports
            </h3>
            <p class="text-muted">Generate quarterly tax reports for MIRA compliance. Reports include subscription payments from approved businesses.</p>
            <hr>
        </div>
        
        <div class="col-xs-12">
            <div class="panel panel-default">
                <div class="panel-header">
                    <h4 class="panel-title">
                        <i class="fa fa-filter"></i> Report Filters
                    </h4>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="report_type">Report Type:</label>
                                <select id="report_type" name="report_type" class="form-control">
                                    <option value="quarterly">Current Quarter</option>
                                    <option value="q1">Quarter 1 (Jan-Mar)</option>
                                    <option value="q2">Quarter 2 (Apr-Jun)</option>
                                    <option value="q3">Quarter 3 (Jul-Sep)</option>
                                    <option value="q4">Quarter 4 (Oct-Dec)</option>
                                    <option value="custom">Custom Date Range</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="report_year">Year:</label>
                                <select id="report_year" name="report_year" class="form-control">
                                    @for ($year = date('Y'); $year >= 2020; $year--)
                                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="custom_date_range" style="display: none;">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="start_date">Start Date:</label>
                                <input type="date" id="start_date" name="start_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label for="end_date">End Date:</label>
                                <input type="date" id="end_date" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12">
                            <button type="button" id="generate_mira_report" class="btn btn-primary btn-lg">
                                <i class="fas fa-download"></i> Generate & Download MIRA Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xs-12">
            <div class="panel panel-info">
                <div class="panel-header">
                    <h4 class="panel-title">
                        <i class="fa fa-info-circle"></i> Report Information
                    </h4>
                </div>
                <div class="panel-body">
                    <h5>Report Structure:</h5>
                    <ul class="list-unstyled">
                        <li><strong>Sheet 1 - TaxInvoices:</strong> Contains subscription payments from businesses with Tax ID numbers</li>
                        <li><strong>Sheet 2 - OtherTransactions:</strong> Contains aggregated revenue from businesses without Tax ID numbers</li>
                    </ul>
                    
                    <h5>Data Included:</h5>
                    <ul class="list-unstyled">
                        <li>✓ Only approved subscriptions (excludes free trials)</li>
                        <li>✓ One invoice payment per row</li>
                        <li>✓ GST calculations using reverse calculation method</li>
                        <li>✓ Business TIN and activity numbers from settings</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

