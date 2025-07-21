<?php

namespace App\Services;

use App\Models\TelegramUser;
use App\Models\Birthday;
use App\Enums\GreetingStyleEnum;
use App\Services\ZodiacService;

class WebhookHandlerService
{
    private TelegramBotService $telegramBot;
    private BirthdayService $birthdayService;
    private UserStateService $stateService;

    public function __construct(
        TelegramBotService $telegramBot,
        BirthdayService $birthdayService,
        UserStateService $stateService
    ) {
        $this->telegramBot = $telegramBot;
        $this->birthdayService = $birthdayService;
        $this->stateService = $stateService;
    }

    public function getTelegramBot(): TelegramBotService
    {
        return $this->telegramBot;
    }

    public function handleUpdate($update): void
    {
        if ($update->isType('message')) {
            $this->handleMessage($update->getMessage());
        } elseif ($update->isType('callback_query')) {
            $this->handleCallbackQuery($update->getCallbackQuery());
        }
    }

    private function handleMessage($message): void
    {
        $userId = $message->getFrom()->getId();
        $chatId = $message->getChat()->getId();
        $text = trim($message->getText());

        // Save user and chat_id
        TelegramUser::updateOrCreate(
            ['user_id' => $userId],
            ['chat_id' => $chatId]
        );

        if ($text === '/add') {
            $this->stateService->setState($userId, 'awaiting_name');
            $this->telegramBot->sendMessage($chatId, "Введите имя именинника:");
            return;
        }

        if ($text === '/list') {
            $this->birthdayService->listBirthdays($userId, $chatId);
            return;
        }

        if ($text === '/upcoming') {
            $this->birthdayService->showUpcomingBirthdays($userId, $chatId);
            return;
        }

        if ($text === '/info') {
            $this->stateService->setState($userId, 'awaiting_info_input');
            $this->telegramBot->sendMessage($chatId, "Введите дату в формате MM-DD или YYYY-MM-DD, либо имя именинника, либо telegram username:");
            return;
        }

        if ($text === '/check') {
            $notificationService = new \App\Services\NotificationService($this->telegramBot);
            $todayBirthdays = $notificationService->getTodaysBirthdays();
            $tomorrowBirthdays = $notificationService->getTomorrowBirthdays();
            $totalBirthdays = count($todayBirthdays) + count($tomorrowBirthdays);
            if ($totalBirthdays === 0) {
                $this->telegramBot->sendMessage(
                    $chatId,
                    'Сегодня-завтра никаких дней рождений! Можете проверить ближайшие с помощью команды /upcoming'
                );
            } else {
                $notificationService->sendDailyBirthdayNotifications();
            }
            return;
        }

        $state = $this->stateService->getState($userId);

        if ($state && $state['state'] === 'awaiting_name') {
            $this->stateService->updateStateWithTempName($userId, $text, 'awaiting_username');
            $this->telegramBot->sendMessage(
                $chatId,
                'Теперь введите Telegram username именинника (с @ или без) или отправьте /skip чтобы пропустить:'
            );
            return;
        }

        if ($state && $state['state'] === 'awaiting_username') {
            $input = trim($text);

            if ($input === '/skip') {
                // Skip username step
                $this->stateService->updateStateWithTempNameAndUsername(
                    $userId,
                    $state['temp_name'],
                    null,
                    'awaiting_date'
                );
                $this->telegramBot->sendMessage(
                    $chatId,
                    'Username пропущен. Теперь введите дату рождения в формате ГГГГ-ММ-ДД или ММ-ДД:'
                );
                return;
            }

            if (empty($input)) {
                $this->telegramBot->sendMessage(
                    $chatId,
                    '❌ Поле не может быть пустым. Введите Telegram username (с @ или без).'
                        . PHP_EOL . 'Или отправьте /skip чтобы пропустить.'
                );
                return;
            }

            // Normalize username (remove @ if present)
            $normalizedUsername = $this->normalizeUsername($input);

            // Save normalized username
            $this->stateService->updateStateWithTempNameAndUsername(
                $userId,
                $state['temp_name'],
                $normalizedUsername,
                'awaiting_date'
            );
            $this->telegramBot->sendMessage(
                $chatId,
                'Теперь введите дату рождения в формате ГГГГ-ММ-ДД или ММ-ДД:'
            );
            return;
        }

        if ($state && $state['state'] === 'awaiting_date') {
            if (!$this->birthdayService->validateDate($text)) {
                $this->telegramBot->sendMessage(
                    $chatId,
                    '❌ Неверный формат. Введите в формате ГГГГ-ММ-ДД или ММ-ДД:'
                );
                return;
            }

            // Normalize date to YYYY-MM-DD format
            $normalizedDate = $this->birthdayService->normalizeDate($text);

            $this->birthdayService->addBirthday(
                $userId,
                $chatId,
                $state['temp_name'],
                $state['temp_username'],
                null,
                $normalizedDate
            );
            $this->stateService->clearState($userId);
            return;
        }

        if ($state && $state['state'] === 'awaiting_info_input') {
            $input = trim($text);
            if (empty($input)) {
                $this->telegramBot->sendMessage(
                    $chatId,
                    '❌ Поле не может быть пустым. Введите дату в формате MM-DD или YYYY-MM-DD, либо имя именинника, либо telegram username:'
                );
                return;
            }

            try {
                $zodiacService = new ZodiacService();
                $result = $zodiacService->getZodiacInfo($input);

                $message = "🔮 *Информация о знаке зодиака*\n\n";
                $message .= "📅 Дата: " . $result['date'] . "\n";

                if (isset($result['name'])) {
                    $message .= "👤 Имя: " . $result['name'] . "\n";
                }

                $message .= "♈ Знак зодиака: " . $result['zodiac_sign'] . "\n";

                if (isset($result['additional_info'])) {
                    $message .= "\n📊 *Дополнительная информация:*\n";
                    $message .= "📅 День недели: " . $result['additional_info']['day_of_week'] . "\n";
                    $message .= "🐉 Китайский зодиак: " . $result['additional_info']['chinese_zodiac'] . "\n";
                    $message .= "🌙 Фаза луны: " . $result['additional_info']['moon_phase'] . "\n";
                }

                $this->telegramBot->sendMessage($chatId, $message, ['parse_mode' => 'Markdown']);
            } catch (\Exception $e) {
                $this->telegramBot->sendMessage($chatId, '❌ Ошибка: ' . $e->getMessage());
            }

            $this->stateService->clearState($userId);
            return;
        }

        if ($state && $state['state'] === 'awaiting_greeting_style') {
            $style = trim($text);
            if (empty($style)) {
                $this->telegramBot->sendMessage(
                    $chatId,
                    '❌ Стиль не может быть пустым. Введите стиль поздравления:'
                );
                return;
            }

            try {
                $openAIService = new OpenAIService();
                $greeting = $openAIService->generateBirthdayGreeting($state['temp_name'], $style);

                // Send greeting to birthday person
                $birthdayChatId = $this->getChatIdByUsername($state['temp_username']);
                if ($birthdayChatId) {
                    $this->telegramBot->sendMessage($birthdayChatId, $greeting);
                    $this->telegramBot->sendMessage($chatId, '🤖 ИИ-поздравление отправлено!');
                } else {
                    // If chat_id not found, send to current chat with mention
                    $greetingWithMention = $greeting . PHP_EOL . PHP_EOL . 'https://t.me/' . $state['temp_username'];
                    $this->telegramBot->sendMessage($chatId, $greetingWithMention, ['parse_mode' => 'Markdown']);
                    $this->telegramBot->sendMessage($chatId, '🤖 ИИ-поздравление отправлено в чат!');
                }
            } catch (\Exception $e) {
                $this->telegramBot->sendMessage($chatId, '❌ Ошибка генерации поздравления: ' . $e->getMessage());
            }

            $this->stateService->clearState($userId);
            return;
        }

        $this->telegramBot->sendMessage($chatId, 'Команды:'
            . PHP_EOL . '/add — добавить именинника'
            . PHP_EOL . '/list — список и удаление'
            . PHP_EOL . '/upcoming — ближайшие дни рождения'
            . PHP_EOL . '/info — информация о знаке зодиака'
            . PHP_EOL . '/check — проверить дни рождения сегодня и завтра');
    }

