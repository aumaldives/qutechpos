<!-- Bank Transfers Dropdown -->
<div id="bank_transfers_dropdown" class="bank-transfers-dropdown no-print" style="display: none;">
    <div class="bank-transfers-header">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 600;">
                <i class="fa fa-university"></i> Bank Transfers - <span id="bank_account_nickname">AGRO MART</span>
            </h4>
            <button type="button" class="btn btn-sm btn-default" id="close_bank_transfers" style="padding: 2px 8px;">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div style="margin-top: 10px; display: flex; gap: 8px;">
            <button type="button" class="btn btn-primary btn-xs" id="refresh_transfers_btn">
                <i class="fa fa-sync-alt"></i> Refresh
            </button>
            <button type="button" class="btn btn-success btn-xs" id="force_update_btn">
                <i class="fa fa-bolt"></i> Force Update
            </button>
        </div>
    </div>
    <div class="bank-transfers-body">
        <div id="bank_transfers_loading" style="text-align: center; padding: 40px;">
            <i class="fa fa-spinner fa-spin fa-2x" style="color: #667eea;"></i>
            <p style="margin-top: 15px; color: #666; font-size: 13px;">Loading transfers...</p>
        </div>
        <div id="bank_transfers_list" style="display: none;">
            <!-- Transfers will be loaded here -->
        </div>
        <div id="bank_transfers_error" style="display: none; text-align: center; padding: 40px; color: #e74c3c;">
            <i class="fa fa-exclamation-triangle fa-2x"></i>
            <p style="margin-top: 15px; font-size: 13px;">Failed to load transfers. Please try again.</p>
        </div>
    </div>
</div>

<!-- Overlay for closing dropdown -->
<div id="bank_transfers_overlay" class="bank-transfers-overlay no-print" style="display: none;"></div>

<style>
/* Dropdown positioning */
.bank-transfers-dropdown {
    position: fixed;
    top: 60px;
    right: 20px;
    width: 450px;
    max-width: calc(100vw - 40px);
    max-height: calc(100vh - 80px);
    background: white;
    border-radius: 8px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    animation: slideInDown 0.3s ease-out;
    display: flex;
    flex-direction: column;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100%);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.animate-slideInRight {
    animation: slideInRight 0.4s ease-out;
}

.bank-transfers-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: transparent;
    z-index: 9998;
    pointer-events: none;
}

.bank-transfers-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    border-radius: 8px 8px 0 0;
    flex-shrink: 0;
}

.bank-transfers-body {
    overflow-y: auto;
    flex: 1;
    min-height: 0;
}

