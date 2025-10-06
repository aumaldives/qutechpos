<style>
    .bank-transfer-container {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 2rem;
        margin: 1rem 0;
    }

    .bank-transfer-title {
        text-align: center;
        margin-bottom: 2rem;
        color: #2c3e50;
        font-weight: 600;
        font-size: 1.8rem;
    }

    .bank-selection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
        justify-content: center;
    }

    .bank-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        cursor: pointer;
        border: 3px solid transparent;
        position: relative;
        overflow: hidden;
        max-width: 380px;
        margin: 0 auto;
    }

    .bank-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }

    .bank-card.selected {
        border-color: #3498db;
        box-shadow: 0 8px 24px rgba(52, 152, 219, 0.2);
    }

    .bank-card.selected::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3498db, #2980b9);
    }

    .bank-card-logo {
        width: 320px;
        height: 180px;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #f8f9fa;
        padding: 30px;
        border: 1px solid #e9ecef;
    }
    
    .bank-card-logo img {
        max-width: 260px;
        max-height: 140px;
        object-fit: contain;
        display: block;
    }

    .bank-name {
        text-align: center;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }

    .bank-select-btn {
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .bank-select-btn:hover {
        background: linear-gradient(135deg, #2980b9, #1f5f8b);
        transform: translateY(-1px);
    }

    .account-selection-section {
        display: none;
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-top: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border: 1px solid #e9ecef;
    }

    .account-selection-section.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .currency-tabs {
        display: flex;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.25rem;
        margin-bottom: 1.5rem;
        gap: 0.25rem;
    }

    .currency-tab {
        flex: 1;
        padding: 0.75rem;
        background: transparent;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        color: #6c757d;
    }

    .currency-tab.active {
        background: white;
        color: #3498db;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .account-details {
        display: none;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #3498db;
    }

    .account-details.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .account-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .account-info-item {
        background: white;
        padding: 1.2rem;
        border-radius: 10px;
        border: 2px solid #e9ecef;
        min-height: 65px;
    }

    .account-info-label {
        font-size: 1rem;
        color: #6c757d;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .account-info-value {
        font-size: 1.4rem;
        font-weight: 700;
        color: #2c3e50;
        font-family: 'Courier New', monospace;
        line-height: 1.3;
    }

    .copy-btn {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .copy-btn:hover {
        background: linear-gradient(135deg, #20c997, #17a085);
        transform: translateY(-1px);
    }

    .upload-section {
        background: white;
        border: 3px dashed #dee2e6;
        border-radius: 15px;
        padding: 3rem 2rem;
        text-align: center;
        margin: 2rem 0;
        transition: all 0.3s ease;
        position: relative;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .upload-section:hover {
        border-color: #3498db;
        background: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(52, 152, 219, 0.1);
    }

    .upload-section.dragover {
        border-color: #3498db;
        background: #e3f2fd;
        transform: scale(1.02);
    }

    .upload-icon {
        font-size: 4rem;
        color: #3498db;
        margin-bottom: 1.5rem;
    }

    .upload-text {
        color: #2c3e50;
        margin-bottom: 2rem;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .file-input {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        top: 0;
        left: 0;
    }

    .file-input-label {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        padding: 1rem 2rem;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-block;
        font-weight: 600;
        font-size: 1.1rem;
        border: none;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    }

    .file-input-label:hover {
        background: linear-gradient(135deg, #2980b9, #1f5f8b);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
    }

    .selected-file {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 0.75rem 1rem;
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        border: none;
        border-radius: 8px;
        color: white;
        display: none;
        font-weight: 600;
        box-shadow: 0 2px 10px rgba(39, 174, 96, 0.3);
        z-index: 10;
    }

    .submit-section {
        text-align: center;
        margin-top: 2rem;
    }

    .submit-btn {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 200px;
        justify-content: center;
    }

    .submit-btn:hover {
        background: linear-gradient(135deg, #c0392b, #a93226);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(231, 76, 60, 0.3);
    }

    .submit-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .instructions {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 1.5rem;
        margin: 1.5rem 0;
        color: #856404;
    }

    .instructions-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #b8860b;
    }

    @media (max-width: 768px) {
        .bank-selection-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .bank-transfer-container {
            padding: 1rem;
        }
        
        .account-info-grid {
            grid-template-columns: 1fr;
        }
        
        .currency-tabs {
            flex-direction: column;
        }
    }
</style>

<div class="bank-transfer-container">
    <h2 class="bank-transfer-title">
        <i class="fas fa-university" style="margin-right: 0.5rem; color: #3498db;"></i>
        Bank Transfer Payment
    </h2>

    <div class="bank-selection-grid">
        <div class="bank-card" data-bank="bml">
            <div class="bank-card-logo">
                <img src="/img/bml.png" alt="Bank of Maldives">
            </div>
            <div class="bank-name">Bank of Maldives</div>
            <button type="button" class="bank-select-btn" data-bank="bml">
                <i class="fas fa-credit-card"></i>
                Select BML Account
            </button>
        </div>

        <div class="bank-card" data-bank="mib">
            <div class="bank-card-logo">
                <img src="/img/MIB.png" alt="Maldives Islamic Bank">
            </div>
            <div class="bank-name">Maldives Islamic Bank</div>
            <button type="button" class="bank-select-btn" data-bank="mib">
                <i class="fas fa-university"></i>
                Select MIB Account
            </button>
        </div>
    </div>

    <div class="account-selection-section" id="account-section">
        <h4 style="color: #2c3e50; margin-bottom: 1.5rem; text-align: center;">
            <i class="fas fa-info-circle" style="color: #3498db; margin-right: 0.5rem;"></i>
            Select Currency & Account Details
        </h4>

        <div class="currency-tabs">
            <button type="button" class="currency-tab active" data-currency="mvr">
                <i class="fas fa-coins"></i> MVR Account
            </button>
            <button type="button" class="currency-tab" data-currency="usd">
                <i class="fas fa-dollar-sign"></i> USD Account
            </button>
        </div>

        <!-- MVR Account Details -->
        <div class="account-details active" id="mvr-details">
            <div class="account-info-grid">
                <div class="account-info-item">
                    <div class="account-info-label">Account Name</div>
                    <div class="account-info-value" id="mvr-account-name">-</div>
                </div>
                <div class="account-info-item">
                    <div class="account-info-label">Account Number</div>
                    <div class="account-info-value" id="mvr-account-number">-</div>
                </div>
                <div class="account-info-item">
                    <div class="account-info-label">Currency</div>
                    <div class="account-info-value">MVR</div>
                </div>
            </div>
            <div style="text-align: center;">
                <button type="button" class="copy-btn" id="copy-mvr-account">
                    <i class="fas fa-copy"></i> Copy Account Number
                </button>
            </div>
        </div>

        <!-- USD Account Details -->
        <div class="account-details" id="usd-details">
            <div class="account-info-grid">
                <div class="account-info-item">
                    <div class="account-info-label">Account Name</div>
                    <div class="account-info-value" id="usd-account-name">-</div>
                </div>
                <div class="account-info-item">
                    <div class="account-info-label">Account Number</div>
                    <div class="account-info-value" id="usd-account-number">-</div>
                </div>
                <div class="account-info-item">
                    <div class="account-info-label">Currency</div>
                    <div class="account-info-value">USD</div>
                </div>
            </div>
            <div style="text-align: center;">
                <button type="button" class="copy-btn" id="copy-usd-account">
                    <i class="fas fa-copy"></i> Copy Account Number
                </button>
            </div>
        </div>


        <div class="instructions">
            <div class="instructions-title">
                <i class="fas fa-exclamation-triangle"></i> Transfer Instructions
            </div>
            <ol style="margin: 0.5rem 0 0 1.5rem;">
                <li>Transfer the subscription amount to the selected bank account</li>
                <li>Take a screenshot or photo of the transfer receipt</li>
                <li>Upload the receipt using the form below</li>
                <li>Your subscription will be activated after verification</li>
            </ol>
        </div>

        <form action="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'confirm'], [$package->id]) }}" method="POST" enctype="multipart/form-data" id="payment-form">
            {{ csrf_field() }}
            <input type="hidden" name="gateway" value="{{$k}}">
            <input type="hidden" name="selected_bank" id="selected-bank">
            <input type="hidden" name="selected_currency" id="selected-currency">

            <div class="upload-section" id="upload-section">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-text">
                    <strong>Upload Payment Receipt</strong><br>
                    Drag and drop your receipt here or click anywhere to browse
                </div>
                <div class="file-input-label">
                    <i class="fas fa-folder-open"></i> Click to Select File
                </div>
                <input type="file" name="receipt_upload" id="receipt_upload" class="file-input" accept="image/*,.pdf" required>
                <div class="selected-file" id="selected-file">
                    <i class="fas fa-check-circle"></i>
                    <span id="file-name"></span>
                    <button type="button" id="remove-file" style="margin-left: 0.5rem; background: none; border: none; color: white; font-size: 1.1rem;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="submit-section">
                <button type="submit" class="submit-btn" id="submit-btn" disabled>
                    <i class="fas fa-handshake"></i>
                    Subscribe Now
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bank account data from backend
    const bankAccounts = {
        bml: {
            usd: {
                name: @json($bank_settings['bml_usd_account_name']),
                number: @json($bank_settings['bml_usd_account_number'])
            },
            mvr: {
                name: @json($bank_settings['bml_mvr_account_name']),
                number: @json($bank_settings['bml_mvr_account_number'])
            }
        },
        mib: {
            usd: {
                name: @json($bank_settings['mib_usd_account_name']),
                number: @json($bank_settings['mib_usd_account_number'])
            },
            mvr: {
                name: @json($bank_settings['mib_mvr_account_name']),
                number: @json($bank_settings['mib_mvr_account_number'])
            }
        }
    };

    let selectedBank = null;
    let selectedCurrency = 'mvr';

    // Bank selection
    document.querySelectorAll('.bank-select-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const bank = this.getAttribute('data-bank');
            selectBank(bank);
        });
    });

    // Currency tab switching
    document.querySelectorAll('.currency-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const currency = this.getAttribute('data-currency');
            switchCurrency(currency);
        });
    });

    // Copy account number functionality
    document.getElementById('copy-usd-account').addEventListener('click', function() {
        copyAccountNumber('usd');
    });

    document.getElementById('copy-mvr-account').addEventListener('click', function() {
        copyAccountNumber('mvr');
    });

    // File upload handling
    const fileInput = document.getElementById('receipt_upload');
    const uploadSection = document.getElementById('upload-section');
    const selectedFile = document.getElementById('selected-file');
    const fileName = document.getElementById('file-name');
    const removeFileBtn = document.getElementById('remove-file');
    const submitBtn = document.getElementById('submit-btn');

    fileInput.addEventListener('change', handleFileSelect);
    removeFileBtn.addEventListener('click', removeFile);

    // Drag and drop
    uploadSection.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadSection.classList.add('dragover');
    });

    uploadSection.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadSection.classList.remove('dragover');
    });

    uploadSection.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadSection.classList.remove('dragover');
        fileInput.files = e.dataTransfer.files;
        handleFileSelect();
    });

    function selectBank(bank) {
        selectedBank = bank;
        
        // Update UI
        document.querySelectorAll('.bank-card').forEach(card => {
            card.classList.remove('selected');
        });
        document.querySelector(`[data-bank="${bank}"]`).classList.add('selected');
        
        // Show account section
        document.getElementById('account-section').classList.add('show');
        
        // Update account details
        updateAccountDetails();
        
        // Update hidden form field
        document.getElementById('selected-bank').value = bank;
        
        // Scroll to account section
        document.getElementById('account-section').scrollIntoView({ behavior: 'smooth' });
    }

    function switchCurrency(currency) {
        selectedCurrency = currency;
        
        // Update currency tabs
        document.querySelectorAll('.currency-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-currency="${currency}"]`).classList.add('active');
        
        // Update account details visibility
        document.querySelectorAll('.account-details').forEach(details => {
            details.classList.remove('active');
        });
        document.getElementById(`${currency}-details`).classList.add('active');
        
        // Update account details
        updateAccountDetails();
        
        // Update hidden form field
        document.getElementById('selected-currency').value = currency;
    }

    function updateAccountDetails() {
        if (!selectedBank) return;
        
        const account = bankAccounts[selectedBank][selectedCurrency];
        document.getElementById(`${selectedCurrency}-account-name`).textContent = account.name;
        document.getElementById(`${selectedCurrency}-account-number`).textContent = account.number;
    }

    function copyAccountNumber(currency) {
        if (!selectedBank) return;
        
        const accountNumber = bankAccounts[selectedBank][currency].number;
        navigator.clipboard.writeText(accountNumber).then(() => {
            // Show success message
            const btn = document.getElementById(`copy-${currency}-account`);
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            btn.style.background = 'linear-gradient(135deg, #27ae60, #2ecc71)';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            }, 2000);
        });
    }

    function handleFileSelect() {
        const file = fileInput.files[0];
        if (file) {
            fileName.textContent = file.name;
            selectedFile.style.display = 'block';
            submitBtn.disabled = false;
        }
    }

    function removeFile() {
        fileInput.value = '';
        selectedFile.style.display = 'none';
        submitBtn.disabled = true;
    }
});
</script>