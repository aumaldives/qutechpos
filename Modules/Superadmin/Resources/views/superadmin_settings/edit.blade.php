@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Superadmin Settings')

@section('content')
@include('superadmin::layouts.nav')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('superadmin::lang.super_admin_settings')<small>@lang('superadmin::lang.edit_super_admin_settings')</small></h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    @include('layouts.partials.search_settings')
    <br>
    {!! Form::open(['action' => '\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@update', 'method' => 'put']) !!}
    <div class="row">
        <div class="col-xs-12">
           <!--  <pos-tab-container> -->
            <div class="col-xs-12 pos-tab-container">
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu">
                    <div class="list-group">
                        <a href="#" class="list-group-item text-center active">@lang('superadmin::lang.super_admin_settings')</a>
                        <a href="#" class="list-group-item text-center">@lang('superadmin::lang.application_settings')</a>
                        <a href="#" class="list-group-item text-center">@lang('superadmin::lang.email_smtp_settings')</a>
                        <a href="#" class="list-group-item text-center">@lang('superadmin::lang.payment_gateways')</a>
                        <a href="#" class="list-group-item text-center">@lang('superadmin::lang.backup')</a>
                        <a href="#" class="list-group-item text-center">@lang('superadmin::lang.cron')</a>
                        <a href="#" class="list-group-item text-center">@lang('superadmin::lang.pusher_settings')</a>
                        <a href="#" class="list-group-item text-center">@lang('superadmin::lang.additional_js_css')</a>
                        <a href="#" class="list-group-item text-center">Reports</a>
                    </div>
                </div>
                <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                    @include('superadmin::superadmin_settings.partials.super_admin_settings')
                    @include('superadmin::superadmin_settings.partials.application_settings')
                    @include('superadmin::superadmin_settings.partials.email_smtp_settings')
                    @include('superadmin::superadmin_settings.partials.payment_gateways')
                    @include('superadmin::superadmin_settings.partials.backup')
                    @include('superadmin::superadmin_settings.partials.cron')
                    @include('superadmin::superadmin_settings.partials.pusher_setting')
                    @include('superadmin::superadmin_settings.partials.additional_js_css')
                    @include('superadmin::superadmin_settings.partials.reports')
                </div>
            </div>
            <!--  </pos-tab-container> -->
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="form-group pull-right">
            {{Form::submit(__('messages.update'), ['class'=>"btn btn-danger"])}}
            </div>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).on('change', '#BACKUP_DISK', function() {
        if($(this).val() == 'dropbox'){
            $('div#dropbox_access_token_div').removeClass('hide');
        } else {
            $('div#dropbox_access_token_div').addClass('hide');
        }
    });

    $(document).ready( function(){
        if ($('#welcome_email_body').length) {
            tinymce.init({
                selector: 'textarea#welcome_email_body',
            });
        }

        if ($('#superadmin_register_tc').length) {
            tinymce.init({
                selector: 'textarea#superadmin_register_tc'
            });
        }
        
        // MIRA Reports JavaScript
        initMiraReports();
    });
    
    function initMiraReports() {
        console.log('MIRA Reports script loaded'); // Debug log
        
        // Show/hide custom date range
        $(document).on('change', '#report_type', function() {
            if ($(this).val() === 'custom') {
                $('#custom_date_range').show();
            } else {
                $('#custom_date_range').hide();
            }
        });
        
        // Generate report button - using delegated event handling
        $(document).on('click', '#generate_mira_report', function(e) {
            e.preventDefault();
            console.log('MIRA Report button clicked'); // Debug log
            
            // Add loading state
            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating Report...');
            
            var reportType = $('#report_type').val();
            var reportYear = $('#report_year').val();
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            
            console.log('Form values:', {reportType, reportYear, startDate, endDate}); // Debug log
            
            // Basic validation
            if (reportType === 'custom' && (!startDate || !endDate)) {
                alert('Please select both start and end dates for custom range.');
                $(this).prop('disabled', false).html('<i class="fas fa-download"></i> Generate & Download MIRA Report');
                return;
            }
            
            // Build URL parameters
            var params = {
                type: reportType,
                year: reportYear
            };
            
            if (reportType === 'custom') {
                params.start_date = startDate;
                params.end_date = endDate;
            }
            
            // Create download URL
            var baseUrl = "{{ url('superadmin/mira/export') }}";
            var url = baseUrl + "?" + $.param(params);
            
            console.log('Downloading from URL:', url); // Debug log
            
            // Create a temporary link for download
            var downloadLink = document.createElement('a');
            downloadLink.href = url;
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            // Reset button state after a delay
            var self = this;
            setTimeout(function() {
                $(self).prop('disabled', false).html('<i class="fas fa-download"></i> Generate & Download MIRA Report');
            }, 3000);
        });
    }
</script>
@endsection