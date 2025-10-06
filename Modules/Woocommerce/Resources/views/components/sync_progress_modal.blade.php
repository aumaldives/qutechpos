<!-- Real-time Sync Progress Modal -->
<div class="modal fade" id="sync-progress-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-sync fa-spin" id="sync-icon"></i>
                    <span id="modal-title">Synchronization in Progress</span>
                </h4>
                <div class="modal-header-actions">
                    <button type="button" class="btn btn-sm btn-warning" id="pause-sync-btn" title="Pause Sync">
                        <i class="fas fa-pause"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-success d-none" id="resume-sync-btn" title="Resume Sync">
                        <i class="fas fa-play"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" id="cancel-sync-btn" title="Cancel Sync">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Progress Overview -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="sync-info">
                            <h5 id="sync-location-name">Loading...</h5>
                            <p id="sync-type-info" class="text-muted">Preparing sync...</p>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <div id="sync-status-badge" class="badge badge-info badge-lg">Processing</div>
                    </div>
                </div>

                <!-- Main Progress Bar -->
                <div class="progress mb-3" style="height: 25px;">
                    <div id="main-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                         style="width: 0%" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        <span id="progress-percentage">0%</span>
                    </div>
                </div>

                <!-- Current Operation -->
                <div class="current-operation mb-3">
                    <small class="text-muted">Current Operation:</small>
                    <div id="current-operation" class="font-weight-bold">Initializing...</div>
                </div>

                <!-- Step Progress -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label">Step:</span>
                            <span id="step-info">1 of 5</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label">Records:</span>
                            <span id="records-info">0 / 0</span>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="stat-box text-center">
                            <div class="stat-number text-success" id="success-count">0</div>
                            <div class="stat-label">Success</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box text-center">
                            <div class="stat-number text-danger" id="failed-count">0</div>
                            <div class="stat-label">Failed</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-box text-center">
                            <div class="stat-number text-info" id="processed-count">0</div>
                            <div class="stat-label">Processed</div>
                        </div>
                    </div>
                </div>

                <!-- Time Information -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label">Elapsed:</span>
                            <span id="elapsed-time">0s</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <span class="info-label">ETA:</span>
                            <span id="eta-time">Calculating...</span>
                        </div>
                    </div>
                </div>

                <!-- Error Messages (if any) -->
                <div id="error-section" class="d-none">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> Error Details</h6>
                        <div id="error-message"></div>
                    </div>
                </div>

                <!-- Live Activity Log -->
                <div class="activity-log">
                    <h6>
                        <i class="fas fa-list"></i> Activity Log
                        <small class="text-muted">(last 10 operations)</small>
                    </h6>
                    <div id="activity-log" class="log-container">
                        <div class="log-entry text-muted">Waiting for sync to start...</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="flex-grow-1">
                    <div id="sync-id-info" class="small text-muted"></div>
                </div>
                <button type="button" class="btn btn-secondary d-none" id="close-modal-btn" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary d-none" id="view-results-btn">View Results</button>
            </div>
        </div>
    </div>
</div>

<style>
.sync-info h5 {
    margin-bottom: 5px;
    color: #333;
}

.progress {
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
}

.info-item {
    margin-bottom: 8px;
}

.info-label {
    display: inline-block;
    width: 80px;
    font-weight: 600;
    color: #666;
}

.stat-box {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.log-container {
    max-height: 200px;
    overflow-y: auto;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 10px;
}

.log-entry {
    padding: 5px 0;
    border-bottom: 1px solid #e9ecef;
    font-size: 13px;
    line-height: 1.4;
}

.log-entry:last-child {
    border-bottom: none;
}

.log-entry .timestamp {
    color: #6c757d;
    margin-right: 8px;
}

.log-entry.error {
    color: #dc3545;
}

.log-entry.success {
    color: #28a745;
}

.log-entry.warning {
    color: #ffc107;
}

.modal-header-actions {
    display: flex;
    gap: 5px;
}

.modal-header-actions .btn {
    padding: 5px 10px;
}

.badge-lg {
    font-size: 14px;
    padding: 8px 12px;
}

#sync-icon.paused {
    animation: none !important;
}

