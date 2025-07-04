<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserState extends Model
{
    protected $fillable = [
        'user_id',
        'state',
        'temp_name',
        'temp_username',
        'temp_birthday_chat_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'temp_birthday_chat_id' => 'integer',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class, 'user_id', 'user_id');
    }
}
