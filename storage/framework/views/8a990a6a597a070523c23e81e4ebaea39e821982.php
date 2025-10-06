<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Display - <?php echo e($business_details->name ?? 'AGRO MART', false); ?></title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- CryptoJS for Decryption -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; transform: translateX(-20px); }
        }

        @keyframes slideIn {
            from { transform: translateX(100%); }
            to { transform: translateX(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .item-enter {
            animation: fadeIn 0.3s ease-out;
        }

        .item-exit {
            animation: fadeOut 0.3s ease-out;
        }

        .thank-you-animation {
            animation: slideIn 0.5s ease-out;
        }

        .pulse-update {
            animation: pulse 0.3s ease-in-out;
        }

        body {
            overflow: hidden;
        }

        /* Auto-scroll items - hide oldest from top when overflow */
        #cart-items {
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        #cart-items::-webkit-scrollbar {
            width: 6px;
        }

        #cart-items::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #cart-items::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #cart-items::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Main Container -->
    <div class="h-screen grid grid-cols-1 lg:grid-cols-2">

        <!-- LEFT SIDE: Cart Items -->
        <div class="bg-white p-6 lg:p-8 flex flex-col h-screen">
            <!-- Header -->
            <div class="mb-4 border-b-2 border-gray-200 pb-4 flex-shrink-0">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-800">SHOPPING CART</h2>
                <div id="connection-status" class="mt-2 text-sm">
                    <span class="text-yellow-600">● Connecting...</span>
                </div>
            </div>

            <!-- Column Headers -->
            <div id="cart-headers" class="border-b-2 border-gray-300 pb-2 mb-2 hidden flex-shrink-0">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-bold text-gray-700 uppercase">Item Name</span>
                    </div>
                    <div class="text-sm font-bold text-gray-700 uppercase text-center" style="min-width: 100px;">
                        Tax
                    </div>
                    <div class="text-sm font-bold text-gray-700 uppercase text-right" style="min-width: 120px;">
                        Total
                    </div>
                </div>
            </div>

            <!-- Items List (Scrollable) -->
            <div id="cart-items" class="flex-1 overflow-y-auto mb-4 min-h-0">
                <!-- Empty State -->
                <div id="empty-state" class="flex flex-col items-center justify-center h-full text-gray-400">
                    <svg class="w-24 h-24 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-2xl font-light">Scan items to begin</p>
                </div>

                <!-- Items will be dynamically added here -->
            </div>

            <!-- Totals (Sticky Bottom) -->
            <div id="cart-totals" class="border-t-2 border-gray-200 pt-4 hidden bg-white flex-shrink-0">
                <div class="space-y-1 text-base lg:text-lg">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal (excl. tax):</span>
                        <span id="subtotal-amount" class="font-semibold">MVR 0.00</span>
                    </div>
                    <div id="tax-row" class="flex justify-between text-gray-600">
                        <span>Tax (GST 8%):</span>
                        <span id="tax-amount" class="font-semibold text-blue-600">MVR 0.00</span>
                    </div>
                    <div class="flex justify-between text-2xl lg:text-3xl font-extrabold text-green-600 border-t-2 border-gray-300 pt-2 mt-2">
                        <span>TOTAL:</span>
                        <span id="total-amount">MVR 0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT SIDE: Branding -->
        <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 lg:p-8 flex flex-col items-center justify-center text-center">
            <div class="space-y-8">
                <!-- Welcome Text -->
                <div>
                    <p class="text-xl lg:text-2xl font-light text-gray-600 mb-2">Welcome to</p>
                    <h1 class="text-5xl lg:text-7xl font-black text-green-500 leading-tight">
                        
                    </h1>
                </div>

                <!-- Store Logo -->
                <div class="my-8">
                    <img src="<?php echo e(asset('assets/AgroMart.png'), false); ?>"
                         alt="Store Logo"
                         class="max-w-xs lg:max-w-md mx-auto rounded-3xl shadow-lg">
                </div>

                <!-- Marketing Messages -->
                <div class="space-y-4 text-lg lg:text-xl text-gray-700">
                    <p class="font-semibold">🌿 Fresh Products Daily</p>
                    <p class="font-semibold">✓ Quality Guaranteed</p>
                </div>

                <!-- Footer Message -->
                <div class="mt-12">
                    <p class="text-base lg:text-lg text-gray-600 font-light">
                        Thank you for shopping with us
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Thank You Overlay (Hidden by default) -->
    <div id="thank-you-overlay" class="hidden fixed inset-0 bg-green-500 flex items-center justify-center z-50">
        <div class="text-center text-white thank-you-animation px-4">
            <h1 class="text-6xl lg:text-8xl font-black mb-4">THANK YOU!</h1>
            <p class="text-2xl lg:text-3xl">Your total: <span id="final-total" class="font-bold">MVR 0.00</span></p>
            <p class="text-xl lg:text-2xl mt-4">Please come again!</p>
        </div>
    </div>

    <script>
        // ============================================
        // Customer Display WebSocket with Decryption
        // ============================================

        const encryptionKey = 'b7e1f3a49c8d27f2e5a09c341bd6ef75';
        let ws = null;
        let currentItems = {};

        // Decryption function
        function decryptMessage(encryptedMsg) {
            try {
                const decoded = atob(encryptedMsg);

                let ivHex = '';
                for (let i = 0; i < 16; i++) {
                    ivHex += ('0' + decoded.charCodeAt(i).toString(16)).slice(-2);
                }

                const encryptedBase64 = btoa(decoded.slice(16));
                const keyHex = CryptoJS.enc.Utf8.parse(encryptionKey);
                const iv = CryptoJS.enc.Hex.parse(ivHex);

                const decrypted = CryptoJS.AES.decrypt(
                    encryptedBase64,
                    keyHex,
                    { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }
                );

                const decryptedText = decrypted.toString(CryptoJS.enc.Utf8);
                return JSON.parse(decryptedText);
            } catch (error) {
                console.error('Decryption error:', error);
                return null;
            }
        }

        // Connect to WebSocket
        function connectWebSocket() {
            try {
                ws = new WebSocket('wss://iapi.gifty.mv:8081');

                ws.onopen = function() {
                    console.log('Customer Display connected');
                    updateConnectionStatus('connected');
                };

                ws.onmessage = function(event) {
                    console.log('[Customer Display] Raw message received:', event.data);

                    try {
                        const envelope = JSON.parse(event.data);
                        console.log('[Customer Display] Parsed envelope:', envelope);

                        if (envelope.type === 'pos_update' && envelope.encrypted_message) {
                            console.log('[Customer Display] POS update message received, decrypting...');

                            // DEBUG: Show raw unencrypted data if available
                            if (envelope.raw_data) {
                                console.log('[Customer Display] 🔍 DEBUG raw_data:', envelope.raw_data);
                            }

                            const message = decryptMessage(envelope.encrypted_message);

                            if (message) {
                                console.log('[Customer Display] ✅ Decrypted message:', message);
                                console.log('Message type:', message.type);
                                handleMessage(message);
                            } else {
                                console.error('[Customer Display] ❌ Decryption failed');
                            }
                        } else {
                            console.log('[Customer Display] Message not for us. Type:', envelope.type);
                        }
                    } catch (error) {
                        console.error('[Customer Display] ❌ Error processing message:', error);
                    }
                };

                ws.onclose = function() {
                    console.log('WebSocket closed, reconnecting...');
                    updateConnectionStatus('disconnected');
                    setTimeout(connectWebSocket, 5000);
                };

                ws.onerror = function(error) {
                    console.error('WebSocket error:', error);
                    updateConnectionStatus('error');
                };
            } catch (error) {
                console.error('Error connecting:', error);
                setTimeout(connectWebSocket, 5000);
            }
        }

        // Handle incoming messages
        function handleMessage(message) {
            if (message.type === 'cart_update') {
                updateCartDisplay(message.data);
            } else if (message.type === 'sale_complete') {
                showThankYouScreen(message.data.total);
            }
        }

        // Update connection status
        function updateConnectionStatus(status) {
            const statusEl = document.getElementById('connection-status');
            if (status === 'connected') {
                statusEl.innerHTML = '<span class="text-green-600">● Connected</span>';
            } else if (status === 'disconnected') {
                statusEl.innerHTML = '<span class="text-red-600">● Disconnected</span>';
            } else {
                statusEl.innerHTML = '<span class="text-yellow-600">● Connecting...</span>';
            }
        }

        // Update cart display
        function updateCartDisplay(data) {
            const cartItemsEl = document.getElementById('cart-items');
            const cartHeadersEl = document.getElementById('cart-headers');
            const totalsEl = document.getElementById('cart-totals');

            if (!data.items || data.items.length === 0) {
                cartHeadersEl.classList.add('hidden');
                totalsEl.classList.add('hidden');
                cartItemsEl.innerHTML = '<div id="empty-state" class="flex flex-col items-center justify-center h-full text-gray-400"><svg class="w-24 h-24 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg><p class="text-2xl font-light">Scan items to begin</p></div>';
                currentItems = {};
                return;
            }

            cartHeadersEl.classList.remove('hidden');
            totalsEl.classList.remove('hidden');

            let itemsHTML = '';
            const newItems = {};

            // Calculate totals manually
            let subtotalWithoutTax = 0;
            let totalTax = 0;

            data.items.forEach(item => {
                newItems[item.id] = item;

                // Calculate tax amount per item
                const taxAmount = Math.round((item.price_inc_tax - item.price_exc_tax) * item.quantity * 100) / 100;
                const isZeroRated = item.price_exc_tax === item.price_inc_tax;

                // Calculate item subtotal without tax
                const itemSubtotalNoTax = item.price_exc_tax * item.quantity;
                subtotalWithoutTax += itemSubtotalNoTax;
                totalTax += taxAmount;

                const itemHTML = `
                    <div class="item-enter border-b border-gray-200 py-3" data-item-id="${item.id}">
                        <div class="flex items-center justify-between gap-4">
                            <!-- Left: Item name + qty + zero-rated -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="font-bold text-lg text-green-600">${item.quantity}×</span>
                                    <h3 class="text-base font-semibold text-gray-800">${escapeHtml(item.name)}</h3>
                                    ${isZeroRated ? '<span class="text-xs text-green-600 font-semibold ml-2">Zero-rated</span>' : ''}
                                </div>
                            </div>

                            <!-- Middle: Tax amount -->
                            <div class="text-base text-gray-600 text-center" style="min-width: 100px;">
                                ${!isZeroRated ? `MVR ${taxAmount.toFixed(2)}` : '-'}
                            </div>

                            <!-- Right: Line total -->
                            <div class="text-xl font-bold text-gray-900 text-right" style="min-width: 120px;">
                                MVR ${item.line_total.toFixed(2)}
                            </div>
                        </div>
                    </div>
                `;
                itemsHTML += itemHTML;
            });

            cartItemsEl.innerHTML = itemsHTML;
            currentItems = newItems;

            // Auto-scroll to bottom to show newest items (hides oldest from top when overflow)
            setTimeout(() => {
                cartItemsEl.scrollTop = cartItemsEl.scrollHeight;
            }, 100);

            // Update totals
            const finalTotal = subtotalWithoutTax + totalTax;

            document.getElementById('subtotal-amount').textContent = 'MVR ' + subtotalWithoutTax.toFixed(2);
            document.getElementById('tax-amount').textContent = 'MVR ' + totalTax.toFixed(2);
            document.getElementById('total-amount').textContent = 'MVR ' + finalTotal.toFixed(2);

            // Show/hide tax row
            if (totalTax > 0) {
                document.getElementById('tax-row').classList.remove('hidden');
            } else {
                document.getElementById('tax-row').classList.add('hidden');
            }
        }

        // Show thank you screen
        function showThankYouScreen(total) {
            const overlayEl = document.getElementById('thank-you-overlay');
            document.getElementById('final-total').textContent = 'MVR ' + total.toFixed(2);

            overlayEl.classList.remove('hidden');

            setTimeout(function() {
                overlayEl.classList.add('hidden');
                resetDisplay();
            }, 5000);
        }

        // Reset display
        function resetDisplay() {
            currentItems = {};
            const cartItemsEl = document.getElementById('cart-items');
            const cartHeadersEl = document.getElementById('cart-headers');
            const totalsEl = document.getElementById('cart-totals');

            cartItemsEl.innerHTML = '<div id="empty-state" class="flex flex-col items-center justify-center h-full text-gray-400"><svg class="w-24 h-24 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg><p class="text-2xl font-light">Scan items to begin</p></div>';
            cartHeadersEl.classList.add('hidden');
            totalsEl.classList.add('hidden');
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initialize
        connectWebSocket();
    </script>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/pos/customer_display.blade.php ENDPATH**/ ?>