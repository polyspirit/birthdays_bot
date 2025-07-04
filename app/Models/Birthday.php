<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Birthday extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'telegram_username',
        'birthday_chat_id',
        'birth_date',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'birthday_chat_id' => 'integer',
        'birth_date' => 'date',
    ];

    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class, 'user_id', 'user_id');
    }
}
