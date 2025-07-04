<?php

namespace App\Services;

use App\Models\Birthday;
use App\Models\TelegramUser;

class BirthdayService
{
    private TelegramBotService $telegramBot;

    public function __construct(TelegramBotService $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }

    public function addBirthday(int $userId, int $chatId, string $name, string $telegramUsername, ?int $birthdayChatId, string $birthDate): void
    {
        Birthday::create([
            'user_id' => $userId,
            'name' => $name,
            'telegram_username' => $telegramUsername,
            'birthday_chat_id' => $birthdayChatId,
            'birth_date' => $birthDate,
        ]);

        $this->telegramBot->sendMessage($chatId, "✅ {$name} (@{$telegramUsername}) добавлен(а)!");
    }

    public function listBirthdays(int $userId, int $chatId): void
    {
        $birthdays = Birthday::where('user_id', $userId)->get();

        if ($birthdays->isEmpty()) {
            $this->telegramBot->sendMessage($chatId, "У вас пока нет добавленных именинников.");
            return;
        }

        $keyboard = [];
        $message = "🎂 Ваши именинники:\n";

        foreach ($birthdays as $birthday) {
            $username = $birthday->telegram_username ? "@{$birthday->telegram_username}" : "без username";
            $message .= "{$birthday->name} ({$username}) — " . $birthday->birth_date->format('d.m') . "\n";
            $keyboard[] = [[
                'text' => "❌ Удалить {$birthday->name}",
                'callback_data' => "delete_{$birthday->id}"
            ]];
        }

        $this->telegramBot->sendMessage($chatId, $message, ['inline_keyboard' => $keyboard]);
    }

    public function deleteBirthday(int $id, int $userId, int $chatId, string $callbackQueryId): void
    {
        Birthday::where('id', $id)->where('user_id', $userId)->delete();
        $this->telegramBot->answerCallbackQuery($callbackQueryId, 'Удалено ✅');
        $this->telegramBot->sendMessage($chatId, "Именинник удалён.");
    }

    public function validateDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
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
