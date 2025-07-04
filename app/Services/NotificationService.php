<?php

namespace App\Services;

use App\Models\Birthday;

class NotificationService
{
    private TelegramBotService $telegramBot;

    public function __construct(TelegramBotService $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }

    public function sendDailyBirthdayNotifications(): void
    {
        $birthdays = $this->getTodaysBirthdays();

        foreach ($birthdays as $birthday) {
            $text = "🎉 Сегодня день рождения у {$birthday['name']}!\n\nПоздравьте его/её!";
            $keyboard = [
                [
                    ['text' => '📨 Отправить поздравление', 'callback_data' => "greet_" . urlencode($birthday['name']) . "_" . urlencode($birthday['telegram_username'])]
                ]
            ];
            $this->telegramBot->sendMessage($birthday['chat_id'], $text, ['inline_keyboard' => $keyboard]);
        }
    }

    public function getTodaysBirthdays(): array
    {
        $today = now()->format('m-d');

        return Birthday::join('telegram_users', 'birthdays.user_id', '=', 'telegram_users.user_id')
            ->whereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$today])
            ->select('birthdays.name', 'birthdays.telegram_username', 'birthdays.birthday_chat_id', 'birthdays.birth_date', 'telegram_users.chat_id')
            ->get()
            ->toArray();
    }
}
