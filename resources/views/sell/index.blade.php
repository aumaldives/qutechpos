@extends('layouts.app')
@section('title', __( 'lang_v1.all_sales'))

@section('content')

<!-- Load Tailwind and Flatpickr CSS only for this page -->
<!-- Using existing Font Awesome Premium from layout -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" id="tailwind-sells-page">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" id="flatpickr-sells-page">

<script>
// Remove Tailwind CSS when leaving this page to prevent affecting other pages
window.addEventListener('beforeunload', function() {
    var tailwindLink = document.getElementById('tailwind-sells-page');
    var flatpickrLink = document.getElementById('flatpickr-sells-page');
    if (tailwindLink) tailwindLink.remove();
    if (flatpickrLink) flatpickrLink.remove();
});
</script>

<style>
/* Custom Tailwind-compatible styles - scoped to sells page */
.dataTables_wrapper {
    overflow: visible !important;
}

.dataTables_scrollBody {
    overflow: visible !important;
}

.dataTables_scrollHead {
    overflow: visible !important;
}

.table-responsive {
    overflow: visible !important;
}

/* Modern DataTables styling */
.dataTables_wrapper .dataTables_length select {
    @apply px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.dataTables_wrapper .dataTables_filter input {
    @apply px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    @apply px-3 py-2 mx-1 rounded-lg transition-all;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    @apply bg-blue-600 text-white;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    @apply bg-blue-100;
}

/* Modern table styling */
#sell_table thead th {
    @apply bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold py-4 px-4 text-sm uppercase tracking-wider;
}

#sell_table tbody tr {
    @apply hover:bg-blue-50 transition-colors duration-150;
}

#sell_table tbody td {
    @apply py-3 px-4 text-sm text-gray-700;
}

#sell_table tfoot tr {
    @apply bg-gradient-to-r from-gray-700 to-gray-800 text-white font-bold;
}

#sell_table tfoot td {
    @apply py-4 px-4 text-sm;
}

/* Modern dropdown menu */
.modern-dropdown {
    @apply absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-200 py-2 z-50 opacity-0 invisible transition-all duration-200 transform scale-95;
}

.modern-dropdown.show {
    @apply opacity-100 visible scale-100;
}

.modern-dropdown a {
    @apply flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-150;
}

.modern-dropdown a i {
    @apply w-5 mr-3 text-gray-400;
}

/* Flatpickr modern styling */
.flatpickr-calendar {
    @apply shadow-2xl border-0 rounded-xl;
}

.flatpickr-day.selected {
    @apply bg-blue-600;
}

