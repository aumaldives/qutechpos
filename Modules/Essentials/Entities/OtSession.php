<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;

class OtSession extends Model
{
    protected $table = 'ot_sessions';

    protected $guarded = ['id'];

    protected $dates = ['start_time', 'end_time', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    /**
     * Get the duration of the OT session in hours
     */
    public function getDurationInHours()
    {
        if ($this->end_time) {
            return $this->start_time->diffInMinutes($this->end_time, true) / 60;
        }
        
        return $this->start_time->diffInMinutes(now(), true) / 60;
    }

    /**
     * Get the duration of the OT session in minutes
     */
    public function getDurationInMinutes()
    {
        if ($this->end_time) {
            return $this->start_time->diffInMinutes($this->end_time, true);
        }
        
        return $this->start_time->diffInMinutes(now(), true);
    }

    /**
     * Check if this session starts outside normal work hours
     * Only sessions that START outside work hours qualify for overtime
     */
    public function isOutsideWorkHours()
    {
        // Work hours: 9 AM - 6 PM (can be made configurable later)
        $work_start_hour = 9;  // 9 AM
        $work_end_hour = 18;   // 6 PM
        
        $start_hour = $this->start_time->hour;
        
        // Session starts before work hours (early morning overtime)
        if ($start_hour < $work_start_hour) {
            return true;
        }
        
        // Session starts after work hours (evening/night overtime)
        if ($start_hour >= $work_end_hour) {
            return true;
        }
        
        return false;
    }

    /**
     * Get active OT session for a user
     */
    public static function getActiveSession($user_id, $business_id)
    {
        $session = static::where('user_id', $user_id)
            ->where('business_id', $business_id)
            ->where('status', 'active')
            ->first();
            
        // Check for auto OT out if session exists
        if ($session) {
            $session->checkAutoOtOut();
        }
            
        return $session;
    }
    
    /**
     * Check if session should be auto OT out based on maximum hours setting
     */
    public function checkAutoOtOut()
    {
        $essentials_util = app(\Modules\Essentials\Utils\EssentialsUtil::class);
        $settings = $essentials_util->getEssentialsSettings();
        $max_hours = $settings['overtime_maximum_hours'] ?? 24;
        
        // Check if session has exceeded maximum hours
        if ($this->getDurationInHours() >= $max_hours) {
            $this->update([
                'end_time' => $this->start_time->copy()->addHours($max_hours),
                'end_note' => 'Auto OT out after ' . $max_hours . ' hours maximum',
                'status' => 'completed'
            ]);
            
            // Create draft overtime request if conditions are met
            $attendance_controller = app(\Modules\Essentials\Http\Controllers\AttendanceController::class);
            $reflection = new \ReflectionClass($attendance_controller);
            $method = $reflection->getMethod('createDraftOvertimeRequest');
            $method->setAccessible(true);
            $method->invoke($attendance_controller, $this);
        }
    }
}