    private function handleCallbackQuery($callback): void
    {
        $data = $callback->getData();
        $userId = $callback->getFrom()->getId();
        $chatId = $callback->getMessage()->getChat()->getId();

        if (preg_match('/^delete_(\d+)$/', $data, $matches)) {
            $id = (int) $matches[1];
            $this->birthdayService->deleteBirthday($id, $userId, $chatId, $callback->getId());
        }

        if (preg_match('/^greet_simple_(\d+)$/', $data, $m)) {
            $birthdayId = (int) $m[1];
            $birthday = Birthday::find($birthdayId);
            if ($birthday) {
                $greeting = $birthday->name . ', с днём рождения! 🎉' . PHP_EOL . 'Желаю счастья, радости, любви и тепла!';
                $birthdayChatId = $birthday->birthday_chat_id;
                if ($birthdayChatId) {
                    $this->telegramBot->sendMessage($birthdayChatId, $greeting);
                    $this->telegramBot->answerCallbackQuery($callback->getId(), '📨 Поздравление отправлено!');
                } else {
                    $greetingWithMention = $greeting;
                    if ($birthday->telegram_username) {
                        $greetingWithMention .= PHP_EOL . PHP_EOL . 'https://t.me/' . $birthday->telegram_username;
                    }
                    $this->telegramBot->sendMessage($chatId, $greetingWithMention, ['parse_mode' => 'Markdown']);
                    $this->telegramBot->answerCallbackQuery($callback->getId(), '📨 Поздравление отправлено в чат!');
                }
            } else {
                $this->telegramBot->answerCallbackQuery($callback->getId(), '❌ Именинник не найден');
            }
        }

        if (preg_match('/^greet_ai_(\d+)$/', $data, $m)) {
            $birthdayId = (int) $m[1];
            $birthday = Birthday::find($birthdayId);
            if ($birthday) {
                // Set state to await greeting style
                $this->stateService->updateStateWithTempNameAndUsername(
                    $userId,
                    $birthday->name,
                    $birthday->telegram_username,
                    'awaiting_greeting_style'
                );
                // Show predefined styles as buttons using Enum
                $keyboard = GreetingStyleEnum::getAllStyles($birthday->id);
                $this->telegramBot->sendMessage(
                    $chatId,
                    'Выберите стиль поздравления или введите свой:',
                    ['inline_keyboard' => $keyboard]
                );
                $this->telegramBot->answerCallbackQuery($callback->getId(), 'Выберите стиль');
            } else {
                $this->telegramBot->answerCallbackQuery($callback->getId(), '❌ Именинник не найден');
            }
        }

        \Illuminate\Support\Facades\Log::info($data);

        // Handle predefined style selections
        $pattern = implode('|', array_map(fn($e) => $e->value, GreetingStyleEnum::cases()));
        if (preg_match('/^style_(' . $pattern . ')_(\d+)$/', $data, $m)) {
            $style = $m[1];
            $birthdayId = (int)$m[2];
            $birthday = Birthday::find($birthdayId);
            if (!$birthday) {
                $this->telegramBot->sendMessage($chatId, '❌ Именинник не найден');
                $this->telegramBot->answerCallbackQuery($callback->getId(), '❌ Ошибка');
                return;
            }
            if ($style === 'custom') {
                // Set state to await custom style input
                $this->stateService->updateStateWithTempNameAndUsername(
                    $userId,
                    $birthday->name,
                    $birthday->telegram_username,
                    'awaiting_greeting_style'
                );
                $this->telegramBot->sendMessage(
                    $chatId,
                    'Введите свой стиль поздравления:'
                );
                $this->telegramBot->answerCallbackQuery($callback->getId(), 'Введите свой стиль');
                return;
            }
            // Get style from Enum
            $greetingStyle = GreetingStyleEnum::fromString($style);
            if (!$greetingStyle) {
                $this->telegramBot->sendMessage($chatId, '❌ Неизвестный стиль поздравления');
                $this->telegramBot->answerCallbackQuery($callback->getId(), '❌ Ошибка');
                return;
            }
            $styleText = $greetingStyle->getRussianDescription();
            try {
                $openAIService = new OpenAIService();
                $greeting = $openAIService->generateBirthdayGreeting($birthday->name, $styleText);
                // Send greeting to birthday person
                $birthdayChatId = $birthday->birthday_chat_id;
                if ($birthdayChatId) {
                    $this->telegramBot->sendMessage($birthdayChatId, $greeting);
                    $this->telegramBot->answerCallbackQuery($callback->getId(), '🤖 ИИ-поздравление отправлено!');
                } else {
                    $greetingWithMention = $greeting;
                    if ($birthday->telegram_username) {
                        $greetingWithMention .= PHP_EOL . PHP_EOL . 'Скопируй и отправь https://t.me/' . $birthday->telegram_username;
                    }
                    $this->telegramBot->sendMessage($chatId, $greetingWithMention, ['parse_mode' => 'Markdown']);
                    $this->telegramBot->answerCallbackQuery($callback->getId(), '🤖 ИИ-поздравление отправлено в чат!');
                }
            } catch (\Exception $e) {
                $this->telegramBot->sendMessage($chatId, '❌ Ошибка генерации поздравления: ' . $e->getMessage());
                $this->telegramBot->answerCallbackQuery($callback->getId(), '❌ Ошибка');
            }
        }
    }

    private function getChatIdByUsername(?string $username): ?int
    {
        if (!$username) {
            return null;
        }
        $birthday = Birthday::where('telegram_username', $username)->first();
        return $birthday ? $birthday->birthday_chat_id : null;
    }

    /**
     * Normalize Telegram username by removing @ symbol if present
     */
    private function normalizeUsername(string $username): string
    {
        return ltrim($username, '@');
    }
}
