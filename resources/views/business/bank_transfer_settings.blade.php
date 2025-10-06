@extends('layouts.app')

@section('title', __('lang_v1.bank_transfer_settings'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.bank_transfer_settings')
        <small>@lang('lang_v1.manage_bank_transfer_payment_settings')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-sm-12">
                @include('business.partials.settings_bank_transfer')
            </div>
        </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        // Debug: Check if form exists
        console.log('Form exists:', $('#business_bank_transfer_form').length);
        console.log('Form element:', $('#business_bank_transfer_form')[0]);
        
        // Form submission using event delegation - but only if form exists
        if ($('#business_bank_transfer_form').length > 0) {
            console.log('Attaching form submission handler...');
            $(document).on('submit', 'form#business_bank_transfer_form', function(e) {
                e.preventDefault();
                console.log('Form submission handler called');
            
            var form_data = $(this).serialize();
            console.log('Form data:', form_data);
            
            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: form_data,
                beforeSend: function(xhr) {
                    console.log('AJAX beforeSend triggered');
                    $('button[type="submit"]').attr('disabled', true);
                },
                success: function(result) {
                    console.log('AJAX Success callback triggered:', result);
                    if (result.success == true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    console.log('AJAX Error callback triggered');
                    console.log('XHR Error:', xhr);
                    console.log('Status:', xhr.status);
                    console.log('Response Text:', xhr.responseText);
                    console.log('Response JSON:', xhr.responseJSON);
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else if (xhr.responseText) {
                        toastr.error('Error: ' + xhr.responseText.substring(0, 100));
                    } else {
                        toastr.error('Something went wrong. Status: ' + xhr.status);
                    }
                },
                complete: function() {
                    console.log('AJAX Complete callback triggered');
                    $('button[type="submit"]').attr('disabled', false);
                }
            });
        });
        } // End of if form exists check

        // Show/hide bank transfer settings
        $('#enable_bank_transfer_payment').on('ifChanged', function(){
            if($(this).is(':checked')){
                $('#bank_transfer_settings').slideDown();
            } else {
                $('#bank_transfer_settings').slideUp();
            }
        });

        // Add bank account
        $('#add_bank_account').click(function(){
            console.log('Add bank account clicked');
            var form = document.getElementById('bank_account_form');
            console.log('Form element:', form);
            if (form) {
                form.reset();
            }
            $('#account_id').val('');
            $('#modal_title').text('@lang("lang_v1.add_bank_account")');
            $('#bank_account_modal').modal('show');
        });

        // Edit bank account
        $(document).on('click', '.edit-bank-account', function(){
            var account_id = $(this).data('account-id');
            
            $.ajax({
                method: 'GET',
                url: '{{url("/business/bank-accounts")}}/' + account_id,
                success: function(data){
                    if(data.success){
                        var account = data.account;
                        $('#account_id').val(account.id);
                        $('#bank_id').val(account.bank_id).trigger('change');
                        $('#location_id').val(account.location_id).trigger('change');
                        $('#account_name').val(account.account_name);
                        $('#account_number').val(account.account_number);
                        $('#account_type').val(account.account_type);
                        $('#swift_code').val(account.swift_code);
                        $('#branch_name').val(account.branch_name);
                        $('#is_active').prop('checked', account.is_active == 1);
                        $('#notes').val(account.notes);
                        
                        $('#modal_title').text('@lang("lang_v1.edit_bank_account")');
                        $('#bank_account_modal').modal('show');
                    }
                }
            });
        });

        // Save bank account
        $(document).on('click', '#save_bank_account', function(){
            console.log('Save bank account button clicked');
            // Build form data manually to ensure Select2 values are included
            var form_data = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                account_id: $('#account_id').val(),
                bank_id: $('#bank_id').val(),
                location_id: $('#location_id').val(),
                account_name: $('#account_name').val(),
                account_number: $('#account_number').val(),
                account_type: $('#account_type').val(),
                swift_code: $('#swift_code').val(),
                branch_name: $('#branch_name').val(),
                is_active: $('#is_active').is(':checked') ? 1 : 0,
                notes: $('#notes').val()
            };
            
            console.log('Form data:', form_data);
            
            var account_id = $('#account_id').val();
            var url = account_id ? '{{url("/business/bank-accounts")}}/' + account_id : '{{url("/business/bank-accounts")}}';
            var method = account_id ? 'PUT' : 'POST';

            $.ajax({
                method: method,
                url: url,
                data: form_data,
                dataType: 'json',
                success: function(data){
                    if(data.success){
                        $('#bank_account_modal').modal('hide');
                        toastr.success(data.msg);
                        location.reload(); // Reload to refresh the bank accounts list
                    } else {
                        toastr.error(data.msg);
                    }
                },
                error: function(xhr){
                    console.log('AJAX Error:', xhr);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var errorMsg = '';
                        $.each(errors, function(key, value){
                            errorMsg += value[0] + '<br>';
                        });
                        toastr.error(errorMsg);
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                }
            });
        });

        // Delete bank account
        $(document).on('click', '.delete-bank-account', function(){
            var account_id = $(this).data('account-id');
            
            swal({
                title: '@lang("messages.sure")',
                text: '@lang("lang_v1.delete_bank_account_confirm")',
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        method: 'DELETE',
                        url: '{{url("/business/bank-accounts")}}/' + account_id,
                        success: function(data){
                            if(data.success){
                                toastr.success(data.msg);
                                $('tr[data-account-id="' + account_id + '"]').remove();
                            } else {
                                toastr.error(data.msg);
                            }
                        }
                    });
                }
            });
        });

        // Initialize Select2
        $('.select2').select2();

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Add explicit click handler for save button (prevent duplicate)
        $('button[type="submit"]').off('click').on('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            console.log('Save button clicked, submitting form...');
            
            var $form = $('#business_bank_transfer_form');
            console.log('Form found:', $form.length);
            console.log('Form action:', $form.attr('action'));
            
            var form_data = $form.serialize();
            console.log('Form data:', form_data);
            
            // Form data looks good, proceeding with AJAX
            console.log('About to send AJAX request...');
            
            $.ajax({
                method: 'POST',
                url: '{{action([\App\Http\Controllers\BusinessController::class, 'postBusinessSettings'])}}',
                dataType: 'json',
                data: form_data,
                beforeSend: function(xhr) {
                    console.log('AJAX beforeSend triggered');
                    $('button[type="submit"]').attr('disabled', true);
                },
                success: function(result) {
                    console.log('AJAX Success callback triggered:', result);
                    if (result.success == true) {
                        toastr.success(result.msg);
                    } else {
                        toastr.error(result.msg);
                    }
                },
                error: function(xhr) {
                    console.log('AJAX Error callback triggered');
                    console.log('XHR Error:', xhr);
                    console.log('Status:', xhr.status);
                    console.log('Response Text:', xhr.responseText);
                    console.log('Response JSON:', xhr.responseJSON);
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else if (xhr.responseText) {
                        toastr.error('Error: ' + xhr.responseText.substring(0, 100));
                    } else {
                        toastr.error('Something went wrong. Status: ' + xhr.status);
                    }
                },
                complete: function() {
                    console.log('AJAX Complete callback triggered');
                    $('button[type="submit"]').attr('disabled', false);
                }
            });
        });
    });
</script>
@endsection