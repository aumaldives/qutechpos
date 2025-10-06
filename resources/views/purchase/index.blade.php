@extends('layouts.app')
@section('title', __('purchase.purchases'))

@section('content')

<style>
.box{
overflow: visible !important;
}

.box .table {
  overflow: visible !important;
}

.dataTables_wrapper {
  overflow: visible !important;
}

.dataTables_scrollHead {
  overflow: visible !important;
}

.dataTables_scrollBody {
  overflow: visible !important;
}

.dataTables_scroll {
  overflow: visible !important;
}

/* Fix z-index for Select2 dropdowns in general */
.select2-container {
  z-index: 1030 !important;
}

.select2-dropdown {
  z-index: 1030 !important;
}

/* Specific fixes for modals */
.modal {
  z-index: 1050 !important;
}

.modal-backdrop {
  z-index: 1040 !important;
}

/* Ensure Select2 dropdowns in modals appear above modal */
.modal .select2-container {
  z-index: 1060 !important;
}

.modal .select2-dropdown {
  z-index: 1060 !important;
}

/* Fix Select2 dropdown positioning within modals */
.modal .select2-container--open .select2-dropdown {
  position: fixed !important;
  z-index: 1060 !important;
}

/* Make sure modal content doesn't cut off dropdowns */
.modal-content {
  overflow: visible !important;
}

.modal-body {
  overflow: visible !important;
}

/* Alternative approach: Set dropdownParent for Select2 in modal */
.product_modal .select2-container {
  z-index: 1060 !important;
}

.product_modal .select2-dropdown {
  z-index: 1060 !important;
}

/* When modal is open, ensure page filter dropdowns stay below modal */
.modal-open .select2-container {
  z-index: 1020 !important;
}

.modal-open .select2-dropdown {
  z-index: 1020 !important;
}

/* But allow modal dropdowns to still be above */
.modal-open .modal .select2-container {
  z-index: 1060 !important;
}

.modal-open .modal .select2-dropdown {
  z-index: 1060 !important;
}
</style>

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('purchase.purchases')
        <small></small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('purchase_list_filter_location_id',  __('purchase.business_location') . ':') !!}
                {!! Form::select('purchase_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('purchase_list_filter_supplier_id',  __('purchase.supplier') . ':') !!}
                {!! Form::select('purchase_list_filter_supplier_id', $suppliers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('purchase_list_filter_status',  __('purchase.purchase_status') . ':') !!}
                {!! Form::select('purchase_list_filter_status', $orderStatuses, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('purchase_list_filter_payment_status',  __('purchase.payment_status') . ':') !!}
                {!! Form::select('purchase_list_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial'), 'overdue' => __('lang_v1.overdue')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('purchase_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.all_purchases')])
        @can('purchase.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{action([\App\Http\Controllers\PurchaseController::class, 'create'])}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @include('purchase.partials.purchase_table')
    @endcomponent

    <div class="modal fade product_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    @include('purchase.partials.update_purchase_status_modal')

</section>

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script>
    //Date range as a button
    $('#purchase_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
           purchase_table.ajax.reload();
        }
    );
    $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#purchase_list_filter_date_range').val('');
        purchase_table.ajax.reload();
    });

    $(document).on('click', '.update_status', function(e){
        e.preventDefault();
        $('#update_purchase_status_form').find('#status').val($(this).data('status'));
        $('#update_purchase_status_form').find('#purchase_id').val($(this).data('purchase_id'));
        $('#update_purchase_status_modal').modal('show');
    });

    $(document).on('submit', '#update_purchase_status_form', function(e){
        e.preventDefault();
        var form = $(this);
        var data = form.serialize();

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function(result) {
                if (result.success == true) {
                    $('#update_purchase_status_modal').modal('hide');
                    toastr.success(result.msg);
                    purchase_table.ajax.reload();
                    $('#update_purchase_status_form')
                        .find('button[type="submit"]')
                        .attr('disabled', false);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    // Fix Select2 dropdowns in modals
    $(document).on('show.bs.modal', '.modal', function () {
        // Close any open Select2 dropdowns on the page when modal is opening
        $('.select2-container--open').each(function() {
            $(this).prev('select').select2('close');
        });
    });

    $(document).on('shown.bs.modal', '.modal', function () {
        var modal = $(this);

        // Reinitialize Select2 elements in the modal with proper dropdownParent
        modal.find('.select2').each(function() {
            var $element = $(this);

            // Destroy existing Select2 if it exists
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.select2('destroy');
            }

            // Reinitialize with modal as dropdownParent
            $element.select2({
                dropdownParent: modal,
                width: '100%'
            });
        });
    });

    // Additional fix for Select2 positioning in modals
    $(document).on('select2:open', '.modal .select2', function () {
        var $select = $(this);
        var modal = $select.closest('.modal');

        // Ensure dropdown appears above modal backdrop
        setTimeout(function() {
            $('.select2-dropdown').css({
                'z-index': 1060
            });
        }, 1);
    });
</script>
	
@endsection