/* Loading animation */
@keyframes pulse-slow {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.loading-pulse {
    animation: pulse-slow 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Custom select2 modern styling */
.select2-container--default .select2-selection--single {
    @apply border-gray-300 rounded-lg h-11 flex items-center;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    @apply leading-10;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    @apply h-11;
}

/* Badge styles */
.status-badge {
    @apply px-3 py-1 rounded-full text-xs font-semibold;
}

.badge-paid {
    @apply bg-green-100 text-green-800;
}

.badge-due {
    @apply bg-red-100 text-red-800;
}

.badge-partial {
    @apply bg-yellow-100 text-yellow-800;
}

/* Smooth animations */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Modern scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    @apply bg-gray-100 rounded-full;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-gray-400 rounded-full;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-500;
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
}
</style>

<!-- Page Header -->
<div class="no-print bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 mb-6 rounded-xl shadow-lg">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">
                <i class="fa-solid fa-cart-shopping mr-3"></i>@lang('sale.sells')
            </h1>
            <p class="text-blue-100 text-sm">Manage and track all your sales transactions</p>
        </div>
        @can('direct_sell.access')
        <div>
            <a href="{{action([\App\Http\Controllers\SellController::class, 'create'])}}"
               class="inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200">
                <i class="fa-solid fa-circle-plus mr-2"></i>
                @lang('messages.add')
            </a>
        </div>
        @endcan
    </div>
</div>

<!-- Main Content -->
<div class="no-print">
    <!-- Filters Card -->
    <div class="bg-white rounded-xl shadow-lg mb-6 fade-in">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fa-solid fa-filter mr-3 text-blue-600"></i>
                    @lang('report.filters')
                </h2>
                <button onclick="toggleFilters()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fa-solid fa-chevron-down transform transition-transform duration-200" id="filter-chevron"></i>
                </button>
            </div>
        </div>

        <div id="filters-content" class="px-6 py-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                <!-- Location Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-location-dot mr-2 text-gray-400"></i>
                        @lang('purchase.business_location')
                    </label>
                    {!! Form::select('sell_list_filter_location_id', $business_locations, null, [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2',
                        'placeholder' => __('lang_v1.all')
                    ]) !!}
                </div>

                <!-- Customer Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fas fa-user mr-2 text-gray-400"></i>
                        @lang('contact.customer')
                    </label>
                    <select name="sell_list_filter_customer_id"
                            id="sell_list_filter_customer_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2">
                        <option value="">@lang('lang_v1.all')</option>
                        @foreach($customers as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Payment Status Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-money-bill-wave mr-2 text-gray-400"></i>
                        @lang('purchase.payment_status')
                    </label>
                    {!! Form::select('sell_list_filter_payment_status', [
                        'paid' => __('lang_v1.paid'),
                        'due' => __('lang_v1.due'),
                        'partial' => __('lang_v1.partial'),
                        'overdue' => __('lang_v1.overdue')
                    ], null, [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2',
                        'placeholder' => __('lang_v1.all')
                    ]) !!}
                </div>

                <!-- Date Range Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-calendar-days mr-2 text-gray-400"></i>
                        @lang('report.date_range')
                    </label>
                    <input type="text"
                           id="sell_list_filter_date_range"
                           placeholder="@lang('lang_v1.select_a_date_range')"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                           readonly>
                </div>

                @if(!empty($sales_representative))
                <!-- Sales Representative Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-user-tie mr-2 text-gray-400"></i>
                        @lang('report.user')
                    </label>
                    {!! Form::select('created_by', $sales_representative, null, [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2'
                    ]) !!}
                </div>
                @endif

                @if(!empty($is_cmsn_agent_enabled))
                <!-- Commission Agent Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-handshake mr-2 text-gray-400"></i>
                        @lang('lang_v1.sales_commission_agent')
                    </label>
                    {!! Form::select('sales_cmsn_agnt', $commission_agents, null, [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2'
                    ]) !!}
                </div>
                @endif

                @if(!empty($service_staffs))
                <!-- Service Staff Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-bell-concierge mr-2 text-gray-400"></i>
                        @lang('restaurant.service_staff')
                    </label>
                    {!! Form::select('service_staffs', $service_staffs, null, [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2',
                        'placeholder' => __('lang_v1.all')
                    ]) !!}
                </div>
                @endif

                @if(!empty($shipping_statuses))
                <!-- Shipping Status Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-truck-fast mr-2 text-gray-400"></i>
                        @lang('lang_v1.shipping_status')
                    </label>
                    {!! Form::select('shipping_status', $shipping_statuses, null, [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2',
                        'placeholder' => __('lang_v1.all')
                    ]) !!}
                </div>
                @endif

                @if(!empty($sources))
                <!-- Source Filter -->
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-code-branch mr-2 text-gray-400"></i>
                        @lang('lang_v1.sources')
                    </label>
                    {!! Form::select('sell_list_filter_source', $sources, null, [
                        'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all select2',
                        'placeholder' => __('lang_v1.all')
                    ]) !!}
                </div>
                @endif

                <!-- Subscriptions Checkbox -->
                <div class="space-y-2 flex items-end">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox"
                               id="only_subscriptions"
                               name="only_subscriptions"
                               value="1"
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition-all">
                        <span class="text-sm font-semibold text-gray-700">
                            <i class="fa-solid fa-rotate mr-2 text-gray-400"></i>
                            @lang('lang_v1.subscriptions')
                        </span>
                    </label>
                </div>

            </div>

            <!-- Clear Filters Button -->
            <div class="mt-6 flex justify-end">
                <button onclick="clearFilters()"
                        class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all font-semibold">
                    <i class="fa-solid fa-xmark mr-2"></i>
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Sales Table Card -->
    @if(auth()->user()->can('direct_sell.view') || auth()->user()->can('view_own_sell_only') || auth()->user()->can('view_commission_agent_sell'))
    @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
    @endphp

    <div class="bg-white rounded-xl shadow-lg overflow-hidden fade-in">
        <div class="border-b border-gray-200 px-6 py-4 bg-gradient-to-r from-gray-50 to-white">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-table-list mr-3 text-blue-600"></i>
                @lang('lang_v1.all_sales')
            </h2>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="table table-bordered table-striped ajax_view w-full" id="sell_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('lang_v1.contact_no')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('lang_v1.payment_method')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.total_paid')</th>
                            <th>@lang('lang_v1.sell_due')</th>
                            <th>@lang('lang_v1.sell_return_due')</th>
                            <th>@lang('lang_v1.shipping_status')</th>
                            <th>@lang('lang_v1.total_items')</th>
                            <th>@lang('lang_v1.types_of_service')</th>
                            <th>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1') }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_1'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_2'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_3'] ?? '' }}</th>
                            <th>{{ $custom_labels['sell']['custom_field_4'] ?? '' }}</th>
                            <th>@lang('lang_v1.added_by')</th>
                            <th>@lang('sale.sell_note')</th>
                            <th>@lang('sale.staff_note')</th>
                            <th>@lang('sale.shipping_details')</th>
                            <th>@lang('restaurant.table')</th>
                            <th>@lang('restaurant.service_staff')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="text-center">
                            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                            <td class="footer_payment_status_count"></td>
                            <td class="payment_method_count"></td>
                            <td class="footer_sale_total"></td>
                            <td class="footer_total_paid"></td>
                            <td class="footer_total_remaining"></td>
                            <td class="footer_total_sell_return_due"></td>
                            <td colspan="2"></td>
                            <td class="service_type_count"></td>
                            <td colspan="11"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modals -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@stop

