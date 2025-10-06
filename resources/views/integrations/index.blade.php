@extends('layouts.app')
@section('title', 'Integrations')

@section('content')

<section class="content-header">
	<h1 >Integrations</h1>
	<hr class="header-row"/>
</section>

<!-- Main content -->
<section class="content">

	<link rel="stylesheet" href="{{ asset('css/integration.css?v=2') }}">

	<div class="row">

	@if($user_permissions['can_manage_api_keys'])
	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-key-skeleton" style="font-size: 48px; color: #28a745; margin-top: 15px; --fa-primary-color: #28a745; --fa-secondary-color: #28a745; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">API Keys</h3>
			</div>
			<div class="rowThree">
				<p>Manage your API keys for secure third-party integrations and custom applications.</p>
			</div>
			<div class="custom_btn">
				<a href="{{ route('api-keys.index') }}">
					<button class="connectBtn">Manage Keys</button>
				</a>
			</div>
		</div>
	</div>
	@endif

	@if($user_permissions['can_view_api_docs'])
	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-book-open-cover" style="font-size: 48px; color: #17a2b8; margin-top: 15px; --fa-primary-color: #17a2b8; --fa-secondary-color: #17a2b8; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">API Documentation</h3>
			</div>
			<div class="rowThree">
				<p>Complete API documentation with examples, authentication guides, and integration tutorials.</p>
			</div>
			<div class="custom_btn">
				<a href="{{ route('api-docs') }}" target="_blank">
					<button class="connectBtn">View Docs</button>
				</a>
			</div>
		</div>
	</div>
	@endif

	@if(!$user_permissions['can_manage_api_keys'] && !$user_permissions['can_view_api_docs'])
	<div class="col-md-12">
		<div class="alert alert-info">
			<h4><i class="fa fa-info-circle"></i> Access Restricted</h4>
			<p>You don't have permission to access integration features. Please contact your administrator to grant you the necessary permissions:</p>
			<ul>
				<li><strong>Access Integrations Section</strong> - To view this page</li>
				<li><strong>Create & Manage API Keys</strong> - To manage API keys</li>
				<li><strong>View API Documentation</strong> - To access API documentation</li>
			</ul>
		</div>
	</div>
	@endif

	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-webhook" style="font-size: 48px; color: #6f42c1; margin-top: 15px; --fa-primary-color: #6f42c1; --fa-secondary-color: #6f42c1; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">Webhooks</h3>
			</div>
			<div class="rowThree">
				<p>Set up real-time event notifications to keep your external systems synchronized with IsleBooks.</p>
			</div>
			<div class="custom_btn">
				<a href="{{ route('webhooks.index') }}">
					<button class="connectBtn">Manage Webhooks</button>
				</a>
			</div>
		</div>
	</div>

	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-store" style="font-size: 48px; color: #96588a; margin-top: 15px; --fa-primary-color: #96588a; --fa-secondary-color: #96588a; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName"> WooCommerce </h3>
			</div>
			<div class="rowThree">
				<p> Unify online and in-store sales with IsleBooks POS's WooCommerce Integration. </p>
			</div>
			<div class="custom_btn">
				<a href="/woocommerce">
					<button class="connectBtn"> Configure </button>
				</a>
			</div>
		</div>
	</div>
</div>

