// Simple test to see if JavaScript is working
console.log('WooCommerce module test script loaded');

// Simple Vue initialization without imports
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing Vue...');
    
    // Check if Vue is available globally
    if (typeof Vue === 'undefined') {
        console.error('Vue is not available globally');
        
        // Show error message in the loading area
        const appDiv = document.getElementById('woocommerce-app');
        if (appDiv) {
            appDiv.innerHTML = '<div class="alert alert-danger"><h4>JavaScript Loading Error</h4><p>Vue.js is not available. This might be a compatibility issue with the JavaScript environment.</p><p>Please check the browser console for more details.</p></div>';
        }
        return;
    }
    
    console.log('Vue is available, version:', Vue.version);
    
    // Simple Vue app without imports
    try {
        new Vue({
            el: '#woocommerce-app',
            data: {
                message: 'WooCommerce Configuration Loaded Successfully!',
                loading: false
            },
            mounted: function() {
                console.log('Vue app mounted successfully');
            },
            template: `
                <div class="container-fluid">
                    <div class="alert alert-success">
                        <h4><i class="fas fa-check-circle"></i> {{ message }}</h4>
                        <p>The Vue.js application is working correctly. The WooCommerce configuration interface will be loaded shortly.</p>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5>System Information</h5>
                            <ul>
                                <li>Vue.js Version: {{ $options._base.version || 'Unknown' }}</li>
                                <li>Business ID: {{ businessId }}</li>
                                <li>Timestamp: {{ new Date().toLocaleString() }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            `,
            data: function() {
                return {
                    message: 'WooCommerce Configuration Loaded Successfully!',
                    businessId: window.businessId || 'Not Available'
                }
            }
        });
    } catch (error) {
        console.error('Vue initialization error:', error);
        
        const appDiv = document.getElementById('woocommerce-app');
        if (appDiv) {
            appDiv.innerHTML = '<div class="alert alert-danger"><h4>Vue Initialization Error</h4><p>' + error.message + '</p></div>';
        }
    }
});