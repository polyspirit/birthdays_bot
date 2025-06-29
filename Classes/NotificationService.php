<?php

namespace Classes;

class NotificationService
{
    private Database $database;
    private TelegramBot $telegramBot;

    public function __construct(Database $database, TelegramBot $telegramBot)
    {
        $this->database = $database;
        $this->telegramBot = $telegramBot;
    }

    public function sendDailyBirthdayNotifications(): void
    {
        $birthdays = $this->database->getTodaysBirthdays();

        foreach ($birthdays as $birthday) {
            $text = "🎉 Сегодня день рождения у {$birthday['name']}!\n\nПоздравьте его/её!";
            $this->telegramBot->sendMessage($birthday['chat_id'], $text);
        }
    }
}
