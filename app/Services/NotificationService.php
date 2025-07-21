<?php

namespace App\Services;

use App\Models\Birthday;
use Carbon\Carbon;

class NotificationService
{
    private TelegramBotService $telegramBot;

    public function __construct(TelegramBotService $telegramBot)
    {
        $this->telegramBot = $telegramBot;
    }

    public function sendDailyBirthdayNotifications(): void
    {
        // Send notifications for birthdays tomorrow (reminder)
        $tomorrowBirthdays = $this->getTomorrowBirthdays();
        foreach ($tomorrowBirthdays as $birthday) {
            $ageText = $this->getAgeText($birthday['birth_date'] ?? null);
            $usernameText = $birthday['telegram_username'] ? ' @' . urlencode($birthday['telegram_username']) : '';
            $text = '📅 Завтра день рождения у ' . $birthday['name'] . $usernameText . '!'
                . $ageText
                . PHP_EOL . PHP_EOL . 'Не забудьте поздравить!';
            $this->telegramBot->sendMessage($birthday['chat_id'], $text);
        }

        // Send notifications for birthdays today
        $todayBirthdays = $this->getTodaysBirthdays();
        foreach ($todayBirthdays as $birthday) {
            $ageText = $this->getAgeText($birthday['birth_date'] ?? null);
            $usernameText = $birthday['telegram_username'] ? ' @' . urlencode($birthday['telegram_username']) : '';
            $text = '🎉 Сегодня день рождения у ' . $birthday['name'] . $usernameText . '!'
                . $ageText
                . PHP_EOL . PHP_EOL . 'Поздравьте его/её!';

            // Only show greeting buttons if username is available
            if ($birthday['telegram_username']) {
                $keyboard = [
                    [
                        [
                            'text' => '📨 Простое поздравление',
                            'callback_data' => 'greet_simple_' . $birthday['user_id']
                        ],
                        [
                            'text' => '🤖 ИИ поздравление',
                            'callback_data' => 'greet_ai_' . $birthday['user_id']
                        ]
                    ]
                ];
                $this->telegramBot->sendMessage($birthday['chat_id'], $text, ['inline_keyboard' => $keyboard]);
            } else {
                $this->telegramBot->sendMessage($birthday['chat_id'], $text);
            }
        }
    }

    /**
     * Calculate age and return age text if birth year is not 9996
     */
    private function getAgeText(?string $birthDate): string
    {
        if ($birthDate === null) {
            return '';
        }

        $birthYear = Carbon::parse($birthDate)->year;

        // If year is 9996, don't show age
        if ($birthYear === 9996) {
            return '';
        }

        $age = Carbon::now()->year - $birthYear;
        $ageSuffix = $this->getAgeSuffix($age);

        return PHP_EOL . 'Исполняется: ' . $age . ' ' . $ageSuffix;
    }

    /**
     * Get proper Russian suffix for age
     */
    private function getAgeSuffix(int $age): string
    {
        $lastDigit = $age % 10;
        $lastTwoDigits = $age % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
            return 'лет';
        }

        switch ($lastDigit) {
            case 1:
                return 'год';
            case 2:
            case 3:
            case 4:
                return 'года';
            default:
                return 'лет';
        }
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
                'telegram_users.chat_id',
                'birthdays.user_id'
            )
            ->get()
            ->toArray();
    }

    public function getTomorrowBirthdays(): array
    {
        $tomorrow = now()->addDay()->format('m-d');

        return Birthday::join('telegram_users', 'birthdays.user_id', '=', 'telegram_users.user_id')
            ->whereRaw("DATE_FORMAT(birth_date, '%m-%d') = ?", [$tomorrow])
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
}
