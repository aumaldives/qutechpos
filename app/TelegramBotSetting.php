<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TelegramBotSetting extends Model
{
    protected $fillable = [
        'business_id',
        'bot_token',
        'authorized_chat_ids',
        'is_active',
        'settings'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get authorized chat IDs as array
     */
    public function getAuthorizedChatIdsArrayAttribute()
    {
        if (empty($this->authorized_chat_ids)) {
            return [];
        }
        
        return array_filter(
            array_map('trim', explode(',', $this->authorized_chat_ids))
        );
    }

    /**
     * Set authorized chat IDs from array
     */
    public function setAuthorizedChatIdsFromArray($chat_ids)
    {
        $this->authorized_chat_ids = implode(',', array_filter($chat_ids));
    }

    /**
     * Check if chat ID is authorized
     */
    public function isChatAuthorized($chat_id)
    {
        return in_array(strval($chat_id), $this->getAuthorizedChatIdsArrayAttribute());
    }
}