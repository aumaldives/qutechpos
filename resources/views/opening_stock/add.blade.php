@extends('layouts.app')
@section('title', __('lang_v1.add_opening_stock'))

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.add_opening_stock')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action([\App\Http\Controllers\OpeningStockController::class, 'save']), 'method' => 'post', 'id' => 'add_opening_stock_form' ]) !!}
	{!! Form::hidden('product_id', $product->id); !!}
	@include('opening_stock.form-part')
	<div class="row">
		<div class="col-sm-12">
			<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
		</div>
	</div>

	{!! Form::close() !!}
</section>
@stop
@section('javascript')
	<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
	<script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
	<script type="text/javascript">
		$(document).ready( function(){
			$('.os_date').datetimepicker({
		        format: moment_date_format + ' ' + moment_time_format,
		        ignoreReadonly: true,
		        widgetPositioning: {
		            horizontal: 'right',
		            vertical: 'bottom'
		        }
		    });

		    // Initialize Flatpickr for expiry date fields
		    $('.os_exp_date').each(function() {
		        flatpickr(this, {
		            dateFormat: convertDateFormatToFlatpickr(datepicker_date_format),
		            allowInput: false,
		            clickOpens: true,
		            altInput: true,
		            altFormat: datepicker_date_format.replace(/dd/g, 'd').replace(/mm/g, 'm').replace(/yyyy/g, 'Y')
		        });
		    });
		});

		// Convert bootstrap datepicker format to Flatpickr format
		function convertDateFormatToFlatpickr(format) {
		    return format.replace(/dd/g, 'd').replace(/mm/g, 'm').replace(/yyyy/g, 'Y');
		}
	</script>
@endsection
