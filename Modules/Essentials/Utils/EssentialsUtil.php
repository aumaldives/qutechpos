<?php

namespace Modules\Essentials\Utils;

use App\Transaction;
use App\Utils\Util;
use DB;
use Illuminate\Support\Facades\View;
use Modules\Essentials\Entities\EssentialsAllowanceAndDeduction;
use Modules\Essentials\Entities\EssentialsAttendance;
use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsUserShift;
use Modules\Essentials\Entities\Shift;

class EssentialsUtil extends Util
{
    /**
     * Function to calculate total work duration of a user for a period of time
     *
     * @param  string  $unit
     * @param  int  $user_id
     * @param  int  $business_id
     * @param  int  $start_date = null
     * @param  int  $end_date = null
     */
    public function getTotalWorkDuration(
        $unit,
        $user_id,
        $business_id,
        $start_date = null,
        $end_date = null
    ) {
        $total_work_duration = 0;
        if ($unit == 'hour') {
            $query = EssentialsAttendance::where('business_id', $business_id)
                                        ->where('user_id', $user_id)
                                        ->whereNotNull('clock_out_time');

            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate('clock_in_time', '>=', $start_date)
                            ->whereDate('clock_in_time', '<=', $end_date);
            }

            $minutes_sum = $query->select(DB::raw('SUM(TIMESTAMPDIFF(MINUTE, clock_in_time, clock_out_time)) as total_minutes'))->first();
            $total_work_duration = ! empty($minutes_sum->total_minutes) ? $minutes_sum->total_minutes / 60 : 0;
        }

