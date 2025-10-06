@extends('layouts.app')
@section('title', 'API Documentation')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>API Documentation
        <small>Complete guide for using IsleBooks POS API</small>
    </h1>
    <hr class="header-row"/>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Coming Soon'])
                <div class="text-center" style="padding: 50px;">
                    <i class="fa fa-book fa-5x text-muted" style="margin-bottom: 20px;"></i>
                    <h2 class="text-muted">API Documentation Coming Soon</h2>
                    <p class="lead text-muted">
                        Comprehensive API documentation with examples, authentication guides, and integration tutorials 
                        will be available in Phase 2 of the API modernization roadmap.
                    </p>
                    
                    <div class="alert alert-info">
                        <h4><i class="fa fa-info-circle"></i> Currently Available</h4>
                        <p>The API key management system is ready for use. You can:</p>
                        <ul class="text-left" style="display: inline-block;">
                            <li>Generate secure API keys with custom permissions</li>
                            <li>Monitor API usage and analytics</li>
                            <li>Configure rate limiting and expiration</li>
                            <li>Revoke or activate keys as needed</li>
                        </ul>
                    </div>


                    <div class="btn-group" style="margin-bottom: 20px;">
                        <a href="{{ route('api-keys.index') }}" class="btn btn-primary btn-lg">
                            <i class="fa fa-key"></i> Manage API Keys
                        </a>
                        <a href="{{ route('api-docs.interactive') }}" class="btn btn-success btn-lg">
                            <i class="fa fa-code"></i> Interactive Docs
                        </a>
                        <a href="{{ route('api-docs.playground') }}" class="btn btn-warning btn-lg">
                            <i class="fa fa-play"></i> API Playground
                        </a>
                        <a href="{{ route('api-docs.code-examples') }}" class="btn btn-info btn-lg">
                            <i class="fa fa-file-code-o"></i> Code Examples
                        </a>
                    </div>
                    
                    <div class="alert alert-warning">
                        <h4><i class="fa fa-download"></i> Official SDKs Available</h4>
                        <p>Download ready-to-use SDKs for popular programming languages:</p>
                        <div class="btn-group" style="margin-top: 10px;">
                            <a href="{{ url('/sdks/php/IslebooksAPI.php') }}" class="btn btn-sm btn-primary" target="_blank">
                                <i class="fa fa-download"></i> PHP SDK
                            </a>
                            <a href="{{ url('/sdks/javascript/islebooks-api.js') }}" class="btn btn-sm btn-warning" target="_blank">
                                <i class="fa fa-download"></i> JavaScript SDK
                            </a>
                            <a href="{{ url('/sdks/python/islebooks_api.py') }}" class="btn btn-sm btn-success" target="_blank">
                                <i class="fa fa-download"></i> Python SDK
                            </a>
                            <a href="{{ url('/sdks/README.md') }}" class="btn btn-sm btn-info" target="_blank">
                                <i class="fa fa-book"></i> SDK Documentation
                            </a>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <a href="{{ route('integrations') }}" class="btn btn-default btn-lg">
                            <i class="fa fa-arrow-left"></i> Back to Integrations
                        </a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Available Endpoints -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-warning', 'title' => 'Available API Endpoints'])
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab_products" data-toggle="tab">Products</a></li>
                        <li><a href="#tab_categories" data-toggle="tab">Categories</a></li>
                        <li><a href="#tab_brands" data-toggle="tab">Brands</a></li>
                        <li><a href="#tab_business" data-toggle="tab">Business</a></li>
                        <li><a href="#tab_contacts" data-toggle="tab">Contacts</a></li>
                        <li><a href="#tab_transactions" data-toggle="tab">Transactions</a></li>
                        <li><a href="#tab_hrm" data-toggle="tab">HRM</a></li>
                        <li><a href="#tab_reports" data-toggle="tab">Reports</a></li>
                        <li><a href="#tab_pos" data-toggle="tab">POS</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab_products">
                            <h4>Products API - Full CRUD Operations</h4>
                            <div class="alert alert-info">
                                <h5><i class="fa fa-map-marker"></i> Location Support in Product API</h5>
                                <p><strong>GET /api/v1/products (List Products):</strong></p>
                                <ul>
                                    <li><strong>location_id is OPTIONAL</strong> - Filter stock information by specific location</li>
                                    <li><strong>Without location_id</strong> - Returns stock from all locations</li>
                                    <li><strong>With location_id</strong> - Returns stock for that location only</li>
                                </ul>
                                <p><strong>POST /api/v1/products (Create Product):</strong></p>
                                <ul>
                                    <li><strong>opening_stock is REQUIRED</strong> - Must specify at least one location</li>
                                    <li><strong>Multiple locations supported</strong> - Set different stock levels per location</li>
                                    <li><strong>Location validation</strong> - All locations must belong to your business</li>
                                    <li><strong>category_name/brand_name support</strong> - Use names instead of IDs (auto-create if not exists)</li>
                                    <li><strong>Simplified for single products</strong> - No variations required for single products</li>
                                </ul>
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/products</td><td>List all products (location_id optional for stock filtering)</td><td>read</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/products</td><td>Create new product (opening_stock required with location_id)</td><td>write, products</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/products/{id}</td><td>Get product details (location_id optional for stock filtering)</td><td>read</td></tr>
                                    <tr><td><span class="label label-warning">PUT</span></td><td>/api/v1/products/{id}</td><td>Update product</td><td>write, products</td></tr>
                                    <tr><td><span class="label label-danger">DELETE</span></td><td>/api/v1/products/{id}</td><td>Delete product</td><td>delete, products</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/products/{id}/variations</td><td>Get product variations</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/products/{id}/stock</td><td>Get product stock levels</td><td>read</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/products/bulk</td><td>Bulk create products</td><td>write, products</td></tr>
                                    <tr><td><span class="label label-warning">PUT</span></td><td>/api/v1/products/bulk</td><td>Bulk update products</td><td>write, products</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane" id="tab_categories">
                            <h4>Categories API - Product Category Management</h4>
                            <div class="alert alert-success">
                                <h5><i class="fa fa-sitemap"></i> Hierarchical Category Support</h5>
                                <p><strong>Features:</strong></p>
                                <ul>
                                    <li><strong>Parent/Sub Categories</strong> - Create hierarchical category structures</li>
                                    <li><strong>Auto-creation</strong> - Use category_name in Product API to auto-create categories</li>
                                    <li><strong>Case-insensitive matching</strong> - "Electronics" matches "electronics"</li>
                                    <li><strong>Duplicate prevention</strong> - Prevents duplicate category names per business</li>
                                    <li><strong>Usage validation</strong> - Cannot delete categories used by products</li>
                                </ul>
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/categories</td><td>List categories (parent_only=true by default)</td><td>read</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/categories</td><td>Create new category (name required, parent_id optional)</td><td>write, products</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/categories/dropdown</td><td>Get categories for dropdown/select lists</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/categories/hierarchical</td><td>Get categories with sub-categories hierarchy</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/categories/{id}</td><td>Get category details (include_sub_categories optional)</td><td>read</td></tr>
                                    <tr><td><span class="label label-warning">PUT</span></td><td>/api/v1/categories/{id}</td><td>Update category (prevents circular parent relationships)</td><td>write, products</td></tr>
                                    <tr><td><span class="label label-danger">DELETE</span></td><td>/api/v1/categories/{id}</td><td>Delete category (soft delete, validates usage)</td><td>delete, products</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane" id="tab_brands">
                            <h4>Brands API - Brand Management</h4>
                            <div class="alert alert-success">
                                <h5><i class="fa fa-tags"></i> Brand Management Features</h5>
                                <p><strong>Features:</strong></p>
                                <ul>
                                    <li><strong>Auto-creation</strong> - Use brand_name in Product API to auto-create brands</li>
                                    <li><strong>Case-insensitive matching</strong> - "Apple" matches "apple"</li>
                                    <li><strong>Repair support</strong> - use_for_repair flag for repair service brands</li>
                                    <li><strong>Duplicate prevention</strong> - Prevents duplicate brand names per business</li>
                                    <li><strong>Usage validation</strong> - Cannot delete brands used by products</li>
                                </ul>
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/brands</td><td>List brands (use_for_repair filter optional)</td><td>read</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/brands</td><td>Create new brand (name required)</td><td>write, products</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/brands/dropdown</td><td>Get brands for dropdown/select lists</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/brands/{id}</td><td>Get brand details</td><td>read</td></tr>
                                    <tr><td><span class="label label-warning">PUT</span></td><td>/api/v1/brands/{id}</td><td>Update brand (name and description)</td><td>write, products</td></tr>
                                    <tr><td><span class="label label-danger">DELETE</span></td><td>/api/v1/brands/{id}</td><td>Delete brand (soft delete, validates usage)</td><td>delete, products</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane" id="tab_business">
                            <h4>Business Information API (Read-Only)</h4>
                            <div class="alert alert-warning">
                                <i class="fa fa-shield"></i> <strong>Security Notice:</strong> All business settings are read-only via API for security reasons. Use the web interface to modify business configuration.
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/business-info</td><td>Get complete business information and locations</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/business</td><td>Get basic business information</td><td>read</td></tr>
                                </tbody>
                            </table>
                            
                            <h5>Business Information Features</h5>
                            <ul class="list-unstyled">
                                <li>✅ <strong>Business Details:</strong> Name, owner information, contact details</li>
                                <li>✅ <strong>Location Management:</strong> Complete list of business locations with addresses</li>
                                <li>✅ <strong>Currency Settings:</strong> Symbol, placement, separators, and formatting</li>
                                <li>✅ <strong>System Settings:</strong> Time zone, financial year configuration</li>
                                <li>✅ <strong>Branding:</strong> Business logo and website information</li>
                                <li>✅ <strong>Multi-Location Support:</strong> Active location filtering and management</li>
                            </ul>
                            
                            <div class="alert alert-info">
                                <h5><i class="fa fa-map-marker"></i> Location IDs for POS Operations</h5>
                                <p><strong>Use the business-info endpoint to get valid location_id values:</strong></p>
                                <ul>
                                    <li><strong>locations[]:</strong> Array of all business locations</li>
                                    <li><strong>locations[].id:</strong> Use this as location_id in POS operations</li>
                                    <li><strong>locations[].is_active:</strong> Only use active locations for sales</li>
                                    <li><strong>Security:</strong> API automatically validates location ownership</li>
                                </ul>
                            </div>
                        </div>
                        <div class="tab-pane" id="tab_contacts">
                            <h4>Contacts API - Complete CRUD Operations</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/contacts</td><td>List contacts with advanced filtering</td><td>read</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/contacts</td><td>Create new contact</td><td>write, contacts</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/contacts/{id}</td><td>Get contact details</td><td>read</td></tr>
                                    <tr><td><span class="label label-warning">PUT</span></td><td>/api/v1/contacts/{id}</td><td>Update contact</td><td>write, contacts</td></tr>
                                    <tr><td><span class="label label-danger">DELETE</span></td><td>/api/v1/contacts/{id}</td><td>Delete contact</td><td>delete, contacts</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/contacts/{id}/transactions</td><td>Get contact transactions</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/contacts/{id}/balance</td><td>Get contact balance</td><td>read</td></tr>
                                </tbody>
                            </table>
                            
                            <h5>Contact Features</h5>
                            <ul class="list-unstyled">
                                <li>✅ Customer and Supplier management</li>
                                <li>✅ Complete address and contact information</li>
                                <li>✅ Credit limits and payment terms</li>
                                <li>✅ Opening balance handling</li>
                                <li>✅ Custom fields support</li>
                                <li>✅ Transaction history integration</li>
                                <li>✅ Balance calculations</li>
                            </ul>
                        </div>
                        <div class="tab-pane" id="tab_transactions">
                            <h4>Transactions API - Advanced Operations</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/transactions</td><td>List all transactions with advanced filtering</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/transactions/{id}</td><td>Get transaction details with line items</td><td>read</td></tr>
                                    <tr><td><span class="label label-warning">PUT</span></td><td>/api/v1/transactions/{id}</td><td>Update transaction (draft/pending only)</td><td>write, transactions</td></tr>
                                    <tr><td><span class="label label-danger">DELETE</span></td><td>/api/v1/transactions/{id}</td><td>Delete transaction (draft only)</td><td>delete, transactions</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/transactions/{id}/payments</td><td>Add payment to transaction</td><td>write, transactions</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/transactions/{id}/payments</td><td>Get transaction payment history</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/sales</td><td>List sales transactions</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/purchases</td><td>List purchase transactions</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/sales/recent</td><td>Get recent sales</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/purchases/recent</td><td>Get recent purchases</td><td>read</td></tr>
                                </tbody>
                            </table>
                            
                            <h5>Transaction Features</h5>
                            <ul class="list-unstyled">
                                <li>✅ Complete transaction lifecycle management</li>
                                <li>✅ Advanced filtering by date, amount, status, contact</li>
                                <li>✅ Transaction line items with product details</li>
                                <li>✅ Payment processing and tracking</li>
                                <li>✅ Payment status automation (paid, partial, due)</li>
                                <li>✅ Financial summaries and balance calculations</li>
                                <li>✅ Sales and purchase separation</li>
                                <li>✅ Transaction security and validation</li>
                                <li>⚠️ Full transaction creation requires web interface (complex business logic)</li>
                            </ul>
                        </div>
                        <div class="tab-pane" id="tab_hrm">
                            <h4>HRM API - Human Resource Management</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/hrm/users</td><td>List all employees/users</td><td>read, hrm</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/hrm/users/{id}</td><td>Get employee details</td><td>read, hrm</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/hrm/check-in</td><td>Employee clock-in</td><td>write, hrm</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/hrm/check-out</td><td>Employee clock-out</td><td>write, hrm</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/hrm/attendance</td><td>Get attendance records</td><td>read, hrm</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/hrm/overtime-in</td><td>Start overtime session</td><td>write, hrm</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/hrm/overtime-out</td><td>End overtime session</td><td>write, hrm</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/hrm/overtime</td><td>Get overtime records</td><td>read, hrm</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/hrm/overtime-request</td><td>Create overtime request</td><td>write, hrm</td></tr>
                                </tbody>
                            </table>
                            
                            <h5>HRM Features</h5>
                            <ul class="list-unstyled">
                                <li>✅ <strong>Employee Management:</strong> List and retrieve detailed employee information</li>
                                <li>✅ <strong>Attendance Tracking:</strong> Complete clock-in/clock-out functionality</li>
                                <li>✅ <strong>Overtime Management:</strong> Track overtime sessions and requests</li>
                                <li>✅ <strong>Time Records:</strong> Comprehensive attendance and overtime history</li>
                                <li>✅ <strong>IP Address Tracking:</strong> Location-based attendance verification</li>
                                <li>✅ <strong>Notes Support:</strong> Add notes to clock-in/out and overtime entries</li>
                                <li>✅ <strong>Real-time Processing:</strong> Immediate attendance status updates</li>
                                <li>✅ <strong>Business Isolation:</strong> Multi-tenant employee data separation</li>
                            </ul>

                            <div class="alert alert-info">
                                <h5><i class="fa fa-info-circle"></i> HRM Endpoint Parameters</h5>
                                <p><strong>Attendance & Overtime endpoints support:</strong></p>
                                <ul>
                                    <li><strong>user_id:</strong> Target employee ID (required for most operations)</li>
                                    <li><strong>date_from/date_to:</strong> Date range filtering (YYYY-MM-DD)</li>
                                    <li><strong>clock_in_note/clock_out_note:</strong> Optional notes for attendance</li>
                                    <li><strong>ip_address:</strong> Automatic IP tracking for location verification</li>
                                </ul>
                                <p><strong>Overtime Request fields:</strong></p>
                                <ul>
                                    <li><strong>date:</strong> Overtime date</li>
                                    <li><strong>start_time/end_time:</strong> Overtime duration</li>
                                    <li><strong>reason:</strong> Justification for overtime</li>
                                </ul>
                            </div>
                        </div>
                        <div class="tab-pane" id="tab_reports">
                            <h4>Reports API - Analytics and Insights</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/reports/dashboard</td><td>Dashboard metrics and KPIs</td><td>read, reports</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/reports/sales-analytics</td><td>Detailed sales performance analytics</td><td>read, reports</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/reports/profit-loss</td><td>Profit & loss statement</td><td>read, reports</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/reports/stock-report</td><td>Inventory and stock analytics</td><td>read, reports</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/reports/trending-products</td><td>Best-selling products analysis</td><td>read, reports</td></tr>
                                </tbody>
                            </table>
                            
                            <h5>Reporting Features</h5>
                            <ul class="list-unstyled">
                                <li>✅ <strong>Dashboard Metrics:</strong> Sales, purchases, expenses, and profitability KPIs</li>
                                <li>✅ <strong>Sales Analytics:</strong> Daily trends, payment methods, top customers</li>
                                <li>✅ <strong>Financial Reports:</strong> Comprehensive P&L with expense breakdowns</li>
                                <li>✅ <strong>Inventory Intelligence:</strong> Stock levels, alerts, and product performance</li>
                                <li>✅ <strong>Product Trends:</strong> Best sellers by quantity and revenue</li>
                                <li>✅ <strong>Multi-location Support:</strong> Location-specific filtering and breakdowns</li>
                                <li>✅ <strong>Date Range Flexibility:</strong> Custom date ranges for all reports</li>
                                <li>✅ <strong>Business Context:</strong> All reports properly scoped to business</li>
                            </ul>
                            
                            <div class="alert alert-info">
                                <h5><i class="fa fa-info-circle"></i> Report Parameters</h5>
                                <p>All report endpoints support these common parameters:</p>
                                <ul>
                                    <li><strong>start_date:</strong> Filter start date (YYYY-MM-DD)</li>
                                    <li><strong>end_date:</strong> Filter end date (YYYY-MM-DD)</li>
                                    <li><strong>location_id:</strong> Filter by specific business location</li>
                                </ul>
                            </div>
                        </div>
                        <div class="tab-pane" id="tab_pos">
                            <h4>Point of Sale (POS) API - Complete Sales Operations</h4>
                            <div class="alert alert-warning">
                                <i class="fa fa-map-marker"></i> <strong>Location IDs Required:</strong> Most POS operations require a valid <code>location_id</code> parameter. Use <code>/api/v1/pos/business-info</code> to get all available location IDs for your business.
                            </div>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                        <th>Permissions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/business-info</td><td>Get business details and available location IDs</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/product-suggestions</td><td>Get product suggestions for POS (requires location_id)</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/product-row</td><td>Get product details for POS row (requires location_id)</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/plastic-bags</td><td>Get available plastic bag types</td><td>read</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/pos/sale</td><td>Create complete POS sale (requires location_id)</td><td>write, transactions</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/recent-transactions</td><td>Get recent POS transactions</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/settings</td><td>Get POS settings and configuration</td><td>read</td></tr>
                                    <tr><td><span class="label label-success">POST</span></td><td>/api/v1/pos/drafts</td><td>Save sale as draft (requires location_id)</td><td>write, transactions</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/drafts</td><td>Get saved drafts with auto-generated reference numbers</td><td>read</td></tr>
                                    <tr><td><span class="label label-info">GET</span></td><td>/api/v1/pos/drafts/{draft_id}</td><td>Load specific draft with full details</td><td>read</td></tr>
                                    <tr><td><span class="label label-danger">DELETE</span></td><td>/api/v1/pos/drafts/{draft_id}</td><td>Delete draft permanently</td><td>delete, transactions</td></tr>
                                </tbody>
                            </table>
                            
                            <h5>POS Core Features</h5>
                            <ul class="list-unstyled">
                                <li>✅ <strong>Product Search & Selection:</strong> Smart product suggestions with filtering</li>
                                <li>✅ <strong>Customer Management:</strong> Select existing customers or walk-in sales</li>
                                <li>✅ <strong>Plastic Bag Integration:</strong> Add eco-friendly plastic bags with stock tracking</li>
                                <li>✅ <strong>Multiple Payment Methods:</strong> Cash, card, bank transfer, and mixed payments</li>
                                <li>✅ <strong>Discount Management:</strong> Fixed amount or percentage discounts</li>
                                <li>✅ <strong>Tax Calculation:</strong> Automated tax calculation with custom rates</li>
                                <li>✅ <strong>Shipping Charges:</strong> Add delivery charges to sales</li>
                                <li>✅ <strong>Credit Sales:</strong> Process credit sales with payment tracking</li>
                                <li>✅ <strong>Draft Management:</strong> Save, load, and manage sale drafts</li>
                                <li>✅ <strong>Transaction History:</strong> Access recent POS transactions</li>
                            </ul>

                            <h5>Advanced POS Features</h5>
                            <ul class="list-unstyled">
                                <li>✅ <strong>Commission Tracking:</strong> Assign sales commission to agents</li>
                                <li>✅ <strong>Sale & Staff Notes:</strong> Add internal notes to transactions</li>
                                <li>✅ <strong>Stock Validation:</strong> Real-time stock checking during sales</li>
                                <li>✅ <strong>Location-Aware:</strong> Multi-location support with proper stock tracking</li>
                                <li>✅ <strong>Business Isolation:</strong> Secure multi-tenant data separation</li>
                                <li>✅ <strong>Audit Trail:</strong> Complete transaction logging and history</li>
                                <li>✅ <strong>Invoice Generation:</strong> Automatic invoice creation and numbering</li>
                                <li>✅ <strong>Payment Status Tracking:</strong> Automatic payment status updates</li>
                            </ul>
                            
                            <div class="alert alert-success">
                                <h5><i class="fa fa-shopping-cart"></i> Complete POS Sale Example</h5>
                                <p><strong>The POS sale endpoint supports all features needed for comprehensive sales operations:</strong></p>
                                <ul>
                                    <li><strong>products[]:</strong> Array of products with variation_id, quantity, unit_price</li>
                                    <li><strong>plastic_bags[]:</strong> Array with type_id and quantity for eco-tracking</li>
                                    <li><strong>payment[]:</strong> Multiple payment methods (cash, card, bank_transfer)</li>
                                    <li><strong>discount_amount & discount_type:</strong> Fixed or percentage discounts</li>
                                    <li><strong>tax_id:</strong> Apply business tax rates automatically</li>
                                    <li><strong>shipping_charges:</strong> Add delivery fees</li>
                                    <li><strong>is_credit_sale:</strong> Process as credit sale for payment tracking</li>
                                    <li><strong>commission_agent:</strong> Assign sales commission</li>
                                    <li><strong>sale_note & staff_note:</strong> Add transaction documentation</li>
                                </ul>
                            </div>

                            <div class="alert alert-info">
                                <h5><i class="fa fa-info-circle"></i> POS API Integration Notes</h5>
                                <p><strong>Key integration points for POS systems:</strong></p>
                                <ul>
                                    <li><strong>Product Search:</strong> Use term, category_id, brand_id for filtering</li>
                                    <li><strong>Stock Validation:</strong> Check available quantity before sale creation</li>
                                    <li><strong>Plastic Bag Stock:</strong> Monitor and track eco-friendly bag usage</li>
                                    <li><strong>Payment Processing:</strong> Support for multiple payment methods in single transaction</li>
                                    <li><strong>Draft System:</strong> Perfect for mobile POS apps with offline capabilities</li>
                                    <li><strong>Location Context:</strong> All operations require location_id for proper stock management</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Interactive Documentation Features -->
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-success', 'title' => 'Interactive Documentation Features'])
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-green"><i class="fa fa-code"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Swagger UI</span>
                                <span class="info-box-number">Explore & Test</span>
                                <a href="{{ route('api-docs.interactive') }}" class="btn btn-xs btn-success">Try Now</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-yellow"><i class="fa fa-play"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">API Playground</span>
                                <span class="info-box-number">Real Testing</span>
                                <a href="{{ route('api-docs.playground') }}" class="btn btn-xs btn-warning">Launch</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-blue"><i class="fa fa-file-code-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Code Examples</span>
                                <span class="info-box-number">4 Languages</span>
                                <a href="{{ route('api-docs.code-examples') }}" class="btn btn-xs btn-info">View</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <span class="info-box-icon bg-gray"><i class="fa fa-download"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">OpenAPI Spec</span>
                                <span class="info-box-number">JSON/YAML</span>
                                <a href="{{ url('/api/openapi.yaml') }}" class="btn btn-xs btn-default" target="_blank">Download</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h4><i class="fa fa-graduation-cap"></i> Developer Experience Features</h4>
                            <ul class="list-inline">
                                <li>🚀 <strong>Interactive Testing</strong> - Test all endpoints directly from the browser</li>
                                <li>🔐 <strong>Real Authentication</strong> - Use your actual API keys for testing</li>
                                <li>📋 <strong>Auto-Generated cURL</strong> - Copy commands for command-line usage</li>
                                <li>💻 <strong>Multi-Language Examples</strong> - Ready-to-use code in cURL, JS, PHP, Python</li>
                                <li>📊 <strong>Response Visualization</strong> - Pretty-formatted JSON responses</li>
                                <li>⚡ <strong>Performance Metrics</strong> - Response times and status codes</li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <!-- Quick Start Guide -->
    <div class="row">
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-info', 'title' => 'Quick Start Guide'])
                <h4>1. Create an API Key</h4>
                <p>Go to <a href="{{ route('api-keys.index') }}">API Keys Management</a> and create a new key with the required permissions.</p>
                
                <h4>2. Authentication</h4>
                <p>Include your API key in requests using one of these methods:</p>
                <ul>
                    <li><strong>Header:</strong> <code>Authorization: Bearer YOUR_API_KEY</code></li>
                    <li><strong>Header:</strong> <code>X-API-Key: YOUR_API_KEY</code></li>
                    <li><strong>Query:</strong> <code>?api_key=YOUR_API_KEY</code></li>
                </ul>

                <h4>3. Rate Limits</h4>
                <p>All API requests are subject to rate limiting based on your key configuration. Check response headers for limit information.</p>

                <h4>4. Test Your API</h4>
                <p>Start with these basic endpoints:</p>
                <pre><code># Check API status