@section('javascript')
<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script type="text/javascript">
// Toggle filters visibility
function toggleFilters() {
    const content = document.getElementById('filters-content');
    const chevron = document.getElementById('filter-chevron');

    content.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
}

// Clear all filters
function clearFilters() {
    // Clear all select2 dropdowns by setting to empty string
    $('#sell_list_filter_location_id').val('').trigger('change');
    $('#sell_list_filter_customer_id').val('').trigger('change');
    $('#sell_list_filter_payment_status').val('').trigger('change');
    $('#created_by').val('').trigger('change');
    $('#sales_cmsn_agnt').val('').trigger('change');
    $('#service_staffs').val('').trigger('change');
    $('#shipping_status').val('').trigger('change');
    $('#sell_list_filter_source').val('').trigger('change');
    $('#only_subscriptions').prop('checked', false);

    // Clear Flatpickr date range
    const dateInput = document.getElementById('sell_list_filter_date_range');
    if (dateInput._flatpickr) {
        dateInput._flatpickr.clear();
    }

    console.log('All filters cleared');
    sell_table.ajax.reload();
}

$(document).ready(function() {
    // Initialize Select2 for all select elements FIRST (before DataTable)
    $('.select2').select2({
        theme: 'default',
        width: '100%',
        allowClear: true,
        placeholder: function() {
            return $(this).data('placeholder') || $(this).find('option:first').text();
        }
    });

    // Initialize Flatpickr for date range
    flatpickr("#sell_list_filter_date_range", {
        mode: "range",
        dateFormat: "Y-m-d",
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                sell_table.ajax.reload();
            }
        },
        onClose: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 0) {
                sell_table.ajax.reload();
            }
        }
    });

    // Initialize DataTable
    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
            url: "/sells",
            data: function(d) {
                // Date range filter
                const dateRange = document.getElementById('sell_list_filter_date_range').value;
                if (dateRange) {
                    const dates = dateRange.split(' to ');
                    if (dates.length === 2) {
                        d.start_date = dates[0];
                        d.end_date = dates[1];
                    } else if (dates.length === 1) {
                        d.start_date = dates[0];
                        d.end_date = dates[0];
                    }
                }

                d.is_direct_sale = 1;

                // Get filter values
                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.created_by = $('#created_by').val();
                d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                d.service_staffs = $('#service_staffs').val();

                // Debug log for customer filter
                console.log('Customer Filter Value:', d.customer_id);

                if ($('#shipping_status').length) {
                    d.shipping_status = $('#shipping_status').val();
                }

                if ($('#sell_list_filter_source').length) {
                    d.source = $('#sell_list_filter_source').val();
                }

                if ($('#only_subscriptions').is(':checked')) {
                    d.only_subscriptions = 1;
                }

                d = __datatable_ajax_callback(d);
                return d;
            }
        },
        scrollY: "75vh",
        scrollX: true,
        scrollCollapse: true,
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'invoice_no', name: 'invoice_no' },
            { data: 'conatct_name', name: 'conatct_name' },
            { data: 'mobile', name: 'contacts.mobile' },
            { data: 'business_location', name: 'bl.name' },
            { data: 'payment_status', name: 'payment_status' },
            { data: 'payment_methods', orderable: false, searchable: false },
            { data: 'final_total', name: 'final_total' },
            { data: 'total_paid', name: 'total_paid', searchable: false },
            { data: 'total_remaining', name: 'total_remaining' },
            { data: 'return_due', orderable: false, searchable: false },
            { data: 'shipping_status', name: 'shipping_status' },
            { data: 'total_items', name: 'total_items', searchable: false },
            { data: 'types_of_service_name', name: 'tos.name', @if(empty($is_types_service_enabled)) visible: false @endif },
            { data: 'service_custom_field_1', name: 'service_custom_field_1', @if(empty($is_types_service_enabled)) visible: false @endif },
            { data: 'custom_field_1', name: 'transactions.custom_field_1', @if(empty($custom_labels['sell']['custom_field_1'])) visible: false @endif },
            { data: 'custom_field_2', name: 'transactions.custom_field_2', @if(empty($custom_labels['sell']['custom_field_2'])) visible: false @endif },
            { data: 'custom_field_3', name: 'transactions.custom_field_3', @if(empty($custom_labels['sell']['custom_field_3'])) visible: false @endif },
            { data: 'custom_field_4', name: 'transactions.custom_field_4', @if(empty($custom_labels['sell']['custom_field_4'])) visible: false @endif },
            { data: 'added_by', name: 'u.first_name' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'staff_note', name: 'staff_note' },
            { data: 'shipping_details', name: 'shipping_details' },
            { data: 'table_name', name: 'tables.name', @if(empty($is_tables_enabled)) visible: false @endif },
            { data: 'waiter', name: 'ss.first_name', @if(empty($is_service_staff_enabled)) visible: false @endif }
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#sell_table'));
        },
        footerCallback: function(row, data, start, end, display) {
            var footer_sale_total = 0;
            var footer_total_paid = 0;
            var footer_total_remaining = 0;
            var footer_total_sell_return_due = 0;

            for (var r in data) {
                footer_sale_total += $(data[r].final_total).data('orig-value') ? parseFloat($(data[r].final_total).data('orig-value')) : 0;
                footer_total_paid += $(data[r].total_paid).data('orig-value') ? parseFloat($(data[r].total_paid).data('orig-value')) : 0;
                footer_total_remaining += $(data[r].total_remaining).data('orig-value') ? parseFloat($(data[r].total_remaining).data('orig-value')) : 0;
                footer_total_sell_return_due += $(data[r].return_due).find('.sell_return_due').data('orig-value') ? parseFloat($(data[r].return_due).find('.sell_return_due').data('orig-value')) : 0;
            }

            $('.footer_total_sell_return_due').html(__currency_trans_from_en(footer_total_sell_return_due));
            $('.footer_total_remaining').html(__currency_trans_from_en(footer_total_remaining));
            $('.footer_total_paid').html(__currency_trans_from_en(footer_total_paid));
            $('.footer_sale_total').html(__currency_trans_from_en(footer_sale_total));

            $('.footer_payment_status_count').html(__count_status(data, 'payment_status'));
            $('.service_type_count').html(__count_status(data, 'types_of_service_name'));
            $('.payment_method_count').html(__count_status(data, 'payment_methods'));
        },
        createdRow: function(row, data, dataIndex) {
            $(row).find('td:eq(6)').attr('class', 'clickable_td');
        }
    });

    // Filter change handlers - specifically for Select2 elements
    $('#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #created_by, #sales_cmsn_agnt, #service_staffs, #shipping_status, #sell_list_filter_source').on('change', function() {
        console.log('Filter changed:', $(this).attr('id'), 'Value:', $(this).val());
        sell_table.ajax.reload();
    });

    // Checkbox filter handler
    $('#only_subscriptions').on('change', function() {
        sell_table.ajax.reload();
    });

    // Modern dropdown positioning fix
    $(document).on('shown.bs.dropdown', '.dropdown', function() {
        var $dropdown = $(this).find('.dropdown-menu');
        var $table = $(this).closest('.dataTables_wrapper');

        if ($table.length) {
            $dropdown.css({
                'position': 'fixed',
                'z-index': '9999'
            });

            var offset = $(this).offset();
            $dropdown.css({
                'top': offset.top + $(this).outerHeight(),
                'left': offset.left
            });
        }
    });

    $(document).on('hidden.bs.dropdown', '.dropdown', function() {
        $(this).find('.dropdown-menu').css({
            'position': '',
            'top': '',
            'left': '',
            'z-index': ''
        });
    });
});

// Copy invoice URL function
function copyInvoiceUrl(url) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(function() {
            toastr.success('Invoice URL copied to clipboard!');
        }, function(err) {
            console.error('Could not copy text: ', err);
            fallbackCopyTextToClipboard(url);
        });
    } else {
        fallbackCopyTextToClipboard(url);
    }
}

function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            toastr.success('Invoice URL copied to clipboard!');
        } else {
            toastr.error('Failed to copy invoice URL');
        }
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
        toastr.error('Failed to copy invoice URL');
    }

    document.body.removeChild(textArea);
}
</script>

<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
