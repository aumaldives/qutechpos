<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-12">
            <h3 class="text-primary">
                <i class="fas fa-chart-bar"></i> Reports
            </h3>
            <p class="text-muted">Access different types of reports for business analysis and compliance.</p>
            <hr>
        </div>
        
        <div class="col-xs-12">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-primary">
                        <div class="panel-header">
                            <h4 class="panel-title">
                                <i class="fas fa-file-invoice-dollar"></i> Transaction Reports
                            </h4>
                        </div>
                        <div class="panel-body">
                            <p>Generate detailed transaction reports with MVR conversion for subscription payments.</p>
                            
                            <h5>Features:</h5>
                            <ul class="list-unstyled">
                                <li>✓ Approved subscriptions only (excludes free trials)</li>
                                <li>✓ One invoice payment per row</li>
                                <li>✓ USD to MVR conversion using historical rates</li>
                                <li>✓ Customer TIN and business details</li>
                                <li>✓ Overview statistics sheet</li>
                                <li>✓ Quarterly and custom date filtering</li>
                                <li>✓ Live table preview before export</li>
                            </ul>
                            
                            <div class="text-center" style="margin-top: 20px;">
                                <a href="{{ route('superadmin.reports.transaction') }}" class="btn btn-primary btn-lg">
                                    <i class="fas fa-chart-line"></i> Open Transaction Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="panel panel-success">
                        <div class="panel-header">
                            <h4 class="panel-title">
                                <i class="fas fa-file-excel"></i> MIRA Tax Reports
                            </h4>
                        </div>
                        <div class="panel-body">
                            <p>Generate quarterly tax reports specifically for MIRA compliance with GST calculations.</p>
                            
                            <h5>Features:</h5>
                            <ul class="list-unstyled">
                                <li>✓ TaxInvoices sheet (businesses with TIN)</li>
                                <li>✓ OtherTransactions sheet (businesses without TIN)</li>
                                <li>✓ GST reverse calculation (8% default)</li>
                                <li>✓ MVR amounts with exchange rate conversion</li>
                                <li>✓ MIRA-compliant format and columns</li>
                                <li>✓ Quarterly reporting periods</li>
                                <li>✓ Business tax activity numbers</li>
                            </ul>
                            
                            <div class="text-center" style="margin-top: 20px;">
                                <a href="{{ route('superadmin.reports.tax') }}" class="btn btn-success btn-lg">
                                    <i class="fas fa-calculator"></i> Open Tax Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xs-12">
            <div class="alert alert-info">
                <h4><i class="fa fa-info-circle"></i> Exchange Rate Information</h4>
                <p>
                    Both reports use the USD to MVR exchange rate functionality configured in the Super Admin Settings. 
                    Historical exchange rates are stored with each subscription for accurate reporting. 
                    Current exchange rate: <strong>{{ number_format(\Modules\Superadmin\Utils\CurrencyUtil::getUsdToMvrRate(), 4) }} MVR per USD</strong>
                </p>
            </div>
        </div>
    </div>
</div>