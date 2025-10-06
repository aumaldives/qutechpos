@extends('superadmin.layouts.app')
@section('title', 'API Key Management')

@section('content')
<section class="content-header">
    <h1>API Key Management
        <small>Manage API keys across all businesses</small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3 id="total-keys">0</h3>
                    <p>Total API Keys</p>
                </div>
                <div class="icon">
                    <i class="fa fa-key"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3 id="active-keys">0</h3>
                    <p>Active Keys</p>
                </div>
                <div class="icon">
                    <i class="fa fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3 id="system-keys">0</h3>
                    <p>System Level Keys</p>
                </div>
                <div class="icon">
                    <i class="fa fa-cogs"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-xs-6">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3 id="superadmin-keys">0</h3>
                    <p>Superadmin Keys</p>
                </div>
                <div class="icon">
                    <i class="fa fa-shield"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">API Keys</h3>
                    <div class="box-tools">
                        <button type="button" class="btn btn-primary" onclick="showCreateModal()">
                            <i class="fa fa-plus"></i> Create API Key
                        </button>
                    </div>
                </div>
                
                <div class="box-body">
                    <table id="api-keys-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Business</th>
                                <th>User</th>
                                <th>Key</th>
                                <th>Access Level</th>
                                <th>Abilities</th>
                                <th>Status</th>
                                <th>Usage Info</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Create API Key Modal -->
<div class="modal fade" id="create-api-key-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Create New API Key</h4>
            </div>
            
            <form id="create-api-key-form">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">API Key Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="business_id">Business *</label>
                                <select class="form-control select2" name="business_id" required>
                                    <option value="">Select Business</option>
                                    @foreach($businesses as $business)
                                        <option value="{{ $business->id }}">{{ $business->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="access_level">Access Level *</label>
                                <select class="form-control" name="access_level" required>
                                    <option value="business">Business (Standard)</option>
                                    <option value="system">System (Internal)</option>
                                    <option value="superadmin">Superadmin (Full Access)</option>
                                </select>
                                <small class="text-muted">
                                    <strong>Business:</strong> Standard API access<br>
                                    <strong>System:</strong> Access to system endpoints (users, settings)<br>
                                    <strong>Superadmin:</strong> Full system access
                                </small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rate_limit_per_minute">Rate Limit (per minute) *</label>
                                <input type="number" class="form-control" name="rate_limit_per_minute" value="60" min="1" max="1000" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>API Abilities *</label>
                                <div class="checkbox-group">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="read"> Read
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="write"> Write
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="delete"> Delete
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="products"> Products
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="contacts"> Contacts
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="transactions"> Transactions
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="reports"> Reports
                                    </label>
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="abilities[]" value="*"> All Permissions
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expires_at">Expires At (Optional)</label>
                                <input type="datetime-local" class="form-control" name="expires_at">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create API Key</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- API Key Created Modal -->
<div class="modal fade" id="api-key-created-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-green">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">API Key Created Successfully</h4>
            </div>
            
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i>
                    <strong>Important:</strong> Save this API key now. You will not be able to see it again.
                </div>
                
                <div class="form-group">
                    <label>API Key:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="created-api-key" readonly>
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button" onclick="copyApiKey()">
                                <i class="fa fa-copy"></i> Copy
                            </button>
                        </span>
                    </div>
                </div>
                
                <div id="api-key-details"></div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">I've Saved It</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Load statistics
    loadStats();
    
    // Initialize DataTable
    initDataTable();
    
    // Handle form submission
    $('#create-api-key-form').on('submit', handleCreateApiKey);
});

function loadStats() {
    $.get('/superadmin/api-keys/stats')
        .done(function(response) {
            if (response.success) {
                $('#total-keys').text(response.data.total_keys);
                $('#active-keys').text(response.data.active_keys);
                $('#system-keys').text(response.data.by_access_level.system);
                $('#superadmin-keys').text(response.data.by_access_level.superadmin);
            }
        });
}

function initDataTable() {
    $('#api-keys-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("superadmin.api-keys.index") }}',
        columns: [
            {data: 'name', name: 'name'},
            {data: 'business_name', name: 'business.name'},
            {data: 'user_name', name: 'user_name', orderable: false},
            {data: 'display_key', name: 'key_prefix'},
            {data: 'access_level_badge', name: 'access_level'},
            {data: 'abilities_display', name: 'abilities', orderable: false},
            {data: 'status', name: 'is_active'},
            {data: 'usage_info', name: 'last_used_at', orderable: false},
            {data: 'created_at', name: 'created_at'},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[8, 'desc']],
        pageLength: 25,
        responsive: true
    });
}

function showCreateModal() {
    $('#create-api-key-modal').modal('show');
}

function handleCreateApiKey(e) {
    e.preventDefault();
    
    var formData = new FormData(e.target);
    
    $.ajax({
        url: '{{ route("superadmin.api-keys.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            $('#create-api-key-modal').modal('hide');
            $('#created-api-key').val(response.data.api_key);
            $('#api-key-details').html(`
                <p><strong>Display Key:</strong> ${response.data.display_key}</p>
                <p><strong>Access Level:</strong> ${response.data.access_level}</p>
            `);
            $('#api-key-created-modal').modal('show');
            $('#api-keys-table').DataTable().ajax.reload();
            loadStats();
        }
    })
    .fail(function(xhr) {
        var errors = xhr.responseJSON?.errors || {general: ['An error occurred']};
        var errorMsg = Object.values(errors).flat().join('\n');
        alert('Error creating API key:\n' + errorMsg);
    });
}

function copyApiKey() {
    var apiKey = document.getElementById('created-api-key');
    apiKey.select();
    document.execCommand('copy');
    toastr.success('API key copied to clipboard');
}

// Handle action buttons
$(document).on('click', '.revoke-key', function() {
    var id = $(this).data('id');
    if (confirm('Are you sure you want to revoke this API key?')) {
        $.post('/superadmin/api-keys/' + id + '/revoke', {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function() {
            toastr.success('API key revoked');
            $('#api-keys-table').DataTable().ajax.reload();
            loadStats();
        });
    }
});

$(document).on('click', '.activate-key', function() {
    var id = $(this).data('id');
    $.post('/superadmin/api-keys/' + id + '/activate', {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function() {
        toastr.success('API key activated');
        $('#api-keys-table').DataTable().ajax.reload();
        loadStats();
    });
});

$(document).on('click', '.delete-key', function() {
    var id = $(this).data('id');
    if (confirm('Are you sure you want to permanently delete this API key? This cannot be undone.')) {
        $.ajax({
            url: '/superadmin/api-keys/' + id,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            }
        })
        .done(function() {
            toastr.success('API key deleted');
            $('#api-keys-table').DataTable().ajax.reload();
            loadStats();
        });
    }
});
</script>
@endsection