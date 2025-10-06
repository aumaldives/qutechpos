<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;
use App\User;

class EssentialsOvertimeRequest extends Model
{
    protected $fillable = [
        'business_id',
        'user_id', 
        'overtime_date',
        'start_time',
        'end_time',
        'hours_requested',
        'overtime_type',
        'reason',
        'description',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'approved_hours',
        'multiplier_rate',
        'hourly_rate',
        'total_amount',
        'is_processed_in_payroll',
        'payroll_transaction_id'
    ];

    protected $dates = [
        'overtime_date',
        'approved_at',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'hours_requested' => 'decimal:2',
        'approved_hours' => 'decimal:2',
        'multiplier_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'is_processed_in_payroll' => 'boolean'
    ];

    /**
     * Get the user that owns the overtime request
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who approved this request
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the payroll transaction associated with this overtime
     */
    public function payrollTransaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'payroll_transaction_id');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $business_id)
    {
        return $query->where('business_id', $business_id);
    }

    /**
     * Check if the overtime can be approved
     */
    public function canBeApproved()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the overtime can be rejected
     */
    public function canBeRejected()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the overtime is processed in payroll
     */
    public function isProcessedInPayroll()
    {
        return $this->is_processed_in_payroll && !empty($this->payroll_transaction_id);
    }

    /**
     * Get overtime type label
     */
    public function getOvertimeTypeLabel()
    {
        $labels = [
            'workday' => 'Workday',
            'weekend' => 'Weekend',
            'holiday' => 'Holiday'
        ];

        return $labels[$this->overtime_type] ?? 'Unknown';
    }

    /**
     * Get status label with color
     */
    public function getStatusLabel()
    {
        $labels = [
            'draft' => ['label' => 'Draft', 'class' => 'label-default'],
            'pending' => ['label' => 'Pending', 'class' => 'label-warning'],
            'approved' => ['label' => 'Approved', 'class' => 'label-success'],
            'rejected' => ['label' => 'Rejected', 'class' => 'label-danger']
        ];

        return $labels[$this->status] ?? ['label' => 'Unknown', 'class' => 'label-default'];
    }

    /**
     * Calculate total overtime amount
     */
    public function calculateTotalAmount()
    {
        if (empty($this->approved_hours) || empty($this->hourly_rate) || empty($this->multiplier_rate)) {
            return 0;
        }

        return $this->approved_hours * $this->hourly_rate * $this->multiplier_rate;
    }
}