<!-- Second Row of Integrations -->
<div class="row" style="margin-top: 30px;">

	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-books" style="font-size: 48px; color: #0077C5; margin-top: 15px; --fa-primary-color: #0077C5; --fa-secondary-color: #0077C5; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">QuickBooks Integration</h3>
			</div>
			<div class="rowThree">
				<p>Seamlessly sync your sales, inventory, and financial data with QuickBooks for streamlined accounting workflows.</p>
			</div>
			<div class="custom_btn">
				@if(isset($module_availability['quickbooks_enabled']) && $module_availability['quickbooks_enabled'])
					<a href="{{ route('quickbooks.index') }}">
						<button class="connectBtn">Configure</button>
					</a>
				@elseif(isset($module_availability['quickbooks_upgrade_available']) && $module_availability['quickbooks_upgrade_available'])
					<button class="connectBtn" style="background-color: #28a745;" onclick="showUpgradeModal()">
						Upgrade to {{ $module_availability['quickbooks_required_package'] }}
					</button>
					<div class="mt-2">
						<small class="text-muted">
							<i class="fas fa-info-circle"></i>
							QuickBooks integration available with {{ $module_availability['quickbooks_required_package'] }}
						</small>
					</div>
				@else
					<button class="connectBtn" style="background-color: #6c757d;" disabled>
						Not Available
					</button>
					<div class="mt-2">
						<small class="text-muted">
							<i class="fas fa-info-circle"></i>
							QuickBooks integration is not currently available in any subscription plan
						</small>
					</div>
				@endif
			</div>
		</div>
	</div>

	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-brands fa-shopify" style="font-size: 48px; color: #95BF47; margin-top: 15px;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">Shopify Integration</h3>
			</div>
			<div class="rowThree">
				<p>Connect your Shopify store with IsleBooks POS to synchronize products, orders, and inventory in real-time.</p>
			</div>
			<div class="custom_btn">
				<button class="connectBtn" style="background-color: #6c757d;" disabled>Coming Soon</button>
			</div>
		</div>
	</div>

	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-chart-mixed" style="font-size: 48px; color: #e83e8c; margin-top: 15px; --fa-primary-color: #e83e8c; --fa-secondary-color: #e83e8c; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">Analytics & BI</h3>
			</div>
			<div class="rowThree">
				<p>Advanced business intelligence integration with Google Analytics, Power BI, and custom dashboards.</p>
			</div>
			<div class="custom_btn">
				<button class="connectBtn" style="background-color: #6c757d;" disabled>Coming Soon</button>
			</div>
		</div>
	</div>

	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-credit-card-front" style="font-size: 48px; color: #20c997; margin-top: 15px; --fa-primary-color: #20c997; --fa-secondary-color: #20c997; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">Payment Gateways</h3>
			</div>
			<div class="rowThree">
				<p>Connect with popular payment processors like BML MPOS, MIB Payment Gateway, mFaisaa, Dhiraagu Pay for seamless transactions.</p>
			</div>
			<div class="custom_btn">
				<button class="connectBtn" style="background-color: #6c757d;" disabled>Coming Soon</button>
			</div>
		</div>
	</div>

</div>

<!-- Third Row - Additional Coming Soon Integrations -->
<div class="row" style="margin-top: 30px;">

	<div class="col-md-3">
		<div class="card">
			<div class="rowOne">
				<i class="fa-duotone fa-envelope-open-text" style="font-size: 48px; color: #6610f2; margin-top: 15px; --fa-primary-color: #6610f2; --fa-secondary-color: #6610f2; --fa-secondary-opacity: 0.3;"></i>
			</div>
			<div class="rowTwo">
				<h3 class="iconName">Email Marketing</h3>
			</div>
			<div class="rowThree">
				<p>Integrate with Mailchimp, Constant Contact, and other email marketing platforms to engage your customers.</p>
			</div>
			<div class="custom_btn">
				<button class="connectBtn" style="background-color: #6c757d;" disabled>Coming Soon</button>
			</div>
		</div>
	</div>

</div>

</section>
@endsection
@if(isset($module_availability['quickbooks_upgrade_available']) && $module_availability['quickbooks_upgrade_available'])
<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="fas fa-star"></i> Upgrade to {{ $module_availability['quickbooks_required_package'] }}
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <h5><i class="fas fa-calculator text-primary"></i> QuickBooks Integration Features</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <strong>Location-Specific Sync:</strong> Connect each business location to its own QuickBooks company</li>
                            <li><i class="fas fa-check text-success"></i> <strong>Smart Invoice Sync:</strong> Separate credit sales, cash sales, and bank payments</li>
                            <li><i class="fas fa-check text-success"></i> <strong>Comprehensive Data Sync:</strong> Customers, suppliers, products, payments, and purchases</li>
                            <li><i class="fas fa-check text-success"></i> <strong>SKU-Based Duplicate Prevention:</strong> Avoid duplicate products using SKU matching</li>
                            <li><i class="fas fa-check text-success"></i> <strong>Real-Time Stock Levels:</strong> Keep inventory synchronized automatically</li>
                            <li><i class="fas fa-check text-success"></i> <strong>OAuth2 Security:</strong> Bank-level security with encrypted token storage</li>
                        </ul>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Ready to upgrade?</strong> Contact your administrator or account manager to enable QuickBooks integration for your business.
                        </div>

                        <div class="text-center">
                            <h6 class="text-muted">
                                <i class="fas fa-shield-alt"></i> Secure • <i class="fas fa-sync"></i> Automated • <i class="fas fa-chart-line"></i> Scalable
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <a href="{{ route('pricing') }}" class="btn btn-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View Pricing Plans
                </a>
                <a href="mailto:support@islebooks.mv?subject=QuickBooks Integration Upgrade Request" class="btn btn-success">
                    <i class="fas fa-envelope"></i> Contact Support
                </a>
            </div>
        </div>
    </div>
</div>
@endif

@section('javascript')
<script>
function showUpgradeModal() {
    @if(isset($module_availability['quickbooks_upgrade_available']) && $module_availability['quickbooks_upgrade_available'])
        $('#upgradeModal').modal('show');
    @else
        // Fallback for when QuickBooks is not available for upgrade
        alert('QuickBooks integration is not currently available in any subscription plan. Please contact support for more information.');
    @endif
}
</script>
@endsection