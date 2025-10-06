@php
	$subtype = '';
@endphp
@if(!empty($transaction_sub_type))
	@php
		$subtype = '?sub_type='.$transaction_sub_type;
	@endphp
@endif
<style>
.share-dropdown {
    position: relative;
    display: inline-block;
}

.share-button , .copy-url-button {
    background-color: white;
    color: #fff;
	border: none;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1;
}

.dropdown-content a {
    display: block;
    padding: 12px 16px;
    text-decoration: none;
    color: #333;
}

.dropdown-content a:hover {
    background-color: #ddd;
}

	</style>
@if(!empty($transactions))
	<table class="table table-slim no-border">
		@foreach ($transactions as $transaction)
			<tr class="cursor-pointer" 
	    		title="Customer: {{$transaction->contact?->name}} 
		    		@if(!empty($transaction->contact->mobile) && $transaction->contact->is_default == 0)
		    			<br/>Mobile: {{$transaction->contact->mobile}}
		    		@endif
	    		" >
				<td>
					{{ $loop->iteration}}.
				</td>
				<td> 
					{{ $transaction->invoice_no }} ({{$transaction->contact?->name}})
					@if(!empty($transaction->table))
						- {{$transaction->table->name}}
					@endif
				</td>
				<td class="display_currency">
					{{ $transaction->final_total }}
				</td>
				<td>
					@if(auth()->user()->can('sell.update') || auth()->user()->can('direct_sell.update'))
					<a href="{{action([\App\Http\Controllers\SellPosController::class, 'edit'], [$transaction->id]).$subtype}}">
	    				<i class="fas fa-pen text-muted" aria-hidden="true" title="{{__('lang_v1.click_to_edit')}}"></i>
	    			</a>
	    			@endif
	    			@if(auth()->user()->can('sell.delete') || auth()->user()->can('direct_sell.delete'))
	    			<a href="{{action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$transaction->id])}}" class="delete-sale" style="padding-left: 20px; padding-right: 20px"><i class="fa fa-trash text-danger" title="{{__('lang_v1.click_to_delete')}}"></i></a>
	    			@endif

					@if(!auth()->user()->can('sell.update') && auth()->user()->can('edit_pos_payment'))
						<a href="{{route('edit-pos-payment', ['id' => $transaction->id])}}" 
						title="@lang('lang_v1.add_edit_payment')">
						 <i class="fas fa-money-bill-alt text-muted"></i>
						</a>
					@endif

	    			<a href="{{action([\App\Http\Controllers\SellPosController::class, 'printInvoice'], [$transaction->id])}}" class="print-invoice-link">
	    				<i class="fa fa-print text-muted" aria-hidden="true" title="{{__('lang_v1.click_to_print')}}"></i>
	    			</a>

				
				
		<td>			
    <!-- "Copy URL" Button -->
    <button class="copy-url-button" data-transaction-id="{{ $transaction->id }}">
        <i class="fa fa-copy text-muted" aria-hidden="true" title="Copy Invoice Url"></i>
    </button>
    
    <!-- Input field with a unique ID -->
	<input type="text" class="form-control invoice-url" id="invoice-url-{{ $transaction->id }}" value="{{ $transaction->invoiceUrl }}" style="position: absolute; left: -9999px;" />

    <!-- "Share" Button (a simple link for sharing) -->
	<div class="share-dropdown">
    <button class="share-button" data-transaction-id="{{ $transaction->id }}">
        <i class="fa fa-share text-muted" aria-hidden="true" title="Share invoice URL"></i>
    </button>
    <div class="dropdown-content" id="shareDropdown-{{ $transaction->id }}">
        <a href="whatsapp://send?text=Here's a link to your invoice: *{{$transaction->invoice_no}}* %0a{{$transaction->invoiceUrl}}" title="Share via WhatsApp">Share via WhatsApp</a>
        <a href="mailto:?subject=Here's your link to your invoice# {{$transaction->invoice_no}}&body={{ $transaction->invoiceUrl }}" title="Share via Email">Share via Email</a>
    </div>
</div>
</td>



			</tr>
		@endforeach
	</table>
@else
	<p>@lang('sale.no_recent_transactions')</p>
@endif

<script>
$(document).ready(function() {
    $('.invoice-url').each(function() {
        var transactionId = $(this).data('transaction-id');
        var invoiceUrl = $(this).val();
        
      
    });

    $('.copy-url-button').click(function() {
        var button = $(this);
        var transactionId = button.data('transaction-id');
        var inputField = $('#invoice-url-' + transactionId);
        
        inputField.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                var iconElement = button.find('i');
                iconElement.removeClass('fa-copy');
                iconElement.addClass('fa-clipboard-check');
                
                setTimeout(function() {
                    iconElement.removeClass('fa-clipboard-check');
                    iconElement.addClass('fa-copy');
                }, 1500); // Reset the icon after 1.5 second
            } else {
                console.error('Copying to clipboard failed');
            }
        } catch (err) {
            console.error('Error copying to clipboard: ', err);
        }
    });
	$('.share-button').click(function() {
        var button = $(this);
        var transactionId = button.data('transaction-id');
        var dropdown = $('#shareDropdown-' + transactionId);

        // Hide all other dropdowns
        $('.dropdown-content').not(dropdown).hide();

        if (dropdown.is(':visible')) {
            dropdown.hide();
        } else {
            dropdown.show();
        }
    });
})
</script>