/* Transfer card styles */
.transfer-card {
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 12px;
    margin: 8px 12px;
    background: white;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.transfer-card:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.transfer-card.used {
    background: #f9f9f9;
    opacity: 0.6;
}

.transfer-card.usable {
    border-left: 3px solid #10b981;
}

.transfer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.transfer-name {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

.transfer-amount {
    font-size: 16px;
    font-weight: 700;
    color: #10b981;
}

.transfer-details {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 10px;
}

.transfer-detail-item {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: #666;
}

.transfer-detail-item i {
    width: 16px;
    margin-right: 6px;
    color: #667eea;
    font-size: 11px;
}

.transfer-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.transfer-status.usable {
    background: #d1fae5;
    color: #065f46;
}

.transfer-status.used {
    background: #fee2e2;
    color: #991b1b;
}

.transfer-actions {
    display: flex;
    gap: 6px;
    margin-top: 10px;
}

.transfer-actions .btn {
    flex: 1;
    font-size: 12px;
    padding: 6px 10px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .bank-transfers-dropdown {
        width: calc(100vw - 20px);
        right: 10px;
        top: 10px;
        max-height: calc(100vh - 20px);
    }
}
</style>

<!-- CryptoJS Library for AES Decryption -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

<script>
(function() {
    var bankTransfersInterval = null;
    var bankTransfersApiUrl = '{{ route("bank-transfers.get-transfers") }}';
    var bankTransfersMarkPaymentUrl = '{{ route("bank-transfers.mark-payment-status") }}';
    var bankTransfersForceCheckUrl = '{{ route("bank-transfers.force-check") }}';
    var currentTransactions = {}; // Store current transactions by ID
    window.ws = null; // WebSocket connection - exposed globally for customer display
    var wsReconnectTimeout = null;

    // Decryption function using CryptoJS
    function decryptMessage(encryptedMsg, key = 'b7e1f3a49c8d27f2e5a09c341bd6ef75') {
        try {
            // Decode the outer base64
            const decoded = atob(encryptedMsg);

            // Extract IV (first 16 bytes) as hex
            let ivHex = '';
            for (let i = 0; i < 16; i++) {
                ivHex += ('0' + decoded.charCodeAt(i).toString(16)).slice(-2);
            }

            // The rest is the base64 encrypted data from openssl_encrypt
            const encryptedBase64 = btoa(decoded.slice(16));

            // Convert key and IV to CryptoJS format
            const keyHex = CryptoJS.enc.Utf8.parse(key);
            const iv = CryptoJS.enc.Hex.parse(ivHex);

            // Decrypt
            const decrypted = CryptoJS.AES.decrypt(
                encryptedBase64,
                keyHex,
                {
                    iv: iv,
                    mode: CryptoJS.mode.CBC,
                    padding: CryptoJS.pad.Pkcs7
                }
            );

            // Convert to string and parse JSON
            const decryptedText = decrypted.toString(CryptoJS.enc.Utf8);
            return JSON.parse(decryptedText);
        } catch (error) {
            console.error('Decryption error:', error);
            return null;
        }
    }

    // Encryption function - exposed globally for customer display
    window.encryptMessage = function(message, key = 'b7e1f3a49c8d27f2e5a09c341bd6ef75') {
        try {
            var iv = CryptoJS.lib.WordArray.random(16);
            var keyParsed = CryptoJS.enc.Utf8.parse(key);
            var encrypted = CryptoJS.AES.encrypt(
                JSON.stringify(message),
                keyParsed,
                { iv: iv, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }
            );
            var encryptedData = iv.concat(encrypted.ciphertext);
            return encryptedData.toString(CryptoJS.enc.Base64);
        } catch (error) {
            console.error('Encryption error:', error);
            return null;
        }
    };

    // Connect to WebSocket
    function connectWebSocket() {
        if (window.ws && window.ws.readyState === WebSocket.OPEN) {
            return; // Already connected
        }

        try {
            window.ws = new WebSocket('wss://iapi.gifty.mv:8081');

            window.ws.onopen = function() {
                console.log('WebSocket connected (Bank Transfers + Customer Display)');
                if (wsReconnectTimeout) {
                    clearTimeout(wsReconnectTimeout);
                    wsReconnectTimeout = null;
                }
            };

            window.ws.onmessage = function(event) {
                try {
                    const envelope = JSON.parse(event.data);

                    // Ignore pos_update messages (those are for customer display screen)
                    if (envelope.type === 'pos_update') {
                        return;
                    }

                    // Bank transfers messages
                    if (envelope.encrypt_msg) {
                        const decrypted = decryptMessage(envelope.encrypt_msg);
                        if (decrypted && Array.isArray(decrypted)) {
                            console.log('New bank transfers received:', decrypted);
                            handleNewTransfers(decrypted);
                        }
                    }
                } catch (error) {
                    console.error('Error processing WebSocket message:', error);
                }
            };

            window.ws.onerror = function(error) {
                console.error('WebSocket error:', error);
            };

            window.ws.onclose = function() {
                console.log('WebSocket connection closed. Reconnecting in 5 seconds...');
                wsReconnectTimeout = setTimeout(connectWebSocket, 5000);
            };
        } catch (error) {
            console.error('Error connecting to WebSocket:', error);
            wsReconnectTimeout = setTimeout(connectWebSocket, 5000);
        }
    }

    // Handle new transfers from WebSocket
    function handleNewTransfers(newTransfers) {
        newTransfers.forEach(function(transfer) {
            // Check if this is for our account
            if (transfer.account_code === '7730000777869') {
                // Show notification
                showTransferNotification(transfer);

                // Refresh the list to get the full transaction details
                loadBankTransfers();
            }
        });
    }

    // Play notification sound
    function playNotificationSound() {
        try {
            // Create audio context for notification sound
            var audioContext = new (window.AudioContext || window.webkitAudioContext)();
            var oscillator = audioContext.createOscillator();
            var gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Configure sound - pleasant notification tone
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            gainNode.gain.value = 0.3;

            // Play two quick beeps
            oscillator.start(audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
            oscillator.stop(audioContext.currentTime + 0.1);

            // Second beep
            setTimeout(function() {
                var oscillator2 = audioContext.createOscillator();
                var gainNode2 = audioContext.createGain();
                oscillator2.connect(gainNode2);
                gainNode2.connect(audioContext.destination);
                oscillator2.frequency.value = 1000;
                oscillator2.type = 'sine';
                gainNode2.gain.value = 0.3;
                oscillator2.start(audioContext.currentTime);
                gainNode2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
                oscillator2.stop(audioContext.currentTime + 0.15);
            }, 150);
        } catch (error) {
            console.error('Error playing notification sound:', error);
        }
    }

    // Copy to clipboard function (global scope)
    window.copyBankReference = function(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                // Show brief success message
                var successMsg = $('<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; z-index: 10001; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">Reference Copied!</div>');
                $('body').append(successMsg);
                setTimeout(function() {
                    successMsg.fadeOut(200, function() { $(this).remove(); });
                }, 1500);
            }).catch(function(err) {
                console.error('Failed to copy:', err);
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                var successMsg = $('<div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; z-index: 10001; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">Reference Copied!</div>');
                $('body').append(successMsg);
                setTimeout(function() {
                    successMsg.fadeOut(200, function() { $(this).remove(); });
                }, 1500);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            document.body.removeChild(textArea);
        }
    };

    // Show notification for new transfer
    function showTransferNotification(transfer) {
        // Play notification sound
        playNotificationSound();

        var notificationHtml = '<div class="bank-transfer-notification no-print" style="position: fixed; top: 15px; right: 15px; z-index: 10000; width: 320px; max-width: calc(100vw - 30px); background: white; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); border-left: 4px solid #10b981; overflow: hidden; animation: slideInRight 0.4s ease-out; cursor: pointer;" onclick="copyBankReference(\'' + escapeHtml(transfer.reference).replace(/'/g, "\\'") + '\')">';

        // Header with gradient
        notificationHtml += '  <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 10px 12px; display: flex; align-items: center; justify-content: space-between;">';
        notificationHtml += '    <div style="display: flex; align-items: center; gap: 8px;">';
        notificationHtml += '      <div style="background: rgba(255,255,255,0.2); padding: 6px; border-radius: 6px; display: flex; align-items: center; justify-content: center;">';
        notificationHtml += '        <i class="fa fa-university" style="color: white; font-size: 14px;"></i>';
        notificationHtml += '      </div>';
        notificationHtml += '      <h3 style="color: white; font-weight: 700; font-size: 14px; margin: 0;">New Bank Transfer</h3>';
        notificationHtml += '    </div>';
        notificationHtml += '    <button type="button" style="background: transparent; border: none; color: white; cursor: pointer; padding: 4px 6px; border-radius: 50%; transition: background 0.2s;" onmouseover="this.style.background=\'rgba(255,255,255,0.2)\'" onmouseout="this.style.background=\'transparent\'" onclick="event.stopPropagation(); $(this).closest(\'.bank-transfer-notification\').fadeOut(200, function(){ $(this).remove(); })">';
        notificationHtml += '      <i class="fa fa-times" style="font-size: 14px;"></i>';
        notificationHtml += '    </button>';
        notificationHtml += '  </div>';

        // Body content
        notificationHtml += '  <div style="padding: 12px;">';

        // Name - Large
        notificationHtml += '    <div style="margin-bottom: 10px;">';
        notificationHtml += '      <p style="color: #111827; font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2;">' + escapeHtml(transfer.name) + '</p>';
        notificationHtml += '    </div>';

        // Amount - Large
        notificationHtml += '    <div style="margin-bottom: 10px;">';
        notificationHtml += '      <p style="color: #10b981; font-size: 18px; font-weight: 800; margin: 0; line-height: 1.2;">MVR ' + parseFloat(transfer.amount).toFixed(2) + '</p>';
        notificationHtml += '    </div>';

        // Date Time & Reference - smaller, side by side
        notificationHtml += '    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px; color: #6b7280;">';
        notificationHtml += '      <span style="display: flex; align-items: center; gap: 4px;">';
        notificationHtml += '        <i class="fa fa-clock" style="font-size: 10px;"></i>';
        notificationHtml += '        <span>' + escapeHtml(transfer.datetime) + '</span>';
        notificationHtml += '      </span>';
        notificationHtml += '      <span style="display: flex; align-items: center; gap: 4px;">';
        notificationHtml += '        <i class="fa fa-copy" style="font-size: 10px;"></i>';
        notificationHtml += '        <span>' + escapeHtml(transfer.reference) + '</span>';
        notificationHtml += '      </span>';
        notificationHtml += '    </div>';

        notificationHtml += '  </div>';

        notificationHtml += '</div>';

        var $notification = $(notificationHtml);
        $('body').append($notification);

        // Auto dismiss after 10 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 10000);
    }

    // Wait for jQuery to be loaded
    function initBankTransfers() {
        var isDropdownOpen = false;

        // Connect to WebSocket on init
        connectWebSocket();

        // Open dropdown
        $(document).on('click', '#bank_transfers_btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (isDropdownOpen) {
                // If already open, close it
                closeBankTransfersDropdown();
            } else {
                // Open it
                $('#bank_transfers_dropdown').fadeIn(200);
                isDropdownOpen = true;
                loadBankTransfers();
                startAutoRefresh();
            }
        });

        // Close dropdown function
        function closeBankTransfersDropdown() {
            $('#bank_transfers_dropdown').fadeOut(200);
            isDropdownOpen = false;
            stopAutoRefresh();
        }

        // Close dropdown - stop auto refresh (only on close button)
        $(document).on('click', '#close_bank_transfers', function(e) {
            e.stopPropagation();
            closeBankTransfersDropdown();
        });

        // Close on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && isDropdownOpen) {
                closeBankTransfersDropdown();
            }
        });

        // Prevent dropdown from closing when clicking inside
        $(document).on('click', '#bank_transfers_dropdown', function(e) {
            e.stopPropagation();
        });

        // Manual refresh
        $(document).on('click', '#refresh_transfers_btn', function() {
            loadBankTransfers();
        });

        // Force update
        $(document).on('click', '#force_update_btn', function() {
            var btn = $(this);
            btn.prop('disabled', true);
            btn.html('<i class="fa fa-spinner fa-spin"></i> Checking...');

            $.ajax({
                url: bankTransfersForceCheckUrl,
                type: 'POST',
                data: {
                    account_code: '7730000777869',
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    // After force check, reload the transfers
                    setTimeout(function() {
                        loadBankTransfers();
                        btn.prop('disabled', false);
                        btn.html('<i class="fa fa-bolt"></i> Force Update');
                    }, 1000);
                },
                error: function(xhr, status, error) {
                    console.error('Error force checking:', error);
                    btn.prop('disabled', false);
                    btn.html('<i class="fa fa-bolt"></i> Force Update');
                    alert('Failed to force update. Please try again.');
                }
            });
        });

        // Mark payment as used
        $(document).on('click', '.btn-mark-used', function() {
            var transactionId = $(this).data('transaction-id');
            var reference = $(this).data('reference');
            var button = $(this);

            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin"></i> Processing...');

            markPaymentStatus(transactionId, 0, function(success) {
                if (success) {
                    // Copy reference to clipboard
                    if (reference) {
                        window.copyBankReference(reference);
                    }
                    loadBankTransfers();
                } else {
                    button.prop('disabled', false);
                    button.html('<i class="fa fa-check"></i> Mark as Used');
                    alert('Failed to mark payment as used. Please try again.');
                }
            });
        });

        // Release payment
        $(document).on('click', '.btn-release', function() {
            var transactionId = $(this).data('transaction-id');
            var button = $(this);

            button.prop('disabled', true);
            button.html('<i class="fa fa-spinner fa-spin"></i> Processing...');

            markPaymentStatus(transactionId, 1, function(success) {
                if (success) {
                    loadBankTransfers();
                } else {
                    button.prop('disabled', false);
                    button.html('<i class="fa fa-undo"></i> Release');
                    alert('Failed to release payment. Please try again.');
                }
            });
        });
    }

    // Start auto refresh every 15 seconds
    function startAutoRefresh() {
        stopAutoRefresh();
        bankTransfersInterval = setInterval(function() {
            loadBankTransfers();
        }, 15000);
    }

    // Stop auto refresh
    function stopAutoRefresh() {
        if (bankTransfersInterval) {
            clearInterval(bankTransfersInterval);
            bankTransfersInterval = null;
        }
    }

    // Load bank transfers from API
    function loadBankTransfers() {
        var isFirstLoad = Object.keys(currentTransactions).length === 0;

        if (isFirstLoad) {
            $('#bank_transfers_loading').show();
            $('#bank_transfers_list').hide();
            $('#bank_transfers_error').hide();
        }

        $.ajax({
            url: bankTransfersApiUrl,
            type: 'GET',
            data: {
                account_code: '7730000777869'
            },
            success: function(response) {
                if (response && response.transactions) {
                    // Limit to 10 most recent transactions
                    var limitedTransactions = response.transactions.slice(0, 10);
                    updateTransfers(limitedTransactions, response.nickname);
                    $('#bank_account_nickname').text(response.nickname || 'AGRO MART');

                    if (isFirstLoad) {
                        $('#bank_transfers_loading').hide();
                        $('#bank_transfers_list').show();
                    }
                } else {
                    showError();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading bank transfers:', error);
                if (isFirstLoad) {
                    showError();
                }
            }
        });
    }

    // Update transfers incrementally
    function updateTransfers(transactions, nickname) {
        var newTransactions = {};
        var $container = $('#bank_transfers_list');

        // Reverse transactions array to show newest first
        transactions = transactions.slice().reverse();

        // Build a map of new transactions
        transactions.forEach(function(transaction) {
            newTransactions[transaction.id] = transaction;
        });

        // Remove transactions that no longer exist
        Object.keys(currentTransactions).forEach(function(id) {
            if (!newTransactions[id]) {
                $('#transfer-card-' + id).fadeOut(300, function() {
                    $(this).remove();
                });
                delete currentTransactions[id];
            }
        });

        // Update or add transactions
        if (transactions.length === 0 && Object.keys(currentTransactions).length === 0) {
            $container.html('<div style="text-align: center; padding: 50px; color: #999;"><i class="fa fa-inbox fa-3x"></i><p style="margin-top: 20px;">No transfers found</p></div>');
        } else {
            // Remove "no transfers" message if exists
            $container.find('.fa-inbox').closest('div').remove();

            transactions.forEach(function(transaction) {
                var $existingCard = $('#transfer-card-' + transaction.id);

                if ($existingCard.length > 0) {
                    // Update existing card if status changed
                    var oldTransaction = currentTransactions[transaction.id];
                    if (oldTransaction && oldTransaction.status !== transaction.status) {
                        updateTransactionCard($existingCard, transaction);
                    }
                } else {
                    // Add new card
                    var cardHtml = buildTransactionCard(transaction);
                    $container.prepend(cardHtml);
                    $('#transfer-card-' + transaction.id).hide().fadeIn(300);
                }

                currentTransactions[transaction.id] = transaction;
            });
        }
    }

    // Build transaction card HTML
    function buildTransactionCard(transaction) {
        var isUsable = transaction.status == 1;
        var statusClass = isUsable ? 'usable' : 'used';
        var cardClass = isUsable ? 'usable' : 'used';

        var html = '<div class="transfer-card ' + cardClass + '" id="transfer-card-' + transaction.id + '">';
        html += '    <div class="transfer-header">';
        html += '        <div class="transfer-name">' + escapeHtml(transaction.name) + '</div>';
        html += '        <div class="transfer-amount">MVR ' + parseFloat(transaction.amount).toFixed(2) + '</div>';
        html += '    </div>';
        html += '    <div class="transfer-details">';
        html += '        <div class="transfer-detail-item">';
        html += '            <i class="fa fa-hashtag"></i>';
        html += '            <span><strong>Reference:</strong> ' + escapeHtml(transaction.reference) + '</span>';
        html += '        </div>';
        html += '        <div class="transfer-detail-item">';
        html += '            <i class="fa fa-clock"></i>';
        html += '            <span><strong>Date:</strong> ' + escapeHtml(transaction.datetime) + '</span>';
        html += '        </div>';
        html += '        <div class="transfer-detail-item">';
        html += '            <i class="fa fa-tag"></i>';
        html += '            <span><strong>Type:</strong> ' + escapeHtml(transaction.type) + '</span>';
        html += '        </div>';
        html += '    </div>';
        html += '    <div class="transfer-actions">';
        if (isUsable) {
            html += '        <button type="button" class="btn btn-success btn-mark-used" data-transaction-id="' + transaction.id + '" data-reference="' + escapeHtml(transaction.reference) + '">';
            html += '            <i class="fa fa-check"></i> Mark as Used';
            html += '        </button>';
        } else {
            html += '        <button type="button" class="btn btn-warning btn-release" data-transaction-id="' + transaction.id + '">';
            html += '            <i class="fa fa-undo"></i> Release';
            html += '        </button>';
        }
        html += '    </div>';
        html += '</div>';

        return html;
    }

    // Update existing transaction card
    function updateTransactionCard($card, transaction) {
        var isUsable = transaction.status == 1;
        var statusClass = isUsable ? 'usable' : 'used';

        // Update card class
        $card.removeClass('usable used').addClass(statusClass);

        // Update action button
        var $actions = $card.find('.transfer-actions');
        if (isUsable) {
            $actions.html('<button type="button" class="btn btn-success btn-mark-used" data-transaction-id="' + transaction.id + '" data-reference="' + escapeHtml(transaction.reference) + '"><i class="fa fa-check"></i> Mark as Used</button>');
        } else {
            $actions.html('<button type="button" class="btn btn-warning btn-release" data-transaction-id="' + transaction.id + '"><i class="fa fa-undo"></i> Release</button>');
        }
    }

    // Show error message
    function showError() {
        $('#bank_transfers_loading').hide();
        $('#bank_transfers_list').hide();
        $('#bank_transfers_error').show();
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Mark payment status API call
    function markPaymentStatus(transactionId, status, callback) {
        $.ajax({
            url: bankTransfersMarkPaymentUrl,
            type: 'POST',
            data: {
                transaction_id: transactionId,
                status: status,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                callback(true);
            },
            error: function(xhr, status, error) {
                console.error('Error marking payment status:', error);
                callback(false);
            }
        });
    }

    // Initialize when document is ready
    if (typeof jQuery !== 'undefined') {
        $(document).ready(function() {
            initBankTransfers();
        });
    } else {
        console.error('jQuery is not loaded. Bank Transfers feature will not work.');
    }
})();
</script>
