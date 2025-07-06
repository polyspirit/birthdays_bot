<?php

namespace App\Services;

use App\Models\TelegramUser;
use App\Models\Birthday;

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

        $state = $this->stateService->getState($userId);

        if ($state && $state['state'] === 'awaiting_name') {
            $this->stateService->updateStateWithTempName($userId, $text, 'awaiting_username');
            $this->telegramBot->sendMessage(
                $chatId,
                'Теперь введите Telegram username именинника (с @ или без):'
            );
            return;
        }

        if ($state && $state['state'] === 'awaiting_username') {
            $input = trim($text);
            if (empty($input)) {
                $this->telegramBot->sendMessage(
                    $chatId,
                    '❌ Поле не может быть пустым. Введите Telegram username (с @ или без):'
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
            . PHP_EOL . '/list — список и удаление');
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

        if (preg_match('/^greet_simple_(.+)_(.+)$/', $data, $m)) {
            $name = urldecode($m[1]);
            $username = urldecode($m[2]);

            $greeting = $name . ', с днём рождения! 🎉' . PHP_EOL . 'Желаю счастья, радости, любви и тепла!';

            // Send greeting to birthday person
            $birthdayChatId = $this->getChatIdByUsername($username);
            if ($birthdayChatId) {
                $this->telegramBot->sendMessage($birthdayChatId, $greeting);
                $this->telegramBot->answerCallbackQuery($callback->getId(), '📨 Поздравление отправлено!');
            } else {
                // If chat_id not found, send to current chat with mention
                $greetingWithMention = $greeting . PHP_EOL . PHP_EOL . 'https://t.me/' . $username;
                $this->telegramBot->sendMessage($chatId, $greetingWithMention, ['parse_mode' => 'Markdown']);
                $this->telegramBot->answerCallbackQuery($callback->getId(), '📨 Поздравление отправлено в чат!');
            }
        }

        if (preg_match('/^greet_ai_(.+)_(.+)$/', $data, $m)) {
            $name = urldecode($m[1]);
            $username = urldecode($m[2]);

            // Set state to await greeting style
            $this->stateService->updateStateWithTempNameAndUsername(
                $userId,
                $name,
                $username,
                'awaiting_greeting_style'
            );

            // Show predefined styles as buttons
            $keyboard = [
                [
                    ['text' => '🎉 Весёлое', 'callback_data' => 'style_fun_' . urlencode($name) . '_' . urlencode($username)],
                    ['text' => '💼 Официальное', 'callback_data' => 'style_formal_' . urlencode($name) . '_' . urlencode($username)]
                ],
                [
                    ['text' => '💕 Романтичное', 'callback_data' => 'style_romantic_' . urlencode($name) . '_' . urlencode($username)],
                    ['text' => '🤝 Дружеское', 'callback_data' => 'style_friendly_' . urlencode($name) . '_' . urlencode($username)]
                ],
                [
                    ['text' => '📝 Поэтичное', 'callback_data' => 'style_poetic_' . urlencode($name) . '_' . urlencode($username)],
                    ['text' => '😄 Юмористическое', 'callback_data' => 'style_humorous_' . urlencode($name) . '_' . urlencode($username)]
                ],
                [
                    ['text' => '✏️ Свой стиль', 'callback_data' => 'style_custom_' . urlencode($name) . '_' . urlencode($username)]
                ]
            ];

            $this->telegramBot->sendMessage(
                $chatId,
                'Выберите стиль поздравления или введите свой:',
                ['inline_keyboard' => $keyboard]
            );
            $this->telegramBot->answerCallbackQuery($callback->getId(), 'Выберите стиль');
        }

        // Handle predefined style selections
        if (preg_match('/^style_(.+)_(.+)_(.+)$/', $data, $m)) {
            $style = urldecode($m[1]);
            $name = urldecode($m[2]);
            $username = urldecode($m[3]);

            if ($style === 'custom') {
                // Set state to await custom style input
                $this->stateService->updateStateWithTempNameAndUsername(
                    $userId,
                    $name,
                    $username,
                    'awaiting_greeting_style'
                );

                $this->telegramBot->sendMessage(
                    $chatId,
                    'Введите свой стиль поздравления:'
                );
                $this->telegramBot->answerCallbackQuery($callback->getId(), 'Введите свой стиль');
                return;
            }

            // Map style codes to Russian descriptions
            $styleMap = [
                'fun' => 'весёлое',
                'formal' => 'официальное',
                'romantic' => 'романтичное',
                'friendly' => 'дружеское',
                'poetic' => 'поэтичное',
                'humorous' => 'юмористическое'
            ];

            $styleText = $styleMap[$style] ?? $style;

            try {
                $openAIService = new OpenAIService();
                $greeting = $openAIService->generateBirthdayGreeting($name, $styleText);

                // Send greeting to birthday person
                $birthdayChatId = $this->getChatIdByUsername($username);
                if ($birthdayChatId) {
                    $this->telegramBot->sendMessage($birthdayChatId, $greeting);
                    $this->telegramBot->answerCallbackQuery($callback->getId(), '🤖 ИИ-поздравление отправлено!');
                } else {
                    // If chat_id not found, send to current chat with mention
                    $greetingWithMention = $greeting . PHP_EOL . PHP_EOL 
                        . 'Скопируй и отправь https://t.me/' . $username;
                    $this->telegramBot->sendMessage($chatId, $greetingWithMention, ['parse_mode' => 'Markdown']);
                    $this->telegramBot->answerCallbackQuery($callback->getId(), '🤖 ИИ-поздравление отправлено в чат!');
                }
            } catch (\Exception $e) {
                $this->telegramBot->sendMessage($chatId, '❌ Ошибка генерации поздравления: ' . $e->getMessage());
                $this->telegramBot->answerCallbackQuery($callback->getId(), '❌ Ошибка');
            }
        }
    }

    private function getChatIdByUsername(string $username): ?int
    {
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
