<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsOvertimeRequest;
use Modules\Essentials\Entities\OtSession;
use Modules\Essentials\Entities\Shift;
use Modules\Essentials\Utils\EssentialsUtil;
use Carbon\Carbon;

class HrmController extends BaseApiController
{
    protected $moduleUtil;
    protected $essentialsUtil;

    public function __construct(ModuleUtil $moduleUtil, EssentialsUtil $essentialsUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->essentialsUtil = $essentialsUtil;
    }

    /**
     * Get all users/employees
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $per_page = $request->get('per_page', 25);
            $search = $request->get('search');
            $is_active = $request->get('is_active');
            
            $query = User::where('business_id', $business_id)
                ->select([
                    'id',
                    'first_name',
                    'last_name', 
                    'username',
                    'email',
                    'contact_number',
                    'status',
                    'join_date',
                    'dob',
                    'bank_details',
                    'created_at'
                ]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($is_active !== null) {
                $query->where('status', $is_active ? 'active' : 'inactive');
            }

            $users = $query->paginate($per_page);

            $users->getCollection()->transform(function ($user) {
                $bank_details = $user->bank_details ? json_decode($user->bank_details, true) : [];
                
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'mobile' => $user->contact_number,
                    'contact_number' => $user->contact_number,
                    'status' => $user->status,
                    'is_active' => $user->status === 'active',
                    'join_date' => $user->join_date ? Carbon::parse($user->join_date)->format('Y-m-d') : null,
                    'date_of_birth' => $user->dob ? Carbon::parse($user->dob)->format('Y-m-d') : null,
                    'dob' => $user->dob ? Carbon::parse($user->dob)->format('Y-m-d') : null,
                    // Banking Information
                    'account_holder_name' => $bank_details['account_holder_name'] ?? null,
                    'account_number' => $bank_details['account_number'] ?? null,
                    'bank_name' => $bank_details['bank_name'] ?? null,
                    'bank_code' => $bank_details['bank_code'] ?? null,
                    'branch' => $bank_details['branch'] ?? null,
                    'tax_payer_id' => $bank_details['tax_payer_id'] ?? null,
                    'created_at' => $user->created_at->toISOString()
                ];
            });

            return $this->successResponse([
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage()
                ]
            ], 'Users retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve users: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get specific user details
     */
    public function getUserDetails(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $user = User::where('business_id', $business_id)
                ->where('id', $id)
                ->select([
                    'id', 'first_name', 'last_name', 'username', 'email', 
                    'contact_number', 'status', 'join_date', 'dob', 'bank_details', 'created_at',
                    'essentials_department_id', 'essentials_designation_id', 'essentials_salary',
                    'alt_number', 'family_number', 'gender', 'marital_status', 'blood_group',
                    'permanent_address', 'current_address'
                ])
                ->first();

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            $bank_details = $user->bank_details ? json_decode($user->bank_details, true) : [];
            
            $userData = [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'mobile' => $user->contact_number,
                'contact_number' => $user->contact_number,
                'alt_number' => $user->alt_number,
                'family_number' => $user->family_number,
                'status' => $user->status,
                'is_active' => $user->status === 'active',
                'join_date' => $user->join_date ? Carbon::parse($user->join_date)->format('Y-m-d') : null,
                'date_of_birth' => $user->dob ? Carbon::parse($user->dob)->format('Y-m-d') : null,
                'dob' => $user->dob ? Carbon::parse($user->dob)->format('Y-m-d') : null,
                'gender' => $user->gender,
                'marital_status' => $user->marital_status,
                'blood_group' => $user->blood_group,
                'permanent_address' => $user->permanent_address,
                'current_address' => $user->current_address,
                // HR Information
                'department_id' => $user->essentials_department_id,
                'designation_id' => $user->essentials_designation_id,
                'salary' => $user->essentials_salary,
                // Banking Information
                'account_holder_name' => $bank_details['account_holder_name'] ?? null,
                'account_number' => $bank_details['account_number'] ?? null,
                'bank_name' => $bank_details['bank_name'] ?? null,
                'bank_code' => $bank_details['bank_code'] ?? null,
                'branch' => $bank_details['branch'] ?? null,
                'tax_payer_id' => $bank_details['tax_payer_id'] ?? null,
                'created_at' => $user->created_at->toISOString(),
                'shifts' => [] // Can be enhanced with actual shift data later
            ];

            return $this->successResponse([
                'user' => $userData
            ], 'User details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check in attendance
     */
    public function checkIn(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'clock_in_time' => 'sometimes|date_format:Y-m-d H:i:s',
                'clock_in_ip_address' => 'sometimes|ip',
                'clock_in_note' => 'sometimes|string|max:255'
            ]);

            $user_id = $request->user_id;
            $clock_in_time = $request->get('clock_in_time', now());
            
            // Verify user belongs to business
            $user = User::where('business_id', $business_id)->where('id', $user_id)->first();
            if (!$user) {
                return $this->errorResponse('User not found or does not belong to this business', 404);
            }

            // Business Logic: Check if user is already checked in today
            $today = Carbon::parse($clock_in_time)->format('Y-m-d');
            $existingAttendance = EssentialsAttendance::where('user_id', $user_id)
                ->where('business_id', $business_id)
                ->where('clock_in_time', '>=', $today . ' 00:00:00')
                ->where('clock_in_time', '<=', $today . ' 23:59:59')
                ->whereNull('clock_out_time')
                ->first();

            if ($existingAttendance) {
                return $this->errorResponse('User is already checked in for today', 400);
            }

            // Create attendance record
            $attendance = EssentialsAttendance::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'clock_in_time' => $clock_in_time,
                'clock_in_ip_address' => $request->get('clock_in_ip_address', $request->ip()),
                'clock_in_note' => $request->get('clock_in_note'),
                'created_by' => auth()->id() ?? $user_id
            ]);

            return $this->successResponse([
                'attendance' => [
                    'id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'clock_in_time' => $attendance->clock_in_time,
                    'clock_in_note' => $attendance->clock_in_note,
                    'status' => 'checked_in'
                ]
            ], 'Check-in successful');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check in: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check out attendance
     */
    public function checkOut(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'clock_out_time' => 'sometimes|date_format:Y-m-d H:i:s',
                'clock_out_ip_address' => 'sometimes|ip',
                'clock_out_note' => 'sometimes|string|max:255'
            ]);

            $user_id = $request->user_id;
            $clock_out_time = $request->get('clock_out_time', now());
            
            // Business Logic: Find today's attendance that hasn't been checked out
            $today = Carbon::parse($clock_out_time)->format('Y-m-d');
            $attendance = EssentialsAttendance::where('user_id', $user_id)
                ->where('business_id', $business_id)
                ->where('clock_in_time', '>=', $today . ' 00:00:00')
                ->where('clock_in_time', '<=', $today . ' 23:59:59')
                ->whereNull('clock_out_time')
                ->first();

            if (!$attendance) {
                return $this->errorResponse('No active check-in found for today', 404);
            }

            // Update attendance with check-out information
            $attendance->update([
                'clock_out_time' => $clock_out_time,
                'clock_out_ip_address' => $request->get('clock_out_ip_address', $request->ip()),
                'clock_out_note' => $request->get('clock_out_note')
            ]);

            // Calculate total hours
            $clock_in = Carbon::parse($attendance->clock_in_time);
            $clock_out = Carbon::parse($clock_out_time);
            $total_hours = $clock_out->diffInMinutes($clock_in) / 60;

            return $this->successResponse([
                'attendance' => [
                    'id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'clock_in_time' => $attendance->clock_in_time,
                    'clock_out_time' => $attendance->clock_out_time,
                    'total_hours' => round($total_hours, 2),
                    'clock_out_note' => $attendance->clock_out_note,
                    'status' => 'checked_out'
                ]
            ], 'Check-out successful');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check out: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Start overtime session
     */
    public function overtimeIn(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'overtime_date' => 'sometimes|date',
                'start_time' => 'sometimes|date_format:H:i:s',
                'reason' => 'sometimes|string|max:255',
                'description' => 'sometimes|string|max:500'
            ]);

            $user_id = $request->user_id;
            $overtime_date = $request->get('overtime_date', now()->format('Y-m-d'));
            $start_time = $request->get('start_time', now()->format('H:i:s'));
            
            // Business Logic: Validate user belongs to business and is active
            $user = User::where('id', $user_id)
                ->where('business_id', $business_id)
                ->where('status', 'active')
                ->first();

            if (!$user) {
                return $this->errorResponse('Invalid user or user not active', 404);
            }

            // Business Logic: Check if user has checked in today (should be present for overtime)
            $today = now()->format('Y-m-d');
            $attendance = EssentialsAttendance::where('user_id', $user_id)
                ->where('business_id', $business_id)
                ->where('clock_in_time', '>=', $today . ' 00:00:00')
                ->where('clock_in_time', '<=', $today . ' 23:59:59')
                ->first();

            if (!$attendance) {
                return $this->errorResponse('User must clock in first before starting overtime', 400);
            }

            // Business Logic: Check if there's already an active OT session
            $activeSession = OtSession::where('user_id', $user_id)
                ->where('business_id', $business_id)
                ->where('status', 'active')
                ->first();

            if ($activeSession) {
                return $this->errorResponse('User already has an active overtime session', 400);
            }

            // Create OT session
            $otSession = OtSession::create([
                'business_id' => $business_id,
                'user_id' => $user_id,
                'start_time' => now(),
                'start_note' => $request->get('reason') ?? $request->get('description'),
                'status' => 'active'
            ]);

            return $this->successResponse([
                'overtime_session' => [
                    'id' => $otSession->id,
                    'user_id' => $otSession->user_id,
                    'start_time' => $otSession->start_time,
                    'start_note' => $otSession->start_note,
                    'status' => $otSession->status
                ]
            ], 'Overtime session started');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to start overtime: ' . $e->getMessage(), 500);
        }
    }

    /**
     * End overtime session
     */
    public function overtimeOut(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'overtime_date' => 'sometimes|date',
                'end_time' => 'sometimes|date_format:H:i:s'
            ]);

            $user_id = $request->user_id;
            $end_time = now();
            
            // Business Logic: Validate user belongs to business
            $user = User::where('id', $user_id)
                ->where('business_id', $business_id)
                ->where('status', 'active')
                ->first();

            if (!$user) {
                return $this->errorResponse('Invalid user or user not active', 404);
            }

            // Business Logic: Find active OT session
            $otSession = OtSession::where('user_id', $user_id)
                ->where('business_id', $business_id)
                ->where('status', 'active')
                ->first();

            if (!$otSession) {
                return $this->errorResponse('No active overtime session found', 404);
            }

            // Business Logic: Validate minimum overtime duration (e.g., at least 15 minutes)
            $start = Carbon::parse($otSession->start_time);
            $end = Carbon::parse($end_time);
            $duration_minutes = $end->diffInMinutes($start);

            if ($duration_minutes < 15) {
                return $this->errorResponse('Overtime session must be at least 15 minutes long', 400);
            }

            // Update with end time
            $otSession->update([
                'end_time' => $end_time,
                'status' => 'completed'
            ]);

            // Calculate total hours
            $start = Carbon::parse($otSession->start_time);
            $end = Carbon::parse($end_time);
            $total_hours = $end->diffInMinutes($start) / 60;

            return $this->successResponse([
                'overtime_session' => [
                    'id' => $otSession->id,
                    'user_id' => $otSession->user_id,
                    'start_time' => $otSession->start_time,
                    'end_time' => $otSession->end_time,
                    'total_hours' => round($total_hours, 2),
                    'status' => $otSession->status
                ]
            ], 'Overtime session ended');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to end overtime: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get attendance records
     */
    public function attendance(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $user_id = $request->get('user_id');
            $start_date = $request->get('start_date', now()->subDays(7)->format('Y-m-d'));
            $end_date = $request->get('end_date', now()->format('Y-m-d'));
            $per_page = $request->get('per_page', 25);

            $query = EssentialsAttendance::where('business_id', $business_id)
                ->with(['employee:id,first_name,last_name'])
                ->whereBetween('clock_in_time', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);

            if ($user_id) {
                $query->where('user_id', $user_id);
            }

            $attendance = $query->latest('clock_in_time')->paginate($per_page);

            $attendance->getCollection()->transform(function ($record) {
                $clock_in = Carbon::parse($record->clock_in_time);
                $clock_out = $record->clock_out_time ? Carbon::parse($record->clock_out_time) : null;
                $total_hours = $clock_out ? $clock_out->diffInMinutes($clock_in) / 60 : null;

                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'employee_name' => $record->employee ? $record->employee->first_name . ' ' . $record->employee->last_name : null,
                    'date' => $clock_in->format('Y-m-d'),
                    'clock_in_time' => $record->clock_in_time,
                    'clock_out_time' => $record->clock_out_time,
                    'total_hours' => $total_hours ? round($total_hours, 2) : null,
                    'status' => $record->clock_out_time ? 'completed' : 'active',
                    'clock_in_note' => $record->clock_in_note,
                    'clock_out_note' => $record->clock_out_note
                ];
            });

            return $this->successResponse([
                'attendance' => $attendance->items(),
                'pagination' => [
                    'current_page' => $attendance->currentPage(),
                    'per_page' => $attendance->perPage(),
                    'total' => $attendance->total(),
                    'last_page' => $attendance->lastPage()
                ]
            ], 'Attendance records retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get overtime requests
     */
    public function overtime(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $user_id = $request->get('user_id');
            $status = $request->get('status'); // pending, approved, rejected
            $start_date = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $end_date = $request->get('end_date', now()->format('Y-m-d'));
            $per_page = $request->get('per_page', 25);

            $query = EssentialsOvertimeRequest::where('business_id', $business_id)
                ->with(['user:id,first_name,last_name', 'approver:id,first_name,last_name'])
                ->whereBetween('overtime_date', [$start_date, $end_date]);

            if ($user_id) {
                $query->where('user_id', $user_id);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $overtime = $query->latest('overtime_date')->paginate($per_page);

            $overtime->getCollection()->transform(function ($record) {
                return [
                    'id' => $record->id,
                    'user_id' => $record->user_id,
                    'employee_name' => $record->user ? $record->user->first_name . ' ' . $record->user->last_name : null,
                    'overtime_date' => $record->overtime_date,
                    'start_time' => $record->start_time,
                    'end_time' => $record->end_time,
                    'hours_requested' => (float) $record->hours_requested,
                    'approved_hours' => (float) $record->approved_hours,
                    'overtime_type' => $record->overtime_type,
                    'multiplier_rate' => (float) $record->multiplier_rate,
                    'total_amount' => (float) $record->total_amount,
                    'status' => $record->status,
                    'reason' => $record->reason,
                    'description' => $record->description,
                    'approved_by_name' => $record->approver ? $record->approver->first_name . ' ' . $record->approver->last_name : null,
                    'approved_at' => $record->approved_at ? $record->approved_at->toISOString() : null,
                    'rejection_reason' => $record->rejection_reason,
                    'created_at' => $record->created_at->toISOString()
                ];
            });

            return $this->successResponse([
                'overtime_requests' => $overtime->items(),
                'pagination' => [
                    'current_page' => $overtime->currentPage(),
                    'per_page' => $overtime->perPage(),
                    'total' => $overtime->total(),
                    'last_page' => $overtime->lastPage()
                ]
            ], 'Overtime requests retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve overtime requests: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create overtime request
     */
    public function createOvertimeRequest(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'overtime_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'reason' => 'required|string|max:255',
                'description' => 'sometimes|string|max:500'
            ]);

            // Business Logic: Validate user belongs to business and is active
            $user = User::where('id', $request->user_id)
                ->where('business_id', $business_id)
                ->where('status', 'active')
                ->first();

            if (!$user) {
                return $this->errorResponse('Invalid user or user not active', 404);
            }

            // Business Logic: Validate overtime date is not in the future beyond reasonable limits
            $overtime_date = Carbon::parse($request->overtime_date);
            $max_future_date = now()->addDays(30); // Allow requests up to 30 days in advance
            $min_past_date = now()->subDays(7); // Allow requests up to 7 days in the past

            if ($overtime_date->gt($max_future_date)) {
                return $this->errorResponse('Cannot request overtime more than 30 days in advance', 400);
            }

            if ($overtime_date->lt($min_past_date)) {
                return $this->errorResponse('Cannot request overtime for dates older than 7 days', 400);
            }

            // Business Logic: Check for duplicate requests on the same date
            $existing_request = EssentialsOvertimeRequest::where('user_id', $request->user_id)
                ->where('business_id', $business_id)
                ->where('overtime_date', $request->overtime_date)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existing_request) {
                return $this->errorResponse('An overtime request already exists for this date', 400);
            }

            // Business Logic: Calculate hours and validate minimum duration
            $start = Carbon::createFromFormat('H:i', $request->start_time);
            $end = Carbon::createFromFormat('H:i', $request->end_time);
            $hours_requested = $end->diffInMinutes($start) / 60;

            if ($hours_requested < 0.25) { // Minimum 15 minutes
                return $this->errorResponse('Overtime request must be at least 15 minutes', 400);
            }

            if ($hours_requested > 12) { // Maximum 12 hours per request
                return $this->errorResponse('Overtime request cannot exceed 12 hours per day', 400);
            }

            // Business Logic: Determine overtime type and multiplier
            $overtime_type = $this->essentialsUtil->determineOvertimeType($overtime_date);
            $multiplier_rate = $this->essentialsUtil->getOvertimeMultiplier($overtime_type);

            $overtimeRequest = EssentialsOvertimeRequest::create([
                'business_id' => $business_id,
                'user_id' => $request->user_id,
                'overtime_date' => $request->overtime_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'hours_requested' => $hours_requested,
                'overtime_type' => $overtime_type,
                'multiplier_rate' => $multiplier_rate,
                'reason' => $request->reason,
                'description' => $request->get('description'),
                'status' => 'pending'
            ]);

            return $this->successResponse([
                'overtime_request' => [
                    'id' => $overtimeRequest->id,
                    'user_id' => $overtimeRequest->user_id,
                    'overtime_date' => $overtimeRequest->overtime_date,
                    'start_time' => $overtimeRequest->start_time,
                    'end_time' => $overtimeRequest->end_time,
                    'hours_requested' => (float) $overtimeRequest->hours_requested,
                    'overtime_type' => $overtimeRequest->overtime_type,
                    'multiplier_rate' => (float) $overtimeRequest->multiplier_rate,
                    'status' => $overtimeRequest->status,
                    'reason' => $overtimeRequest->reason
                ]
            ], 'Overtime request created successfully');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create overtime request: ' . $e->getMessage(), 500);
        }
    }
}