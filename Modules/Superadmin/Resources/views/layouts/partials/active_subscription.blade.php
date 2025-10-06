@if(!empty($__subscription) && env('APP_ENV') != 'demo')
@php
    $package_details = is_array($__subscription->package_details) ? $__subscription->package_details : json_decode($__subscription->package_details, true) ?? [];
@endphp
<i class="fas fa-info-circle pull-left mt-10 cursor-pointer" style= "margin-top: 24px; color:white" aria-hidden="true" data-toggle="popover" data-html="true" title="@lang('superadmin::lang.active_package_description')" data-placement="right" data-trigger="hover" data-content="
    <table class='table table-condensed'>
     <tr class='text-center'> 
        <td colspan='2'>
            {{$package_details['name'] ?? 'N/A'}}
        </td>
     </tr>
     <tr class='text-center'>
        <td colspan='2'>
            {{ @format_date($__subscription->start_date) }} - {{@format_date($__subscription->end_date) }}
        </td>
     </tr>
     <tr> 
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            @if(($package_details['location_count'] ?? 0) == 0)
                @lang('superadmin::lang.unlimited')
            @else
                {{$package_details['location_count'] ?? 0}}
            @endif

            @lang('business.business_locations')
        </td>
     </tr>
     <tr>
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            @if(($package_details['user_count'] ?? 0) == 0)
                @lang('superadmin::lang.unlimited')
            @else
                {{$package_details['user_count'] ?? 0}}
            @endif

            @lang('superadmin::lang.users')
        </td>
     <tr>
     <tr>
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            @if(($package_details['product_count'] ?? 0) == 0)
                @lang('superadmin::lang.unlimited')
            @else
                {{$package_details['product_count'] ?? 0}}
            @endif

            @lang('superadmin::lang.products')
        </td>
     </tr>
     <tr>
        <td colspan='2'>
            <i class='fa fa-check text-success'></i>
            @if(($package_details['invoice_count'] ?? 0) == 0)
                @lang('superadmin::lang.unlimited')
            @else
                {{$package_details['invoice_count'] ?? 0}}
            @endif

            @lang('superadmin::lang.invoices')
        </td>
     </tr>
     
    </table>                     
">
</i>
@endif