        return number_format($total_work_duration, 2);
    }

    /**
     * Parses month and year from date
     *
     * @param  string  $month_year
     */
    public function getDateFromMonthYear($month_year)
    {
        $month_year_arr = explode('/', $month_year);
        $month = $month_year_arr[0];
        $year = $month_year_arr[1];

        $transaction_date = $year.'-'.$month.'-01';

        return $transaction_date;
    }

    /**
     * Retrieves all allowances and deductions of an employeee
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  string  $start_date = null
     * @param  string  $end_date = null
     */
    public function getEmployeeAllowancesAndDeductions($business_id, $user_id, $start_date = null, $end_date = null)
    {
        $query = EssentialsAllowanceAndDeduction::join('essentials_user_allowance_and_deductions as euad', 'euad.allowance_deduction_id', '=', 'essentials_allowances_and_deductions.id')
                ->where('business_id', $business_id)
                ->where('euad.user_id', $user_id);

        //Filter if applicable one
        if (! empty($start_date) && ! empty($end_date)) {
            $query->where(function ($q) use ($start_date, $end_date) {
                $q->whereNull('applicable_date')
                    ->orWhereBetween('applicable_date', [$start_date, $end_date]);
            });
        }
        $allowances_and_deductions = $query->get();

        return $allowances_and_deductions;
    }

    /**
     * Validates user clock in and returns available shift id
     */
    public function checkUserShift($user_id, $settings, $clock_in_time = null)
    {
        $shift_id = null;
        $shift_date = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time) : \Carbon::now();
        $shift_datetime = $shift_date->format('Y-m-d');
        $day_string = strtolower($shift_date->format('l'));
        $grace_before_checkin = ! empty($settings['grace_before_checkin']) ? (int) $settings['grace_before_checkin'] : 0;
        $grace_after_checkin = ! empty($settings['grace_after_checkin']) ? (int) $settings['grace_after_checkin'] : 0;
        $clock_in_start = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->subMinutes($grace_before_checkin) : \Carbon::now()->subMinutes($grace_before_checkin);
        $clock_in_end = ! empty($clock_in_time) ? \Carbon::parse($clock_in_time)->addMinutes($grace_after_checkin) : \Carbon::now()->addMinutes($grace_after_checkin);

        $user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=', 'essentials_user_shifts.essentials_shift_id')
                    ->where('user_id', $user_id)
                    ->where('start_date', '<=', $shift_datetime)
                    ->where(function ($q) use ($shift_datetime) {
                        $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', $shift_datetime);
                    })
                    ->select('essentials_user_shifts.*', 's.holidays', 's.start_time', 's.end_time', 's.type')
                    ->get();

        foreach ($user_shifts as $shift) {
            $holidays = json_decode($shift->holidays, true);
            //check if it's a weekend/non-working day for this shift
            if (is_array($holidays) && in_array($day_string, $holidays)) {
                continue;
            }

            //Check allocated shift time
            if ((! empty($shift->start_time) && \Carbon::parse($shift->start_time)->between($clock_in_start, $clock_in_end)) || $shift->type == 'flexible_shift') {
                return $shift->essentials_shift_id;
            }
        }

        return $shift_id;
    }

    /**
     * Validates user clock out
     */
    public function canClockOut($clock_in, $settings, $clock_out_time = null)
    {
        $shift = Shift::find($clock_in->essentials_shift_id);
        if (empty($shift->end_time)) {
            return true;
        }

        $grace_before_checkout = ! empty($settings['grace_before_checkout']) ? (int) $settings['grace_before_checkout'] : 0;
        $grace_after_checkout = ! empty($settings['grace_after_checkout']) ? (int) $settings['grace_after_checkout'] : 0;
        $clock_out_start = empty($clock_out_time) ? \Carbon::now()->subMinutes($grace_before_checkout) : \Carbon::parse($clock_out_time)->subMinutes($grace_before_checkout);

        $clock_out_end = empty($clock_out_time) ? \Carbon::now()->addMinutes($grace_after_checkout) : \Carbon::parse($clock_out_time)->addMinutes($grace_after_checkout);

        if ((\Carbon::parse($shift->end_time)->between($clock_out_start, $clock_out_end)) || $shift->type == 'flexible_shift') {
            return true;
        } else {
            return false;
        }
    }

    public function clockin($data, $essentials_settings)
    {
        //Check user can clockin
        $clock_in_time = is_object($data['clock_in_time']) ? $data['clock_in_time']->toDateTimeString() : $data['clock_in_time'];

        $shift = $this->checkUserShift($data['user_id'], $essentials_settings, $clock_in_time);

        if (empty($shift)) {
            $available_shifts = $this->getAllAvailableShiftsForGivenUser($data['business_id'], $data['user_id']);

            $available_shifts_html = view('essentials::attendance.avail_shifts')
                                        ->with(compact('available_shifts'))
                                        ->render();

            $output = ['success' => false,
                'msg' => __('essentials::lang.shift_not_allocated'),
                'type' => 'clock_in',
                'shift_details' => $available_shifts_html,
            ];

            return $output;
        }

        $data['essentials_shift_id'] = $shift;

        //Check if already clocked in
        $count = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->count();
        if ($count == 0) {
            EssentialsAttendance::create($data);

            $shift_info = Shift::getGivenShiftInfo($data['business_id'], $shift);
            $current_shift_html = view('essentials::attendance.current_shift')
                                    ->with(compact('shift_info'))
                                    ->render();

            $output = ['success' => true,
                'msg' => __('essentials::lang.clock_in_success'),
                'type' => 'clock_in',
                'current_shift' => $current_shift_html,
            ];
        } else {
            $output = ['success' => false,
                'msg' => __('essentials::lang.already_clocked_in'),
                'type' => 'clock_in',
            ];
        }

        return $output;
    }

    public function clockout($data, $essentials_settings)
    {

        //Get clock in
        $clock_in = EssentialsAttendance::where('business_id', $data['business_id'])
                                ->where('user_id', $data['user_id'])
                                ->whereNull('clock_out_time')
                                ->first();
        $clock_out_time = is_object($data['clock_out_time']) ? $data['clock_out_time']->toDateTimeString() : $data['clock_out_time'];

        if (! empty($clock_in)) {
            $can_clockout = $this->canClockOut($clock_in, $essentials_settings, $clock_out_time);
            if (! $can_clockout) {
                $output = ['success' => false,
                    'msg' => __('essentials::lang.shift_not_over'),
                    'type' => 'clock_out',
                ];

                return $output;
            }

            $clock_in->clock_out_time = $data['clock_out_time'];
            $clock_in->clock_out_note = $data['clock_out_note'];
            $clock_in->clock_out_location = $data['clock_out_location'] ?? '';
            $clock_in->save();

            $output = ['success' => true,
                'msg' => __('essentials::lang.clock_out_success'),
                'type' => 'clock_out',
            ];
        } else {
            $output = ['success' => false,
                'msg' => __('essentials::lang.not_clocked_in'),
                'type' => 'clock_out',
            ];
        }

        return $output;
    }

    public function getAllAvailableShiftsForGivenUser($business_id, $user_id)
    {
        $available_user_shifts = EssentialsUserShift::join('essentials_shifts as s', 's.id', '=',
                                    'essentials_user_shifts.essentials_shift_id')
                                    ->where('user_id', $user_id)
                                    ->where('s.business_id', $business_id)
                                    ->whereDate('start_date', '<=', \Carbon::today())
                                    ->whereDate('end_date', '>=', \Carbon::today())
                                    ->select('essentials_user_shifts.start_date', 'essentials_user_shifts.end_date',
                                        's.name', 's.type', 's.start_time', 's.end_time', 's.holidays')
                                    ->get();

        return $available_user_shifts;
    }

    /**
     * get total leaves of and employee for given date
     *
     * @param  int  $business_id
     * @param  int  $employee_id
     * @param  string  $start_date
     * @param  string  $end_date
     */
    public function getTotalLeavesForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $leaves = EssentialsLeave::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereDate('start_date', '>=', $start_date)
                        ->whereDate('end_date', '<=', $end_date)
                        ->get();

        $total_leaves = 0;
        foreach ($leaves as $key => $leave) {
            $start_date = \Carbon::parse($leave->start_date);
            $end_date = \Carbon::parse($leave->end_date);

            $diff = $start_date->diffInDays($end_date);
            $diff += 1;
            $total_leaves += $diff;
        }

        return $total_leaves;
    }

    public function getTotalDaysWorkedForGivenDateOfAnEmployee($business_id, $employee_id, $start_date, $end_date)
    {
        $attendances = EssentialsAttendance::where('business_id', $business_id)
                        ->where('user_id', $employee_id)
                        ->whereNotNull('clock_out_time')
                        ->whereDate('clock_in_time', '>=', $start_date)
                        ->whereDate('clock_in_time', '<=', $end_date)
                        ->get()
                        ->groupBy(function ($attendance, $key) {
                            return \Carbon::parse($attendance->clock_in_time)->format('Y-m-d');
                        });

        return count($attendances);
    }

    public function getPayrollQuery($business_id)
    {
        $payrolls = Transaction::where('transactions.business_id', $business_id)
                    ->where('type', 'payroll')
                    ->join('users as u', 'u.id', '=', 'transactions.expense_for')
                    ->leftJoin('categories as dept', 'u.essentials_department_id', '=', 'dept.id')
                    ->leftJoin('categories as dsgn', 'u.essentials_designation_id', '=', 'dsgn.id')
                    ->leftJoin('essentials_payroll_group_transactions as epgt', 'transactions.id', '=', 'epgt.transaction_id')
                    ->leftJoin('essentials_payroll_groups as epg', 'epgt.payroll_group_id', '=', 'epg.id')
                    ->select([
                        'transactions.id',
                        DB::raw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as user"),
                        'final_total',
                        'transaction_date',
                        'ref_no',
                        'transactions.payment_status',
                        'dept.name as department',
                        'dsgn.name as designation',
                        'epgt.payroll_group_id',
                    ]);

        return $payrolls;
    }

    public function getEssentialsSettings()
    {
        $settings = request()->session()->get('business.essentials_settings');
        $settings = ! empty($settings) ? json_decode($settings, true) : [];

        return $settings;
    }

    /**
     * Calculate leave balances for a user
     *
     * @param int $user_id
     * @param int $business_id
     * @return array
     */
    public function getUserLeaveBalances($user_id, $business_id)
    {
        $user = \App\User::find($user_id);
        if (empty($user) || empty($user->join_date)) {
            return [];
        }

        $leave_types = \Modules\Essentials\Entities\EssentialsLeaveType::where('business_id', $business_id)->get();
        $balances = [];

        foreach ($leave_types as $leave_type) {
            if (empty($leave_type->max_leave_count)) {
                continue;
            }

            $balance = [
                'leave_type' => $leave_type->leave_type,
                'max_count' => $leave_type->max_leave_count,
                'used_count' => 0,
                'remaining_count' => $leave_type->max_leave_count,
                'is_paid' => $leave_type->is_paid
            ];

            // Calculate period based on leave_count_interval
            $start_date = null;
            $end_date = \Carbon::now();

            if ($leave_type->leave_count_interval == 'month') {
                $start_date = \Carbon::now()->startOfMonth();
            } elseif ($leave_type->leave_count_interval == 'year') {
                $start_date = \Carbon::now()->startOfYear();
            } elseif ($leave_type->leave_count_interval == 'join_date_anniversary') {
                // Calculate from last anniversary of join date
                $join_date = \Carbon::parse($user->join_date);
                $current_year_anniversary = $join_date->copy()->year(\Carbon::now()->year);
                
                if ($current_year_anniversary->isFuture()) {
                    // Use previous year's anniversary
                    $start_date = $current_year_anniversary->subYear();
                    $end_date = $current_year_anniversary->copy()->subDay();
                } else {
                    $start_date = $current_year_anniversary;
                    // End date should be next year's anniversary minus 1 day
                    $end_date = $current_year_anniversary->copy()->addYear()->subDay();
                }
            }

            if ($start_date) {
                // Get used leaves in the period
                $used_leaves = \Modules\Essentials\Entities\EssentialsLeave::where('business_id', $business_id)
                    ->where('user_id', $user_id)
                    ->where('essentials_leave_type_id', $leave_type->id)
                    ->whereDate('start_date', '>=', $start_date->format('Y-m-d'))
                    ->whereDate('end_date', '<=', $end_date->format('Y-m-d'))
                    ->where('status', 'approved')
                    ->get();

                $used_count = 0;
                foreach ($used_leaves as $leave) {
                    $leave_start = \Carbon::parse($leave->start_date);
                    $leave_end = \Carbon::parse($leave->end_date);
                    $used_count += $leave_start->diffInDays($leave_end) + 1;
                }

                $balance['used_count'] = $used_count;
                $balance['remaining_count'] = max(0, $leave_type->max_leave_count - $used_count);
            }

            $balances[] = $balance;
        }

        return $balances;
    }

    public function calculateHourlyRate($user_id, $base_salary, $month, $year, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }
        
        $settings = $this->getEssentialsSettings($business_id);
        
        // Default settings if not configured
        $method = $settings['hourly_rate_calculation_method'] ?? 'exclude_shift_weekends_and_holidays';
        $include_paid_leaves = !empty($settings['include_paid_leaves_in_hourly_rate']);
        $include_unpaid_leaves = !empty($settings['include_unpaid_leaves_in_hourly_rate']);
        
        $working_days = $this->calculateWorkingDaysForUser($user_id, $month, $year, $method, $include_paid_leaves, $include_unpaid_leaves);
        
        if ($working_days <= 0) {
            return 0;
        }
        
        // Always calculate actual hours per working day based on user's shift
        $hours_per_day = 8; // Default fallback for users without shifts
        
        // Get user's current active shift
        $user = \App\User::find($user_id);
        $current_date = \Carbon::create($year, $month, 1)->format('Y-m-d');
        
        // Check for shift assignment through EssentialsUserShift table first
        $user_shift = \Modules\Essentials\Entities\EssentialsUserShift::where('user_id', $user_id)
            ->where('start_date', '<=', $current_date)
            ->where(function($q) use ($current_date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $current_date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
            
        if ($user_shift) {
            $shift = \Modules\Essentials\Entities\Shift::find($user_shift->essentials_shift_id);
            if ($shift && $shift->type === 'fixed_shift') {
                $start = \Carbon::parse($shift->start_time);
                $end = \Carbon::parse($shift->end_time);
                if ($end->lt($start)) {
                    $end->addDay(); // Handle shifts crossing midnight
                }
                $shift_hours = $start->diffInHours($end, false);
                if ($shift_hours > 0) {
                    $hours_per_day = $shift_hours;
                }
            }
        } 
        // Fallback to direct user shift assignment (legacy)
        elseif (!empty($user->essentials_shift_id)) {
            $shift = \Modules\Essentials\Entities\Shift::find($user->essentials_shift_id);
            if ($shift && $shift->type === 'fixed_shift') {
                $start = \Carbon::parse($shift->start_time);
                $end = \Carbon::parse($shift->end_time);
                if ($end->lt($start)) {
                    $end->addDay(); // Handle shifts crossing midnight
                }
                $shift_hours = $start->diffInHours($end, false);
                if ($shift_hours > 0) {
                    $hours_per_day = $shift_hours;
                }
            }
        }
        
        $working_hours = $working_days * $hours_per_day;
        
        return $base_salary / $working_hours;
    }

    public function calculateWorkingDaysForUser($user_id, $month, $year, $method = 'exclude_shift_weekends_and_holidays', $include_paid_leaves = false, $include_unpaid_leaves = false)
    {
        $start_date = \Carbon::create($year, $month, 1);
        $end_date = $start_date->copy()->endOfMonth();
        $total_days = $start_date->daysInMonth;
        
        $working_days = $total_days;
        
        // Get user's current active shift
        $user = \App\User::find($user_id);
        $current_date = \Carbon::create($year, $month, 1)->format('Y-m-d');
        $shift = null;
        
        // Check for shift assignment through EssentialsUserShift table first
        $user_shift = \Modules\Essentials\Entities\EssentialsUserShift::where('user_id', $user_id)
            ->where('start_date', '<=', $current_date)
            ->where(function($q) use ($current_date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $current_date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
            
        if ($user_shift) {
            $shift = \Modules\Essentials\Entities\Shift::find($user_shift->essentials_shift_id);
        } 
        // Fallback to direct user shift assignment (legacy)
        elseif (!empty($user->essentials_shift_id)) {
            $shift = \Modules\Essentials\Entities\Shift::find($user->essentials_shift_id);
        }
        
        switch ($method) {
            case 'exclude_shift_weekends_and_holidays':
                $working_days = $this->excludeShiftWeekendsAndHolidays($start_date, $end_date, $shift, $user->business_id);
                break;
                
            case 'exclude_holidays_only':
                $working_days = $this->excludeHolidaysOnly($start_date, $end_date, $user->business_id);
                break;
                
            case 'exclude_weekends_only':
                $working_days = $this->excludeWeekendsOnly($start_date, $end_date, $shift);
                break;
                
            case 'include_all_days':
            default:
                // Keep all days
                break;
        }
        
        // Handle leaves - subtract days for leaves that should NOT be included in working time
        if (!$include_paid_leaves || !$include_unpaid_leaves) {
            $leaves = $this->getLeavesForPeriod($user_id, $start_date, $end_date, $include_paid_leaves, $include_unpaid_leaves);
            $working_days -= $leaves;
        }
        
        return max(0, $working_days);
    }

    private function excludeShiftWeekendsAndHolidays($start_date, $end_date, $shift, $business_id)
    {
        $working_days = 0;
        $current = $start_date->copy();
        
        // Get holidays for the period
        $holidays = \Modules\Essentials\Entities\EssentialsHoliday::where('business_id', $business_id)
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('start_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                      ->orWhereBetween('end_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                      ->orWhere(function ($q) use ($start_date, $end_date) {
                          $q->where('start_date', '<=', $start_date->format('Y-m-d'))
                            ->where('end_date', '>=', $end_date->format('Y-m-d'));
                      });
            })
            ->get();
        
        $holiday_dates = [];
        foreach ($holidays as $holiday) {
            $holiday_start = \Carbon::parse($holiday->start_date);
            $holiday_end = \Carbon::parse($holiday->end_date);
            
            while ($holiday_start->lte($holiday_end)) {
                $holiday_dates[] = $holiday_start->format('Y-m-d');
                $holiday_start->addDay();
            }
        }
        
        while ($current->lte($end_date)) {
            $is_weekend = false;
            $is_holiday = in_array($current->format('Y-m-d'), $holiday_dates);
            
            // Check if it's a shift weekend
            if ($shift && !empty($shift->holidays)) {
                $shift_weekends = is_array($shift->holidays) ? $shift->holidays : (json_decode($shift->holidays, true) ?: []);
                $current_day_name = strtolower($current->format('l')); // 'friday', 'saturday', etc.
                $is_weekend = in_array($current_day_name, $shift_weekends);
            } else {
                // Default to Saturday/Sunday if no shift defined
                $is_weekend = $current->isWeekend();
            }
            
            if (!$is_weekend && !$is_holiday) {
                $working_days++;
            }
            
            $current->addDay();
        }
        
        return $working_days;
    }

    private function excludeHolidaysOnly($start_date, $end_date, $business_id)
    {
        $total_days = $start_date->diffInDays($end_date) + 1;
        
        // Get holidays for the period
        $holidays = \Modules\Essentials\Entities\EssentialsHoliday::where('business_id', $business_id)
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('start_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                      ->orWhereBetween('end_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                      ->orWhere(function ($q) use ($start_date, $end_date) {
                          $q->where('start_date', '<=', $start_date->format('Y-m-d'))
                            ->where('end_date', '>=', $end_date->format('Y-m-d'));
                      });
            })
            ->get();
        
        $holiday_days = 0;
        foreach ($holidays as $holiday) {
            $holiday_start = max(\Carbon::parse($holiday->start_date), $start_date);
            $holiday_end = min(\Carbon::parse($holiday->end_date), $end_date);
            
            if ($holiday_start->lte($holiday_end)) {
                $holiday_days += $holiday_start->diffInDays($holiday_end) + 1;
            }
        }
        
        return max(0, $total_days - $holiday_days);
    }

    private function excludeWeekendsOnly($start_date, $end_date, $shift)
    {
        $working_days = 0;
        $current = $start_date->copy();
        
        while ($current->lte($end_date)) {
            $is_weekend = false;
            
            // Check if it's a shift weekend
            if ($shift && !empty($shift->holidays)) {
                $shift_weekends = is_array($shift->holidays) ? $shift->holidays : (json_decode($shift->holidays, true) ?: []);
                $current_day_name = strtolower($current->format('l')); // 'friday', 'saturday', etc.
                $is_weekend = in_array($current_day_name, $shift_weekends);
            } else {
                // Default to Saturday/Sunday if no shift defined
                $is_weekend = $current->isWeekend();
            }
            
            if (!$is_weekend) {
                $working_days++;
            }
            
            $current->addDay();
        }
        
        return $working_days;
    }

    private function getLeavesForPeriod($user_id, $start_date, $end_date, $include_paid_leaves, $include_unpaid_leaves)
    {
        $query = \Modules\Essentials\Entities\EssentialsLeave::where('user_id', $user_id)
            ->where('status', 'approved')
            ->where(function ($q) use ($start_date, $end_date) {
                $q->whereBetween('start_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                  ->orWhereBetween('end_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                  ->orWhere(function ($query) use ($start_date, $end_date) {
                      $query->where('start_date', '<=', $start_date->format('Y-m-d'))
                            ->where('end_date', '>=', $end_date->format('Y-m-d'));
                  });
            });
        
        // Filter by leave type (paid/unpaid) - only get leaves that should be SUBTRACTED from working days
        // CORRECTED LOGIC: 
        // - include_paid_leaves = TRUE means paid leaves should NOT be subtracted (counted as working days)
        // - include_paid_leaves = FALSE means paid leaves SHOULD be subtracted (not counted as working days)
        
        if ($include_paid_leaves && $include_unpaid_leaves) {
            // Both types included in working time = subtract NO leaves
            // Return 0 days to subtract
            return 0;
        } elseif (!$include_paid_leaves || !$include_unpaid_leaves) {
            $query->join('essentials_leave_types', 'essentials_leaves.essentials_leave_type_id', '=', 'essentials_leave_types.id');
            
            if (!$include_paid_leaves && $include_unpaid_leaves) {
                // Only paid leaves should be subtracted (unpaid leaves stay as working days)
                $query->where('essentials_leave_types.is_paid', 1);
            } elseif ($include_paid_leaves && !$include_unpaid_leaves) {
                // Only unpaid leaves should be subtracted (paid leaves stay as working days)
                $query->where('essentials_leave_types.is_paid', 0);
            }
            // If both are false (!include_paid_leaves && !include_unpaid_leaves), get ALL leaves (no additional filter needed)
        }
        
        $leaves = $query->get();
        $leave_days = 0;
        
        foreach ($leaves as $leave) {
            $leave_start = max(\Carbon::parse($leave->start_date), $start_date);
            $leave_end = min(\Carbon::parse($leave->end_date), $end_date);
            
            if ($leave_start->lte($leave_end)) {
                $leave_days += $leave_start->diffInDays($leave_end) + 1;
            }
        }
        
        return $leave_days;
    }

    public function calculateShiftHours($shift_id, $month, $year)
    {
        if (empty($shift_id)) {
            return 0;
        }

        $shift = \Modules\Essentials\Entities\EssentialsShift::find($shift_id);
        if (!$shift || $shift->shift_type !== 'fixed_shift') {
            // For flexible shifts or no shift, return default calculation
            return $this->calculateWorkingDaysForUser(null, $month, $year) * 8;
        }

        $start_date = \Carbon::create($year, $month, 1);
        $end_date = $start_date->copy()->endOfMonth();
        $current = $start_date->copy();
        
        $total_shift_hours = 0;
        $shift_weekends = !empty($shift->weekends) ? json_decode($shift->weekends, true) : [];
        
        while ($current->lte($end_date)) {
            $is_weekend = in_array($current->dayOfWeek, $shift_weekends);
            
            if (!$is_weekend) {
                // Calculate daily shift hours
                $clock_in = \Carbon::parse($shift->start_time);
                $clock_out = \Carbon::parse($shift->end_time);
                
                if ($clock_out->lt($clock_in)) {
                    // Shift crosses midnight
                    $daily_hours = $clock_in->diffInHours($clock_out->addDay());
                } else {
                    $daily_hours = $clock_in->diffInHours($clock_out);
                }
                
                $total_shift_hours += $daily_hours;
            }
            
            $current->addDay();
        }
        
        return $total_shift_hours;
    }

    public function getShiftAwarePayrollValidation($user_id, $month, $year, $actual_hours, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $user = \App\User::find($user_id);
        if (!$user) {
            return ['is_valid' => true];
        }

        $expected_hours = 0;
        if (!empty($user->essentials_shift_id)) {
            $expected_hours = $this->calculateShiftHours($user->essentials_shift_id, $month, $year);
        } else {
            // Fallback to working days calculation
            $working_days = $this->calculateWorkingDaysForUser($user_id, $month, $year);
            $expected_hours = $working_days * 8;
        }

        $variance = $actual_hours - $expected_hours;
        $variance_percentage = $expected_hours > 0 ? abs($variance) / $expected_hours * 100 : 0;

        return [
            'is_valid' => $variance_percentage <= 15, // 15% tolerance
            'expected_hours' => $expected_hours,
            'actual_hours' => $actual_hours,
            'variance' => $variance,
            'variance_percentage' => round($variance_percentage, 1),
            'status' => $variance > 0 ? 'overtime' : ($variance < 0 ? 'undertime' : 'normal')
        ];
    }

    public function getPayrollRecommendations($user_id, $month, $year, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $user = \App\User::find($user_id);
        if (!$user) {
            return [];
        }

        $recommendations = [];
        $settings = $this->getEssentialsSettings($business_id);

        // Check if shift-aware calculation is recommended
        if (!empty($user->essentials_shift_id) && !empty($settings['auto_calculate_hourly_in_payroll'])) {
            $shift_hours = $this->calculateShiftHours($user->essentials_shift_id, $month, $year);
            $actual_hours = $this->getTotalWorkDuration('hour', $user_id, $business_id, 
                \Carbon::create($year, $month, 1)->format('Y-m-d'), 
                \Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d')
            );

            $hourly_rate = $this->calculateHourlyRate($user_id, $user->essentials_salary, $month, $year, $business_id);
            $shift_based_salary = $actual_hours * $hourly_rate;
            $monthly_salary = $user->essentials_salary;

            $recommendations[] = [
                'type' => 'calculation_method',
                'title' => 'Shift-Aware Calculation Available',
                'description' => 'Based on actual attendance and shift schedule',
                'recommended_amount' => $shift_based_salary,
                'standard_amount' => $monthly_salary,
                'difference' => $shift_based_salary - $monthly_salary,
                'hourly_rate' => $hourly_rate,
                'hours_worked' => $actual_hours,
                'scheduled_hours' => $shift_hours
            ];
        }

        // Check for leave adjustments
        $leave_balances = $this->getUserLeaveBalances($user_id, $business_id);
        foreach ($leave_balances as $leave_balance) {
            if ($leave_balance['remaining_count'] < 2 && $leave_balance['leave_type']['is_paid'] == 0) {
                $recommendations[] = [
                    'type' => 'leave_concern',
                    'title' => 'Low Unpaid Leave Balance',
                    'description' => 'Employee has only ' . $leave_balance['remaining_count'] . ' ' . $leave_balance['leave_type']['leave_type'] . ' days remaining',
                    'leave_type' => $leave_balance['leave_type']['leave_type'],
                    'remaining' => $leave_balance['remaining_count']
                ];
            }
        }

        // Check attendance efficiency
        $validation = $this->getShiftAwarePayrollValidation($user_id, $month, $year, $actual_hours ?? 0, $business_id);
        if (!$validation['is_valid']) {
            $recommendations[] = [
                'type' => 'attendance_efficiency',
                'title' => 'Attendance Variance Detected',
                'description' => 'Employee hours vary significantly from scheduled hours (' . $validation['variance_percentage'] . '% variance)',
                'status' => $validation['status'],
                'variance' => $validation['variance'],
                'variance_percentage' => $validation['variance_percentage']
            ];
        }

        return $recommendations;
    }

    public function generatePayrollSummaryReport($payroll_data, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $summary = [
            'total_employees' => count($payroll_data),
            'total_gross_amount' => 0,
            'shift_aware_calculations' => 0,
            'attendance_warnings' => 0,
            'efficiency_stats' => [
                'high_performers' => 0, // >= 95%
                'good_performers' => 0, // 80-95%
                'needs_improvement' => 0 // < 80%
            ],
            'overtime_employees' => 0,
            'undertime_employees' => 0
        ];

        foreach ($payroll_data as $employee_id => $payroll) {
            // Calculate totals
            $basic_salary = ($payroll['essentials_duration'] ?? 1) * ($payroll['essentials_amount_per_unit_duration'] ?? $payroll['essentials_salary']);
            $allowances = array_sum($payroll['allowances']['allowance_amounts'] ?? []);
            $deductions = array_sum($payroll['deductions']['deduction_amounts'] ?? []);
            $gross_amount = $basic_salary + $allowances - $deductions;
            
            $summary['total_gross_amount'] += $gross_amount;

            // Count shift-aware calculations
            if (!empty($payroll['hourly_rate'])) {
                $summary['shift_aware_calculations']++;
            }

            // Count warnings
            if (!empty($payroll['has_attendance_warning'])) {
                $summary['attendance_warnings']++;
            }

            // Categorize efficiency
            if (!empty($payroll['attendance_efficiency'])) {
                if ($payroll['attendance_efficiency'] >= 95) {
                    $summary['efficiency_stats']['high_performers']++;
                } elseif ($payroll['attendance_efficiency'] >= 80) {
                    $summary['efficiency_stats']['good_performers']++;
                } else {
                    $summary['efficiency_stats']['needs_improvement']++;
                }
            }

            // Count overtime/undertime
            if (!empty($payroll['attendance_status'])) {
                if ($payroll['attendance_status'] == 'overtime') {
                    $summary['overtime_employees']++;
                } elseif ($payroll['attendance_status'] == 'undertime') {
                    $summary['undertime_employees']++;
                }
            }
        }

        return $summary;
    }

    public function detectOvertimeFromAttendance($user_id, $date, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $user = \App\User::find($user_id);
        if (!$user) {
            return null;
        }

        // Get attendance for the date
        $attendance = \Modules\Essentials\Entities\EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereDate('clock_in_time', $date)
            ->whereNotNull('clock_out_time')
            ->first();

        if (!$attendance) {
            return null;
        }

        // Calculate actual work duration
        $clock_in = \Carbon::parse($attendance->clock_in_time);
        $clock_out = \Carbon::parse($attendance->clock_out_time);
        $actual_hours = $clock_in->diffInHours($clock_out, false);
        $actual_minutes = $clock_in->diffInMinutes($clock_out) % 60;
        $total_actual_hours = $actual_hours + ($actual_minutes / 60);

        // Get expected hours based on shift
        $expected_hours = 8; // Default
        if (!empty($user->essentials_shift_id)) {
            $shift = \Modules\Essentials\Entities\EssentialsShift::find($user->essentials_shift_id);
            if ($shift && $shift->shift_type === 'fixed_shift') {
                $shift_start = \Carbon::parse($shift->start_time);
                $shift_end = \Carbon::parse($shift->end_time);
                
                if ($shift_end->lt($shift_start)) {
                    $shift_end->addDay();
                }
                
                $expected_hours = $shift_start->diffInHours($shift_end, false);
            }
        }

        // Determine overtime type
        $overtime_type = $this->getOvertimeType($date, $user, $business_id);
        
        // Calculate overtime hours (anything beyond expected hours)
        $overtime_hours = max(0, $total_actual_hours - $expected_hours);
        
        if ($overtime_hours > 0.25) { // Minimum 15 minutes threshold
            return [
                'date' => $date,
                'user_id' => $user_id,
                'attendance_id' => $attendance->id,
                'actual_hours' => round($total_actual_hours, 2),
                'expected_hours' => $expected_hours,
                'overtime_hours' => round($overtime_hours, 2),
                'overtime_type' => $overtime_type,
                'start_time' => $clock_in->addHours($expected_hours)->format('H:i:s'),
                'end_time' => $clock_out->format('H:i:s'),
                'clock_in_time' => $attendance->clock_in_time,
                'clock_out_time' => $attendance->clock_out_time
            ];
        }

        return null;
    }

    public function getOvertimeType($date, $user, $business_id)
    {
        $carbon_date = \Carbon::parse($date);
        
        // Check if it's a holiday
        $holiday = \Modules\Essentials\Entities\EssentialsHoliday::where('business_id', $business_id)
            ->whereDate('start_date', '<=', $carbon_date->format('Y-m-d'))
            ->whereDate('end_date', '>=', $carbon_date->format('Y-m-d'))
            ->first();
            
        if ($holiday) {
            return 'holiday';
        }
        
        // Check if it's a weekend based on user's current active shift
        $current_date = $carbon_date->format('Y-m-d');
        
        // Check for shift assignment through EssentialsUserShift table first
        $user_shift = \Modules\Essentials\Entities\EssentialsUserShift::where('user_id', $user->id)
            ->where('start_date', '<=', $current_date)
            ->where(function($q) use ($current_date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $current_date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
            
        if ($user_shift) {
            $shift = \Modules\Essentials\Entities\Shift::find($user_shift->essentials_shift_id);
            if ($shift && !empty($shift->holidays)) {
                $shift_weekends = is_array($shift->holidays) ? $shift->holidays : (json_decode($shift->holidays, true) ?: []);
                $current_day_name = strtolower($carbon_date->format('l')); // 'friday', 'saturday', etc.
                if (in_array($current_day_name, $shift_weekends)) {
                    return 'weekend';
                }
            }
        } 
        // Fallback to direct user shift assignment (legacy)
        elseif (!empty($user->essentials_shift_id)) {
            $shift = \Modules\Essentials\Entities\Shift::find($user->essentials_shift_id);
            if ($shift && !empty($shift->holidays)) {
                $shift_weekends = is_array($shift->holidays) ? $shift->holidays : (json_decode($shift->holidays, true) ?: []);
                $current_day_name = strtolower($carbon_date->format('l')); // 'friday', 'saturday', etc.
                if (in_array($current_day_name, $shift_weekends)) {
                    return 'weekend';
                }
            }
        } else {
            // Default weekend check (Saturday/Sunday)
            if ($carbon_date->isWeekend()) {
                return 'weekend';
            }
        }
        
        return 'workday';
    }

    public function getOvertimeSettings($business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $settings = $this->getEssentialsSettings($business_id);
        
        return [
            'daily_threshold' => $settings['overtime_daily_threshold'] ?? 8, // hours
            'workday_multiplier' => $settings['overtime_workday_multiplier'] ?? 1.5,
            'weekend_multiplier' => $settings['overtime_weekend_multiplier'] ?? 2.0,
            'holiday_multiplier' => $settings['overtime_holiday_multiplier'] ?? 2.5,
            'approval_required' => !empty($settings['overtime_approval_required']),
            'auto_detect' => !empty($settings['overtime_auto_detect']),
            'minimum_minutes' => $settings['overtime_minimum_minutes'] ?? 15,
            'maximum_hours' => $settings['overtime_maximum_hours'] ?? 24
        ];
    }

    public function calculateOvertimeAmount($hours, $base_hourly_rate, $multiplier)
    {
        return $hours * $base_hourly_rate * $multiplier;
    }

    public function createOvertimeRequestFromDetection($detection_data, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $settings = $this->getOvertimeSettings($business_id);
        $user = \App\User::find($detection_data['user_id']);
        
        if (!$user) {
            return null;
        }

        // Calculate hourly rate
        $hourly_rate = $this->calculateHourlyRate(
            $user->id, 
            $user->essentials_salary, 
            \Carbon::parse($detection_data['date'])->month,
            \Carbon::parse($detection_data['date'])->year,
            $business_id
        );

        // Get multiplier based on overtime type
        $multiplier_map = [
            'workday' => $settings['workday_multiplier'],
            'weekend' => $settings['weekend_multiplier'],
            'holiday' => $settings['holiday_multiplier']
        ];
        $multiplier = $multiplier_map[$detection_data['overtime_type']] ?? 1.5;

        // Calculate total amount
        $total_amount = $this->calculateOvertimeAmount(
            $detection_data['overtime_hours'], 
            $hourly_rate, 
            $multiplier
        );

        return \Modules\Essentials\Entities\EssentialsOvertimeRequest::create([
            'business_id' => $business_id,
            'user_id' => $detection_data['user_id'],
            'overtime_date' => $detection_data['date'],
            'start_time' => $detection_data['start_time'],
            'end_time' => $detection_data['end_time'],
            'hours_requested' => $detection_data['overtime_hours'],
            'overtime_type' => $detection_data['overtime_type'],
            'reason' => 'Auto-detected from attendance',
            'description' => 'Overtime detected based on attendance: worked ' . $detection_data['actual_hours'] . ' hours, expected ' . $detection_data['expected_hours'] . ' hours',
            'status' => $settings['approval_required'] ? 'pending' : 'approved',
            'approved_hours' => $settings['approval_required'] ? null : $detection_data['overtime_hours'],
            'multiplier_rate' => $multiplier,
            'hourly_rate' => $hourly_rate,
            'total_amount' => $settings['approval_required'] ? null : $total_amount,
            'approved_by' => $settings['approval_required'] ? null : null,
            'approved_at' => $settings['approval_required'] ? null : now()
        ]);
    }

    public function processOvertimeDetectionForDate($date, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        $settings = $this->getOvertimeSettings($business_id);
        
        if (!$settings['auto_detect']) {
            return [];
        }

        // Get all users for the business
        $users = \App\User::where('business_id', $business_id)->user()->get();
        $overtime_requests = [];

        foreach ($users as $user) {
            // Check if overtime already exists for this date
            $existing = \Modules\Essentials\Entities\EssentialsOvertimeRequest::where('business_id', $business_id)
                ->where('user_id', $user->id)
                ->whereDate('overtime_date', $date)
                ->exists();

            if ($existing) {
                continue; // Skip if already processed
            }

            $detection = $this->detectOvertimeFromAttendance($user->id, $date, $business_id);
            
            if ($detection) {
                $overtime_request = $this->createOvertimeRequestFromDetection($detection, $business_id);
                if ($overtime_request) {
                    $overtime_requests[] = $overtime_request;
                }
            }
        }

        return $overtime_requests;
    }

    /**
     * Calculate absent day deductions for an employee
     */
    public function calculateAbsentDayDeductions($user_id, $month, $year, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }
        
        $settings = $this->getEssentialsSettings($business_id);
        
        // Check if absent day deductions are enabled
        if (empty($settings['enable_absent_day_deductions'])) {
            return 0;
        }
        
        $start_date = \Carbon::create($year, $month, 1);
        $end_date = $start_date->copy()->endOfMonth();
        
        // Get hourly rate for the user
        $user = \App\User::find($user_id);
        $hourly_rate = $this->calculateHourlyRate($user_id, $user->essentials_salary, $month, $year, $business_id);
        
        if ($hourly_rate <= 0) {
            return 0;
        }
        
        // Get penalty multiplier
        $penalty_multiplier = $settings['absent_day_penalty_multiplier'] ?? 1.0;
        
        // Get shift hours per day
        $shift_hours_per_day = $this->getShiftHoursPerDay($user_id, $start_date->format('Y-m-d'));
        
        // Get approved paid leaves to exclude from deductions
        $paid_leaves = $this->getApprovedPaidLeaves($user_id, $start_date, $end_date, $business_id);
        
        // Get scheduled working days
        $scheduled_working_days = $this->getScheduledWorkingDaysForMonth($user_id, $month, $year, $business_id);
        
        // Get actual attendance days
        $actual_attendance_days = $this->getTotalDaysWorkedForGivenDateOfAnEmployee($business_id, $user_id, $start_date, $end_date);
        
        // Calculate absent days (excluding paid leaves)
        $total_absent_days = max(0, $scheduled_working_days - $actual_attendance_days - $paid_leaves);
        
        // Calculate deduction amount
        $deduction_amount = $total_absent_days * $shift_hours_per_day * $hourly_rate * $penalty_multiplier;
        
        return [
            'total_deduction' => $deduction_amount,
            'absent_days' => $total_absent_days,
            'shift_hours_per_day' => $shift_hours_per_day,
            'hourly_rate' => $hourly_rate,
            'penalty_multiplier' => $penalty_multiplier,
            'paid_leaves_excluded' => $paid_leaves
        ];
    }

    /**
     * Calculate late penalty deductions for an employee
     */
    public function calculateLatePenaltyDeductions($user_id, $month, $year, $business_id = null)
    {
        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }
        
        $settings = $this->getEssentialsSettings($business_id);
        
        // Check if late penalty deductions are enabled
        if (empty($settings['enable_late_penalty_deductions'])) {
            return 0;
        }
        
        $start_date = \Carbon::create($year, $month, 1);
        $end_date = $start_date->copy()->endOfMonth();
        
        // Get hourly rate for the user
        $user = \App\User::find($user_id);
        $hourly_rate = $this->calculateHourlyRate($user_id, $user->essentials_salary, $month, $year, $business_id);
        
        if ($hourly_rate <= 0) {
            return 0;
        }
        
        // Get penalty multiplier and grace period
        $penalty_multiplier = $settings['late_penalty_multiplier'] ?? 1.0;
        $grace_after_checkin = $settings['grace_after_checkin'] ?? 15; // Default 15 minutes
        
        // Get late minutes for the period
        $total_late_minutes = $this->getTotalLateMinutes($user_id, $start_date, $end_date, $grace_after_checkin, $business_id);
        
        if ($total_late_minutes <= 0) {
            return [
                'total_deduction' => 0,
                'total_late_minutes' => 0,
                'late_hours' => 0,
                'hourly_rate' => $hourly_rate,
                'penalty_multiplier' => $penalty_multiplier,
                'grace_period_minutes' => $grace_after_checkin
            ];
        }
        
        // Convert minutes to hours for calculation
        $late_hours = $total_late_minutes / 60;
        
        // Calculate deduction amount
        $deduction_amount = $late_hours * $hourly_rate * $penalty_multiplier;
        
        return [
            'total_deduction' => $deduction_amount,
            'total_late_minutes' => $total_late_minutes,
            'late_hours' => $late_hours,
            'hourly_rate' => $hourly_rate,
            'penalty_multiplier' => $penalty_multiplier,
            'grace_period_minutes' => $grace_after_checkin
        ];
    }

    /**
     * Get shift hours per day for a user
     */
    private function getShiftHoursPerDay($user_id, $current_date)
    {
        $default_hours = 8; // Default fallback
        
        $user = \App\User::find($user_id);
        
        // Check for shift assignment through EssentialsUserShift table first
        $user_shift = \Modules\Essentials\Entities\EssentialsUserShift::where('user_id', $user_id)
            ->where('start_date', '<=', $current_date)
            ->where(function($q) use ($current_date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $current_date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
            
        if ($user_shift) {
            $shift = \Modules\Essentials\Entities\Shift::find($user_shift->essentials_shift_id);
            if ($shift && $shift->type === 'fixed_shift') {
                $start = \Carbon::parse($shift->start_time);
                $end = \Carbon::parse($shift->end_time);
                if ($end->lt($start)) {
                    $end->addDay(); // Handle shifts crossing midnight
                }
                $shift_hours = $start->diffInHours($end, false);
                if ($shift_hours > 0) {
                    return $shift_hours;
                }
            }
        } 
        // Fallback to direct user shift assignment (legacy)
        elseif (!empty($user->essentials_shift_id)) {
            $shift = \Modules\Essentials\Entities\Shift::find($user->essentials_shift_id);
            if ($shift && $shift->type === 'fixed_shift') {
                $start = \Carbon::parse($shift->start_time);
                $end = \Carbon::parse($shift->end_time);
                if ($end->lt($start)) {
                    $end->addDay(); // Handle shifts crossing midnight
                }
                $shift_hours = $start->diffInHours($end, false);
                if ($shift_hours > 0) {
                    return $shift_hours;
                }
            }
        }
        
        return $default_hours;
    }

    /**
     * Get approved paid leaves for a period
     */
    private function getApprovedPaidLeaves($user_id, $start_date, $end_date, $business_id)
    {
        $leaves = \Modules\Essentials\Entities\EssentialsLeave::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('status', 'approved')
            ->where(function($q) use ($start_date, $end_date) {
                $q->whereBetween('start_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                  ->orWhereBetween('end_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                  ->orWhere(function($q2) use ($start_date, $end_date) {
                      $q2->where('start_date', '<=', $start_date->format('Y-m-d'))
                         ->where('end_date', '>=', $end_date->format('Y-m-d'));
                  });
            })
            ->with('leave_type')
            ->get();

        $paid_leave_days = 0;
        foreach ($leaves as $leave) {
            // Only count paid leaves
            if (!empty($leave->leave_type) && !empty($leave->leave_type->is_paid)) {
                $leave_start = max(\Carbon::parse($leave->start_date), $start_date);
                $leave_end = min(\Carbon::parse($leave->end_date), $end_date);
                $days = $leave_start->diffInDays($leave_end) + 1;
                $paid_leave_days += $days;
            }
        }

        return $paid_leave_days;
    }

    /**
     * Get scheduled working days for a month
     */
    private function getScheduledWorkingDaysForMonth($user_id, $month, $year, $business_id)
    {
        $settings = $this->getEssentialsSettings($business_id);
        $method = $settings['hourly_rate_calculation_method'] ?? 'exclude_shift_weekends_and_holidays';
        
        return $this->calculateWorkingDaysForUser($user_id, $month, $year, $method, false, false);
    }

    /**
     * Get total late minutes for a period beyond grace period
     */
    private function getTotalLateMinutes($user_id, $start_date, $end_date, $grace_minutes, $business_id)
    {
        $attendances = \Modules\Essentials\Entities\EssentialsAttendance::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->whereBetween('clock_in_time', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
            ->whereNotNull('clock_in_time')
            ->get();

        $total_late_minutes = 0;
        $user = \App\User::find($user_id);

        foreach ($attendances as $attendance) {
            $clock_in = \Carbon::parse($attendance->clock_in_time);
            $attendance_date = $clock_in->format('Y-m-d');
            
            // Get expected shift start time
            $expected_start_time = $this->getExpectedShiftStartTime($user_id, $attendance_date);
            
            if ($expected_start_time) {
                $expected_start = \Carbon::parse($attendance_date . ' ' . $expected_start_time);
                
                // Add grace period
                $grace_deadline = $expected_start->copy()->addMinutes($grace_minutes);
                
                // Calculate late minutes only if clock in is after grace deadline
                if ($clock_in->gt($grace_deadline)) {
                    $late_minutes = $grace_deadline->diffInMinutes($clock_in);
                    $total_late_minutes += $late_minutes;
                }
            }
        }

        return $total_late_minutes;
    }

    /**
     * Get expected shift start time for a user on a specific date
     */
    private function getExpectedShiftStartTime($user_id, $date)
    {
        $user = \App\User::find($user_id);
        
        // Check for shift assignment through EssentialsUserShift table first
        $user_shift = \Modules\Essentials\Entities\EssentialsUserShift::where('user_id', $user_id)
            ->where('start_date', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->orderBy('start_date', 'desc')
            ->first();
            
        if ($user_shift) {
            $shift = \Modules\Essentials\Entities\Shift::find($user_shift->essentials_shift_id);
            if ($shift && $shift->type === 'fixed_shift') {
                return $shift->start_time;
            }
        } 
        // Fallback to direct user shift assignment (legacy)
        elseif (!empty($user->essentials_shift_id)) {
            $shift = \Modules\Essentials\Entities\Shift::find($user->essentials_shift_id);
            if ($shift && $shift->type === 'fixed_shift') {
                return $shift->start_time;
            }
        }
        
        return null;
    }

    /**
     * Determine overtime type based on date
     */
    public function determineOvertimeType($date)
    {
        $date = \Carbon\Carbon::parse($date);
        
        // Check if it's a weekend (Saturday = 6, Sunday = 0)
        if ($date->dayOfWeek == 0 || $date->dayOfWeek == 6) {
            return 'weekend';
        }
        
        // Check if it's a public holiday
        // This would require checking against a holidays table
        // For now, we'll return 'workday' for all non-weekend days
        // You can enhance this by checking against business holidays
        
        return 'workday';
    }

    /**
     * Get overtime multiplier based on type
     */
    public function getOvertimeMultiplier($overtime_type)
    {
        $multipliers = [
            'workday' => 1.5,
            'weekend' => 2.0,
            'holiday' => 2.5
        ];

        return $multipliers[$overtime_type] ?? 1.5;
    }
}
