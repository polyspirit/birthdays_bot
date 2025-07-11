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

    public function addBirthday(
        int $userId,
        int $chatId,
        string $name,
        ?string $telegramUsername,
        ?int $birthdayChatId,
        string $birthDate
    ): void {
        Birthday::create([
            'user_id' => $userId,
            'name' => $name,
            'telegram_username' => $telegramUsername,
            'birthday_chat_id' => $birthdayChatId,
            'birth_date' => $birthDate,
        ]);

        $usernameText = $telegramUsername ? ' (@' . $telegramUsername . ')' : '';
        $this->telegramBot->sendMessage($chatId, '✅ ' . $name . $usernameText . ' добавлен(а)!');
    }

    public function listBirthdays(int $userId, int $chatId): void
    {
        $birthdays = Birthday::where('user_id', $userId)->get();

        if ($birthdays->isEmpty()) {
            $this->telegramBot->sendMessage($chatId, 'У вас пока нет добавленных именинников.');
            return;
        }

        $keyboard = [];
        $message = '🎂 Ваши именинники:' . PHP_EOL;

        foreach ($birthdays as $birthday) {
            $username = $birthday->telegram_username ? "@{$birthday->telegram_username}" : "без username";

            // Format date: show only day and month, regardless of year
            $formattedDate = $birthday->birth_date->format('d.m');
            $message .= $birthday->name . ' (' . $username . ') — ' . $formattedDate . PHP_EOL;

            $keyboard[] = [[
                'text' => '❌ Удалить ' . $birthday->name,
                'callback_data' => 'delete_' . $birthday->id
            ]];
        }

        $this->telegramBot->sendMessage($chatId, $message, ['inline_keyboard' => $keyboard]);
    }

    public function deleteBirthday(int $id, int $userId, int $chatId, string $callbackQueryId): void
    {
        Birthday::where('id', $id)->where('user_id', $userId)->delete();
        $this->telegramBot->answerCallbackQuery($callbackQueryId, 'Удалено ✅');
        $this->telegramBot->sendMessage($chatId, 'Именинник удалён.');
    }

    public function showUpcomingBirthdays(int $userId, int $chatId): void
    {
        $upcomingBirthdays = $this->getUpcomingBirthdays($userId, 3);

        if (empty($upcomingBirthdays)) {
            $this->telegramBot->sendMessage($chatId, 'У вас пока нет добавленных именинников.');
            return;
        }

        $message = '📅 Ближайшие дни рождения:' . PHP_EOL . PHP_EOL;

        foreach ($upcomingBirthdays as $birthday) {
            $username = $birthday['telegram_username'] ? "@{$birthday['telegram_username']}" : "без username";
            $daysText = $this->getDaysText($birthday['days_until']);

            $message .= "🎂 {$birthday['name']} ({$username})" . PHP_EOL;
            $message .= "📅 {$birthday['formatted_date']} — {$daysText}" . PHP_EOL . PHP_EOL;
        }

        $this->telegramBot->sendMessage($chatId, $message);
    }

    private function getDaysText(int $days): string
    {
        if ($days === 0) {
            return 'сегодня! 🎉';
        }

        if ($days === 1) {
            return 'завтра! 🎉';
        }

        $lastDigit = $days % 10;
        $lastTwoDigits = $days % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
            return "через {$days} дней";
        }

        switch ($lastDigit) {
            case 1:
                return "через {$days} день";
            case 2:
            case 3:
            case 4:
                return "через {$days} дня";
            default:
                return "через {$days} дней";
        }
    }

    public function validateDate(string $date): bool
    {
        // Check for YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4));
        }

        // Check for MM-DD format
        if (preg_match('/^\d{2}-\d{2}$/', $date)) {
            // Use 9996 as dummy year for validation (leap year to handle February 29)
            return checkdate((int)substr($date, 0, 2), (int)substr($date, 3, 2), 9996);
        }

        return false;
    }

    /**
     * Normalize date to YYYY-MM-DD format
     * If only MM-DD is provided, use 9996 as year
     */
    public function normalizeDate(string $date): string
    {
        // If already in YYYY-MM-DD format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // If in MM-DD format, add 9996 as year
        if (preg_match('/^\d{2}-\d{2}$/', $date)) {
            return '9996-' . $date;
        }

        // If format is invalid, return original (will be caught by validation)
        return $date;
    }

    public function getTodaysBirthdays(): array
    {
        $today = now()->format('m-d');

        return Birthday::join('telegram_users', 'birthdays.user_id', '=', 'telegram_users.user_id')
            ->whereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$today])
            ->select(
                'birthdays.name',
                'birthdays.telegram_username',
                'birthdays.birthday_chat_id',
                'birthdays.birth_date',
                'telegram_users.chat_id'
            )
            ->get()
            ->toArray();
    }

    public function getUpcomingBirthdays(int $userId, int $limit = 3): array
    {
        $today = now();
        $currentMonth = $today->month;
        $currentDay = $today->day;

        return Birthday::where('user_id', $userId)
            ->get()
            ->map(function ($birthday) use ($today) {
                $birthMonth = $birthday->birth_date->month;
                $birthDay = $birthday->birth_date->day;

                // Calculate days until next birthday
                $nextBirthday = $today->copy()->setDate($today->year, $birthMonth, $birthDay);

                // If birthday has passed this year, calculate for next year
                if ($nextBirthday->lt($today)) {
                    $nextBirthday->addYear();
                }

                $daysUntil = $today->diffInDays($nextBirthday, false);

                return [
                    'name' => $birthday->name,
                    'telegram_username' => $birthday->telegram_username,
                    'birth_date' => $birthday->birth_date,
                    'days_until' => $daysUntil,
                    'next_birthday' => $nextBirthday,
                    'formatted_date' => $birthday->birth_date->format('d.m')
                ];
            })
            ->sortBy('days_until')
            ->take($limit)
            ->values()
            ->toArray();
    }
}
