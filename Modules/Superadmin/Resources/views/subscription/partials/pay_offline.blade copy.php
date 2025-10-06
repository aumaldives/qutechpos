<style>
    .bank-logo {
        width: 300px;
        height: auto;
        cursor: pointer;
    }

    .bank-container {
        text-align: center;
        margin-bottom: 20px;
    }

    .bank-item {
        display: inline-block;
        margin: 20px;
        text-align: center;
    }

    .dropdown-account-container {
        display: none;
        text-align: center;
        margin-top: 20px;
        position: relative;
    }

    .dropdown-account-menu {
        width: 100%;
        margin: 0 auto;
        text-align: left;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
    }

    .account-info {
        display: none;
        margin-top: 20px;
        text-align: center;
    }

    .btn-copy {
        margin-top: 20px;
    }

    .btn-submit {
        margin-top: 20px;
    }

    body {
        background-color: #f9f9f9;
        color: #333;
    }

    .dropdown-account-toggle {
        width: 300px;
    }

    .dropdown-account-menu li a {
        text-align: center;
    }

    .form-group {
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .form-group label {
        margin-right: 10px;
    }

    .btn-submit {
        margin-top: 20px;
        background-color: #28a745;
        color: white;
        font-weight: bold;
    }

    .bank-info-container{
        display: none;
    }

    .browse-button-margin{
        margin-left:90px;
    }

    @media only screen and (max-width: 600px) {
        .dropdown-account-toggle {
        width: 100%;
        }

        .bank-logo {
        width: 100%;
       
    }
    }
</style>

<div class="col-md-12">
    <div class="container">
        <div class="bank-container">
            <div class="row">
                <div class="col-sm-6 col-md-6">
                    <div class="thumbnail">
                        <img src="/img/bml.png" alt="Bank 1" class="bank-logo" data-bank="bank1">
                        <div class="caption">
                            <h3>Bank Of Maldives</h3>
                            <p><button class="btn btn-primary bank-logo" data-bank="bank1" role="button">View Bank Details</button> </p>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-6">
                    <div class="thumbnail">
                        <img src="/img/MIB.png" alt="Bank 2" class="bank-logo" data-bank="bank2">
                        <div class="caption">
                            <h3>Maldives Islamic Bank</h3>

                            <p><button class="btn btn-primary bank-logo" data-bank="bank2" role="button">View Bank Details</button></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row bank-info-container" id="bank-info-container">
                <div class="col-sm-6 col-md-12">
                    <div class="thumbnail">
                        <div class="caption">
                            <h3>Account Information</h3>
                            <div class="dropdown-account-container" id="dropdown-account-container">
                                <div id="bank-dropdown" class="dropdown">
                                    <button class="btn btn-primary dropdown-account-toggle" type="button" id="dropdownAccountMenu1" data-toggle="dropdown">
                                        Select Account
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-account-menu" role="menu" aria-labelledby="dropdownAccountMenu1">
                                    </ul>
                                </div>
                            </div>

                            <div class="account-info" id="account-info">
                                <p><strong>Account Name:</strong> <span id="account-name">-</span></p>
                                <p><strong>Account Number:</strong> <span id="account-number">-</span></p>
                                <p><strong>Currency:</strong> <span id="currency">-</span></p>

                                <form action="{{ action([\Modules\Superadmin\Http\Controllers\SubscriptionController::class, 'confirm'], [$package->id]) }}" method="POST" enctype="multipart/form-data">
                                    {{ csrf_field() }}
                                    <input type="hidden" name="gateway" value="{{$k}}">

                                    <div class="form-group browse-button-margin">
                                        <label for="receipt_upload">Upload Receipt</label>
                                        <input type="file" name="receipt_upload" id="receipt_upload">
                                    </div>

                                    <button type="submit" class="btn btn-submit"> <i class="fas fa-handshake"></i> Subscribe Now</button>
                                    <p class="btn btn-copy btn-primary" id="copy-account-number">Copy Account Number</p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const accounts = {
            bank1: [{
                    name: 'Bank 1 USD Account',
                    number: '123456789',
                    currency: 'USD'
                },
                {
                    name: 'Bank 1 MVR Account',
                    number: '987654321',
                    currency: 'MVR'
                }
            ],
            bank2: [{
                    name: 'Bank 2 USD Account',
                    number: '111223344',
                    currency: 'USD'
                },
                {
                    name: 'Bank 2 MVR Account',
                    number: '555667788',
                    currency: 'MVR'
                }
            ]
        };

        document.querySelectorAll('.bank-logo').forEach(function(logo) {
            logo.addEventListener('click', function() {
                const bank = this.getAttribute('data-bank');
                const dropdownMenu = document.querySelector('#bank-dropdown .dropdown-account-menu');
                dropdownMenu.innerHTML = '';
                accounts[bank].forEach(account => {
                    const li = document.createElement('li');
                    const a = document.createElement('a');
                    a.setAttribute('href', '#');
                    a.setAttribute('data-account', account.number);
                    a.setAttribute('data-name', account.name);
                    a.setAttribute('data-currency', account.currency);
                    a.innerText = `${account.name} (${account.currency})`;
                    li.appendChild(a);
                    dropdownMenu.appendChild(li);
                });
                document.getElementById('dropdown-account-container').style.display = 'block';
                document.getElementById('bank-info-container').style.display = 'block';
                document.getElementById('account-info').style.display = 'none';
            });
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('#bank-dropdown .dropdown-account-menu a')) {
                e.preventDefault();
                const accountNumber = e.target.getAttribute('data-account');
                const accountName = e.target.getAttribute('data-name');
                const currency = e.target.getAttribute('data-currency');
                document.getElementById('account-name').innerText = accountName;
                document.getElementById('account-number').innerText = accountNumber;
                document.getElementById('currency').innerText = currency;
                document.getElementById('account-info').style.display = 'block';
            }
        });

        document.getElementById('copy-account-number').addEventListener('click', function() {
            const accountNumber = document.getElementById('account-number').innerText;
            if (accountNumber !== '-') {
                const tempInput = document.createElement('input');
                document.body.appendChild(tempInput);
                tempInput.value = accountNumber;
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                alert('Account number copied to clipboard!');
            } else {
                alert('No account number to copy!');
            }
        });
    });
</script>