.current-operation {
    background: #f1f3f4;
    padding: 10px;
    border-left: 4px solid #007bff;
    border-radius: 4px;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.pulsing {
    animation: pulse 2s infinite;
}
</style>

<script>
class SyncProgressModal {
    constructor() {
        this.syncId = null;
        this.eventSource = null;
        this.startTime = null;
        this.activityLog = [];
        this.maxLogEntries = 10;
        
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Control buttons
        $('#pause-sync-btn').click(() => this.pauseSync());
        $('#resume-sync-btn').click(() => this.resumeSync());
        $('#cancel-sync-btn').click(() => this.cancelSync());
        
        // Modal events
        $('#sync-progress-modal').on('hidden.bs.modal', () => {
            this.cleanup();
        });
    }

    show(syncId, locationName, syncType) {
        this.syncId = syncId;
        this.startTime = new Date();
        
        // Initialize UI
        $('#modal-title').text('Synchronization in Progress');
        $('#sync-location-name').text(locationName);
        $('#sync-type-info').text(`${syncType.charAt(0).toUpperCase() + syncType.slice(1)} Sync`);
        $('#sync-id-info').text(`Sync ID: ${syncId}`);
        
        // Reset UI state
        this.resetUI();
        
        // Show modal
        $('#sync-progress-modal').modal('show');
        
        // Start real-time updates
        this.startProgressUpdates();
    }

    resetUI() {
        // Reset progress
        $('#main-progress-bar').css('width', '0%').attr('aria-valuenow', 0);
        $('#progress-percentage').text('0%');
        
        // Reset info
        $('#current-operation').text('Initializing...');
        $('#step-info').text('0 of 0');
        $('#records-info').text('0 / 0');
        
        // Reset stats
        $('#success-count').text('0');
        $('#failed-count').text('0');
        $('#processed-count').text('0');
        
        // Reset time
        $('#elapsed-time').text('0s');
        $('#eta-time').text('Calculating...');
        
        // Reset status
        $('#sync-status-badge').removeClass().addClass('badge badge-info badge-lg').text('Processing');
        $('#sync-icon').removeClass().addClass('fas fa-sync fa-spin');
        
        // Show control buttons
        $('#pause-sync-btn').removeClass('d-none');
        $('#resume-sync-btn').addClass('d-none');
        $('#cancel-sync-btn').removeClass('d-none');
        $('#close-modal-btn').addClass('d-none');
        $('#view-results-btn').addClass('d-none');
        
        // Clear activity log
        this.activityLog = [];
        this.updateActivityLog('Sync started', 'info');
        
        // Hide error section
        $('#error-section').addClass('d-none');
    }

    startProgressUpdates() {
        if (this.eventSource) {
            this.eventSource.close();
        }

        const url = `{{ route('woocommerce.progress.stream') }}?sync_id=${this.syncId}`;
        this.eventSource = new EventSource(url);

        this.eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.updateProgress(data);
            } catch (e) {
                console.error('Error parsing SSE data:', e);
            }
        };

        this.eventSource.onerror = (event) => {
            console.error('SSE connection error:', event);
            // Retry connection after 5 seconds
            setTimeout(() => {
                if (this.syncId && this.eventSource?.readyState !== EventSource.CONNECTING) {
                    this.startProgressUpdates();
                }
            }, 5000);
        };
    }

    updateProgress(data) {
        if (!data || data.status === 'not_found') {
            this.showError('Sync not found or expired');
            return;
        }

        // Update progress bar
        const percentage = data.progress_percentage || 0;
        $('#main-progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
        $('#progress-percentage').text(Math.round(percentage) + '%');

        // Update current operation
        if (data.current_operation) {
            $('#current-operation').text(data.current_operation);
            this.updateActivityLog(data.current_operation);
        }

        // Update step info
        if (data.current_step && data.total_steps) {
            $('#step-info').text(`${data.current_step} of ${data.total_steps}`);
        }

        // Update record counts
        if (data.records_processed !== undefined && data.records_total !== undefined) {
            $('#records-info').text(`${data.records_processed} / ${data.records_total}`);
        }
        
        $('#success-count').text(data.records_success || 0);
        $('#failed-count').text(data.records_failed || 0);
        $('#processed-count').text(data.records_processed || 0);

        // Update time information
        this.updateTimeInfo(data);

        // Update status
        this.updateStatus(data.status);

        // Handle completion
        if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
            this.handleCompletion(data);
        }

        // Show errors if any
        if (data.error_message) {
            this.showError(data.error_message);
        }
    }

    updateTimeInfo(data) {
        // Calculate elapsed time
        if (this.startTime) {
            const elapsed = Math.floor((new Date() - this.startTime) / 1000);
            $('#elapsed-time').text(this.formatDuration(elapsed));
        }

        // Update ETA
        if (data.estimated_time_remaining) {
            $('#eta-time').text(this.formatDuration(data.estimated_time_remaining));
        } else if (data.progress_percentage > 0) {
            $('#eta-time').text('Calculating...');
        }
    }

    updateStatus(status) {
        const statusConfig = {
            'pending': { class: 'badge-secondary', text: 'Pending', icon: 'fas fa-clock' },
            'processing': { class: 'badge-info', text: 'Processing', icon: 'fas fa-sync fa-spin' },
            'paused': { class: 'badge-warning', text: 'Paused', icon: 'fas fa-pause' },
            'completed': { class: 'badge-success', text: 'Completed', icon: 'fas fa-check-circle' },
            'failed': { class: 'badge-danger', text: 'Failed', icon: 'fas fa-exclamation-triangle' },
            'cancelled': { class: 'badge-secondary', text: 'Cancelled', icon: 'fas fa-times-circle' }
        };

        const config = statusConfig[status] || statusConfig['processing'];
        
        $('#sync-status-badge').removeClass().addClass(`badge badge-lg ${config.class}`).text(config.text);
        $('#sync-icon').removeClass().addClass(config.icon);

        // Update control buttons based on status
        if (status === 'paused') {
            $('#pause-sync-btn').addClass('d-none');
            $('#resume-sync-btn').removeClass('d-none');
        } else if (status === 'processing') {
            $('#pause-sync-btn').removeClass('d-none');
            $('#resume-sync-btn').addClass('d-none');
        } else if (['completed', 'failed', 'cancelled'].includes(status)) {
            $('#pause-sync-btn').addClass('d-none');
            $('#resume-sync-btn').addClass('d-none');
            $('#cancel-sync-btn').addClass('d-none');
            $('#close-modal-btn').removeClass('d-none');
        }
    }

    handleCompletion(data) {
        this.cleanup();
        
        // Update activity log
        if (data.status === 'completed') {
            this.updateActivityLog('Sync completed successfully!', 'success');
            $('#view-results-btn').removeClass('d-none');
        } else if (data.status === 'failed') {
            this.updateActivityLog('Sync failed: ' + (data.error_message || 'Unknown error'), 'error');
        } else if (data.status === 'cancelled') {
            this.updateActivityLog('Sync cancelled by user', 'warning');
        }

        // Show completion notification
        const message = data.status === 'completed' 
            ? `Sync completed successfully! Processed ${data.records_success || 0} records.`
            : data.status === 'failed'
            ? `Sync failed: ${data.error_message || 'Unknown error'}`
            : 'Sync was cancelled';

        toastr[data.status === 'completed' ? 'success' : 'warning'](message);
    }

    updateActivityLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const entry = {
            timestamp: timestamp,
            message: message,
            type: type
        };

        this.activityLog.unshift(entry);
        
        // Keep only last N entries
        if (this.activityLog.length > this.maxLogEntries) {
            this.activityLog = this.activityLog.slice(0, this.maxLogEntries);
        }

        // Update DOM
        const logHtml = this.activityLog.map(entry => 
            `<div class="log-entry ${entry.type}">
                <span class="timestamp">${entry.timestamp}</span>
                ${entry.message}
            </div>`
        ).join('');

        $('#activity-log').html(logHtml);
    }

    showError(message) {
        $('#error-message').text(message);
        $('#error-section').removeClass('d-none');
    }

    pauseSync() {
        if (!this.syncId) return;

        $.post(`{{ url('woocommerce/progress') }}/${this.syncId}/pause`, {
            _token: '{{ csrf_token() }}'
        }).done((response) => {
            toastr.success('Sync paused successfully');
            this.updateActivityLog('Sync paused by user', 'warning');
        }).fail((xhr) => {
            toastr.error('Failed to pause sync: ' + (xhr.responseJSON?.message || 'Unknown error'));
        });
    }

    resumeSync() {
        if (!this.syncId) return;

        $.post(`{{ url('woocommerce/progress') }}/${this.syncId}/resume`, {
            _token: '{{ csrf_token() }}'
        }).done((response) => {
            toastr.success('Sync resumed successfully');
            this.updateActivityLog('Sync resumed by user', 'info');
        }).fail((xhr) => {
            toastr.error('Failed to resume sync: ' + (xhr.responseJSON?.message || 'Unknown error'));
        });
    }

    cancelSync() {
        if (!this.syncId) return;

        if (!confirm('Are you sure you want to cancel this sync? This action cannot be undone.')) {
            return;
        }

        $.post(`{{ url('woocommerce/progress') }}/${this.syncId}/cancel`, {
            _token: '{{ csrf_token() }}'
        }).done((response) => {
            toastr.success('Sync cancellation requested');
            this.updateActivityLog('Cancellation requested by user', 'warning');
        }).fail((xhr) => {
            toastr.error('Failed to cancel sync: ' + (xhr.responseJSON?.message || 'Unknown error'));
        });
    }

    formatDuration(seconds) {
        if (seconds < 60) {
            return `${seconds}s`;
        } else if (seconds < 3600) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}m ${secs}s`;
        } else {
            const hours = Math.floor(seconds / 3600);
            const mins = Math.floor((seconds % 3600) / 60);
            return `${hours}h ${mins}m`;
        }
    }

    cleanup() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }
}

// Initialize the sync progress modal
const syncProgressModal = new SyncProgressModal();

// Make it globally accessible
window.syncProgressModal = syncProgressModal;
</script>