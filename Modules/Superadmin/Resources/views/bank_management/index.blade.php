@extends('layouts.app')

@section('title', 'Bank Management')

@section('content')

@include('superadmin::layouts.nav')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>Bank Management
        <small>Manage system banks and logos</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="box">
        <div class="box-header">
            <h3 class="box-title">All Banks</h3>
            <div class="box-tools">
                <button type="button" class="btn btn-block btn-primary" id="add_bank_btn">
                    <i class="fa fa-plus"></i> Add New Bank
                </button>
            </div>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="banks_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Full Name</th>
                            <th>Country</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Bank Modal -->
    <div class="modal fade" id="bank_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="bank_modal_title">Add Bank</h4>
                </div>
                <form id="bank_form" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_name">Bank Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_code">Bank Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_code" name="code" required maxlength="10" placeholder="e.g., MIB, BML">
                                    <small class="help-block">Short code for the bank (max 10 characters)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="bank_full_name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_full_name" name="full_name" required>
                                    <small class="help-block">Complete official name of the bank</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bank_country">Country Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_country" name="country" required maxlength="2" placeholder="MV" value="MV">
                                    <small class="help-block">2-letter country code (ISO 3166-1)</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_logo">Bank Logo</label>
                                    <input type="file" class="form-control" id="bank_logo" name="logo" accept="image/*">
                                    <small class="help-block">Upload bank logo (JPEG, PNG, GIF, SVG - max 2MB)</small>
                                    <div id="current_logo" style="margin-top: 10px; display: none;">
                                        <strong>Current Logo:</strong><br>
                                        <img id="current_logo_img" src="" alt="Current Logo" style="width: 80px; height: 80px; object-fit: contain; border: 1px solid #ddd; padding: 5px; margin-top: 5px;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="bank_is_active" name="is_active" checked> 
                                        Active
                                    </label>
                                    <br>
                                    <small class="help-block">Only active banks will be available for businesses to select</small>
                                </div>
                            </div>
                        </div>

                        <div id="logo_preview" style="display: none; margin-top: 10px;">
                            <strong>Logo Preview:</strong><br>
                            <img id="logo_preview_img" src="" alt="Logo Preview" style="width: 80px; height: 80px; object-fit: contain; border: 1px solid #ddd; padding: 5px; margin-top: 5px;">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="bank_save_btn">Save Bank</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    var bank_table = $('#banks_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('superadmin.banks.index') }}"
        },
        columns: [
            {data: 'id', name: 'id'},
            {data: 'logo_display', name: 'logo_url', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'code', name: 'code'},
            {data: 'full_name', name: 'full_name'},
            {data: 'country', name: 'country'},
            {data: 'status_formatted', name: 'is_active', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[2, 'asc']]
    });

    // Add bank modal
    $('#add_bank_btn').click(function() {
        $('#bank_modal_title').text('Add Bank');
        $('#bank_form')[0].reset();
        $('#bank_form').attr('data-id', '');
        $('#current_logo').hide();
        $('#logo_preview').hide();
        $('#bank_modal').modal('show');
    });

    // Logo preview
    $('#bank_logo').change(function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#logo_preview_img').attr('src', e.target.result);
                $('#logo_preview').show();
            }
            reader.readAsDataURL(file);
        } else {
            $('#logo_preview').hide();
        }
    });

    // Save bank
    $('#bank_form').submit(function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var bank_id = $(this).attr('data-id');
        var url = bank_id ? "{{ route('superadmin.banks.update', ':id') }}".replace(':id', bank_id) : "{{ route('superadmin.banks.store') }}";
        var method = bank_id ? 'PUT' : 'POST';
        
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }
        formData.append('_token', '{{ csrf_token() }}');
        
        $('#bank_save_btn').prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.msg);
                    $('#bank_modal').modal('hide');
                    bank_table.ajax.reload();
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON?.errors;
                if (errors) {
                    var errorMessage = '';
                    Object.keys(errors).forEach(function(key) {
                        errorMessage += errors[key][0] + '\n';
                    });
                    toastr.error(errorMessage);
                } else {
                    toastr.error('Something went wrong');
                }
            },
            complete: function() {
                $('#bank_save_btn').prop('disabled', false).text('Save Bank');
            }
        });
    });

    // Edit bank
    $(document).on('click', '.edit-bank', function() {
        var bank_id = $(this).data('id');
        
        $.get("{{ route('superadmin.banks.show', ':id') }}".replace(':id', bank_id))
        .done(function(response) {
            if (response.success) {
                var bank = response.bank;
                $('#bank_modal_title').text('Edit Bank');
                $('#bank_name').val(bank.name);
                $('#bank_code').val(bank.code);
                $('#bank_full_name').val(bank.full_name);
                $('#bank_country').val(bank.country);
                $('#bank_is_active').prop('checked', bank.is_active == 1);
                $('#bank_form').attr('data-id', bank.id);
                
                if (bank.logo_url) {
                    $('#current_logo_img').attr('src', bank.logo_url);
                    $('#current_logo').show();
                }
                
                $('#logo_preview').hide();
                $('#bank_modal').modal('show');
            } else {
                toastr.error(response.msg);
            }
        })
        .fail(function() {
            toastr.error('Failed to load bank details');
        });
    });

    // Delete bank
    $(document).on('click', '.delete-bank', function() {
        var bank_id = $(this).data('id');
        
        swal({
            title: "Are you sure?",
            text: "This will permanently delete the bank and its logo. This action cannot be undone!",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: "{{ route('superadmin.banks.destroy', ':id') }}".replace(':id', bank_id),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.msg);
                            bank_table.ajax.reload();
                        } else {
                            toastr.error(response.msg);
                        }
                    },
                    error: function() {
                        toastr.error('Failed to delete bank');
                    }
                });
            }
        });
    });
});
</script>
@endsection