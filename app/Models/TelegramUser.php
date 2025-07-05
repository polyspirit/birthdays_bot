<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'user_id',
        'chat_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'chat_id' => 'integer',
    ];

    public function birthdays()
    {
        return $this->hasMany(Birthday::class, 'user_id', 'user_id');
    }

    public function state()
    {
        return $this->hasOne(UserState::class, 'user_id', 'user_id');
    }
}