GET /api/status

# Test authentication
GET /api/v1/ping

# Get business info
GET /api/v1/business

# List products
GET /api/v1/products</code></pre>
            @endcomponent
        </div>

        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-success', 'title' => 'Development Roadmap'])
                <h4><i class="fa fa-check text-green"></i> Phase 1 - Completed</h4>
                <ul>
                    <li>✅ Secure API key generation system</li>
                    <li>✅ Usage tracking and analytics</li>
                    <li>✅ Rate limiting and permissions</li>
                    <li>✅ Management interface</li>
                </ul>

                <h4><i class="fa fa-check text-green"></i> Phase 2 - Completed</h4>
                <ul>
                    <li>✅ Modern REST API endpoints</li>
                    <li>✅ API versioning (v1)</li>
                    <li>✅ Products, Contacts, and Transactions APIs</li>
                    <li>✅ Payment processing and tracking</li>
                    <li>✅ Advanced filtering and search</li>
                </ul>

                <h4><i class="fa fa-check text-green"></i> Phase 3.1 - Completed</h4>
                <ul>
                    <li>✅ Reports and analytics endpoints</li>
                    <li>✅ Dashboard metrics and KPIs</li>
                    <li>✅ Sales analytics and insights</li>
                    <li>✅ Financial reporting (P&L)</li>
                    <li>✅ Inventory and stock analytics</li>
                </ul>

                <h4><i class="fa fa-check text-green"></i> Phase 3.2 - Completed</h4>
                <ul>
                    <li>✅ Interactive API documentation (Swagger UI)</li>
                    <li>✅ API testing playground with real authentication</li>
                    <li>✅ Multi-language code examples (cURL, JavaScript, PHP, Python)</li>
                    <li>✅ OpenAPI 3.0 specification with comprehensive schemas</li>
                    <li>✅ Developer tools and testing interface</li>
                </ul>

                <h4><i class="fa fa-check text-green"></i> Phase 3.3 - Completed</h4>
                <ul>
                    <li>✅ Webhook integrations and event notifications</li>
                    <li>✅ SDK development for popular languages (PHP, JavaScript, Python)</li>
                    <li>✅ API rate monitoring dashboard</li>
                    <li>✅ Advanced developer portal features</li>
                </ul>

                <h4><i class="fa fa-check text-green"></i> Phase 3.4 - Completed</h4>
                <ul>
                    <li>✅ Advanced authentication (OAuth2) via Laravel Passport</li>
                    <li>✅ HRM module API integration</li>
                    <li>✅ Complete webhook management system</li>
                    <li>✅ Professional SDK suite with comprehensive documentation</li>
                </ul>

                <h4><i class="fa fa-lightbulb-o text-blue"></i> Future Enhancements</h4>
                <ul>
                    <li>🔮 Third-party marketplace integrations</li>
                    <li>🔮 Mobile application SDKs</li>
                    <li>🔮 GraphQL endpoints</li>
                    <li>🔮 Advanced analytics and AI features</li>
                </ul>
            @endcomponent
        </div>
    </div>
</section>

@endsection