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

        $this->telegramBot->sendMessage($chatId, "Команды:\n/add — добавить именинника\n/list — список и удаление");
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

        if (preg_match('/^greet_(.+)_(.+)$/', $data, $m)) {
            $name = urldecode($m[1]);
            $username = urldecode($m[2]);

            $greeting = "🎉 С днём рождения, {$name}!\nЖелаю счастья, радости и тепла!";

            // Send greeting to birthday person
            $birthdayChatId = $this->getChatIdByUsername($username);
            if ($birthdayChatId) {
                $this->telegramBot->sendMessage($birthdayChatId, $greeting);
                $this->telegramBot->answerCallbackQuery($callback->getId(), '📨 Поздравление отправлено!');
            } else {
                // If chat_id not found, send to current chat with mention
                $greetingWithMention = '🎉 С днём рождения, [' . $name . '](https://t.me/' . $username . ')!'
                    . PHP_EOL . 'Желаю счастья, радости и тепла!';
                $this->telegramBot->sendMessage($chatId, $greetingWithMention, ['parse_mode' => 'Markdown']);
                $this->telegramBot->answerCallbackQuery($callback->getId(), '📨 Поздравление отправлено в чат!');
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
