<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beautiful Instructions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .instruction-section {
            background-color: #f5f5f5;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .instruction-list {
            list-style-type: none;
            padding: 0;
        }

        .instruction-list li {
            padding: 10px 0;
        }

        .instruction-list i {
            margin-right: 10px;
            color: #007BFF;
        }

        .instruction-link:hover {
            text-decoration: underline;
        }

        .highlighted-link {
            background-color: #FF7377;
            color: #000000;
            padding: 3px 5px;
            font-weight: bold;
            border-radius: 4px;
        }
		
		.instruction-section {
    background-color: #f5f5f5;
    padding: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    margin: 20px 0; /* This provides 20px spacing both at the top and bottom */
}

    </style>
</head>
<body>

<div class="pos-tab-content active">
    <div class="instruction-section">
        <div class="row">
            <div class="col-sm-12">
                <ul class="instruction-list">
                    <li><i class="fa fa-check-circle" aria-hidden="true"></i> {!! __('woocommerce::lang.ins_1') !!}</li>
                    <li><i class="fa fa-check-circle" aria-hidden="true"></i> {!! __('woocommerce::lang.ins_2') !!}</li>
                    <li><i class="fa fa-info-circle" aria-hidden="true"></i> {!! __('woocommerce::lang.api_settings_help_text') !!} <a href="https://docs.woocommerce.com/document/woocommerce-rest-api/#section-3" target="_blank" class="instruction-link">@lang('lang_v1.click_here')</a> @lang('lang_v1.for_more_info')</li>
                    <li><i class="fa fa-cog" aria-hidden="true"></i> {!! __('woocommerce::lang.api_settings_help_permalink') !!}</li>
                    <li><i class="fa fa-refresh" aria-hidden="true"></i> {!! __('woocommerce::lang.api_settings_help_permalink_reset') !!}</li>
                    @if(config('app.env') != 'demo')
                        <li><i class="fa fa-life-ring" aria-hidden="true"></i> If you still face issues, you can use LiveChat to contact support or mail to <a href="mailto:hello@islebooks.com" class="instruction-link">hello@islebooks.com</a></li>
                        <li><i class="fa fa-book" aria-hidden="true"></i> More detailed guidance on WooCommerce setup can be found here: <a href="https://islebooks.mv/documentations/woocommerce-setup-guide" class="highlighted-link">Click Here</a></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

</body>
</html>
