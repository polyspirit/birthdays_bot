<?php

namespace Classes;

class BirthdayManager
{
    private Database $database;
    private TelegramBot $telegramBot;

    public function __construct(Database $database, TelegramBot $telegramBot)
    {
        $this->database = $database;
        $this->telegramBot = $telegramBot;
    }

    public function addBirthday(int $userId, int $chatId, string $name, string $birthDate): void
    {
        $this->database->addBirthday($userId, $name, $birthDate);
        $this->telegramBot->sendMessage($chatId, "✅ {$name} добавлен(а)!");
    }

    public function listBirthdays(int $userId, int $chatId): void
    {
        $birthdays = $this->database->getUserBirthdays($userId);

        if (empty($birthdays)) {
            $this->telegramBot->sendMessage($chatId, "У вас пока нет добавленных именинников.");
            return;
        }

        $keyboard = [];
        $message = "🎂 Ваши именинники:\n";

        foreach ($birthdays as $birthday) {
            $message .= "{$birthday['name']} — " . date('d.m', strtotime($birthday['birth_date'])) . "\n";
            $keyboard[] = [[
                'text' => "❌ Удалить {$birthday['name']}",
                'callback_data' => "delete_{$birthday['id']}"
            ]];
        }

        $this->telegramBot->sendMessage($chatId, $message, ['inline_keyboard' => $keyboard]);
    }

    public function deleteBirthday(int $id, int $userId, int $chatId, string $callbackQueryId): void
    {
        $this->database->deleteBirthday($id, $userId);
        $this->telegramBot->answerCallbackQuery($callbackQueryId, 'Удалено ✅');
        $this->telegramBot->sendMessage($chatId, "Именинник удалён.");
    }

    public function validateDate(string $date): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date);
    }
}
