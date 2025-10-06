<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TelegramBotSession extends Model
{
    protected $fillable = [
        'business_id',
        'chat_id',
        'current_action',
        'session_data',
        'expires_at'
    ];

    protected $casts = [
        'session_data' => 'array',
        'expires_at' => 'datetime'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Create or update session
     */
    public static function createOrUpdate($business_id, $chat_id, $action, $data = [])
    {
        return static::updateOrCreate(
            [
                'business_id' => $business_id,
                'chat_id' => $chat_id
            ],
            [
                'current_action' => $action,
                'session_data' => $data,
                'expires_at' => Carbon::now()->addHour()
            ]
        );
    }

    /**
     * Get session data value
     */
    public function getSessionValue($key, $default = null)
    {
        return $this->session_data[$key] ?? $default;
    }

    /**
     * Set session data value
     */
    public function setSessionValue($key, $value)
    {
        $data = $this->session_data ?? [];
        $data[$key] = $value;
        $this->session_data = $data;
        $this->expires_at = Carbon::now()->addHour();
        $this->save();
    }

    /**
     * Check if session is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Clear session
     */
    public function clearSession()
    {
        $this->current_action = null;
        $this->session_data = [];
        $this->save();
    }
}