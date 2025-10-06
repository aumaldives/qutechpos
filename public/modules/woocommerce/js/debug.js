// Debug script to check JavaScript environment
console.log('=== WooCommerce Debug Script Starting ===');

// Check basic JavaScript functionality
try {
    console.log('1. Basic JavaScript: OK');
} catch (e) {
    console.error('1. Basic JavaScript: FAILED', e);
}

// Check if jQuery is loaded
try {
    if (typeof $ !== 'undefined') {
        console.log('2. jQuery: OK, version:', $.fn.jquery);
    } else {
        console.error('2. jQuery: NOT LOADED');
    }
} catch (e) {
    console.error('2. jQuery: ERROR', e);
}

// Check if Vue is loaded
try {
    if (typeof Vue !== 'undefined') {
        console.log('3. Vue.js: OK, version:', Vue.version);
    } else {
        console.error('3. Vue.js: NOT LOADED');
    }
} catch (e) {
    console.error('3. Vue.js: ERROR', e);
}

// Check if axios is loaded
try {
    if (typeof axios !== 'undefined') {
        console.log('4. Axios: OK');
    } else if (typeof window.axios !== 'undefined') {
        console.log('4. Axios: OK (window.axios)');
    } else {
        console.error('4. Axios: NOT LOADED');
    }
} catch (e) {
    console.error('4. Axios: ERROR', e);
}

// Check if DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('5. DOM Ready: OK');
    
    // Check if target element exists
    const appElement = document.getElementById('woocommerce-app');
    if (appElement) {
        console.log('6. Target Element: OK', appElement);
        
        // Replace loading message with debug info
        appElement.innerHTML = `
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle"></i> Debug Information</h4>
                <ul style="margin-bottom: 0;">
                    <li>JavaScript: Working</li>
                    <li>jQuery: ${typeof $ !== 'undefined' ? 'Loaded (' + $.fn.jquery + ')' : 'NOT LOADED'}</li>
                    <li>Vue.js: ${typeof Vue !== 'undefined' ? 'Loaded (' + Vue.version + ')' : 'NOT LOADED'}</li>
                    <li>Axios: ${typeof axios !== 'undefined' || typeof window.axios !== 'undefined' ? 'Loaded' : 'NOT LOADED'}</li>
                    <li>Business ID: ${window.businessId || 'Not Available'}</li>
                    <li>CSRF Token: ${document.querySelector('meta[name="csrf-token"]') ? 'Available' : 'Missing'}</li>
                </ul>
            </div>
            <div class="card">
                <div class="card-header">
                    <h5>Environment Test</h5>
                </div>
                <div class="card-body">
                    <button id="test-vue" class="btn btn-primary">Test Vue Initialization</button>
                    <button id="test-ajax" class="btn btn-secondary ml-2">Test AJAX Call</button>
                    <div id="test-results" class="mt-3"></div>
                </div>
            </div>
        `;
        
        // Add test buttons functionality
        document.getElementById('test-vue').addEventListener('click', function() {
            const results = document.getElementById('test-results');
            
            if (typeof Vue === 'undefined') {
                results.innerHTML = '<div class="alert alert-danger">Vue.js is not loaded!</div>';
                return;
            }
            
            try {
                const testApp = new Vue({
                    data: {
                        message: 'Vue.js is working!'
                    }
                });
                results.innerHTML = '<div class="alert alert-success">Vue.js initialization: SUCCESS</div>';
            } catch (error) {
                results.innerHTML = '<div class="alert alert-danger">Vue.js initialization: FAILED - ' + error.message + '</div>';
            }
        });
        
        document.getElementById('test-ajax').addEventListener('click', function() {
            const results = document.getElementById('test-results');
            
            if (typeof axios === 'undefined' && typeof window.axios === 'undefined') {
                results.innerHTML = '<div class="alert alert-danger">Axios is not loaded!</div>';
                return;
            }
            
            const axiosInstance = axios || window.axios;
            
            axiosInstance.get('/api/woocommerce/api/stats')
                .then(function(response) {
                    results.innerHTML = '<div class="alert alert-success">AJAX Test: SUCCESS - ' + JSON.stringify(response.data) + '</div>';
                })
                .catch(function(error) {
                    if (error.response) {
                        results.innerHTML = '<div class="alert alert-warning">AJAX Test: API Response - Status: ' + error.response.status + ', Data: ' + JSON.stringify(error.response.data) + '</div>';
                    } else if (error.request) {
                        results.innerHTML = '<div class="alert alert-danger">AJAX Test: Network Error - ' + error.message + '</div>';
                    } else {
                        results.innerHTML = '<div class="alert alert-danger">AJAX Test: Error - ' + error.message + '</div>';
                    }
                });
        });
        
    } else {
        console.error('6. Target Element: NOT FOUND (#woocommerce-app)');
    }
});

console.log('=== WooCommerce Debug Script Complete ===');