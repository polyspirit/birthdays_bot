<?php

namespace Classes;

class WebhookHandler
{
    private TelegramBot $telegramBot;
    private Database $database;
    private UserStateManager $stateManager;
    private BirthdayManager $birthdayManager;

    public function __construct(
        TelegramBot $telegramBot,
        Database $database,
        UserStateManager $stateManager,
        BirthdayManager $birthdayManager
    ) {
        $this->telegramBot = $telegramBot;
        $this->database = $database;
        $this->stateManager = $stateManager;
        $this->birthdayManager = $birthdayManager;
    }

    public function getTelegramBot(): TelegramBot
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

        // Сохраняем пользователя и chat_id
        $this->database->saveUser($userId, $chatId);

        if ($text === '/add') {
            $this->stateManager->setState($userId, 'awaiting_name');
            $this->telegramBot->sendMessage($chatId, "Введите имя именинника:");
            return;
        }

        if ($text === '/list') {
            $this->birthdayManager->listBirthdays($userId, $chatId);
            return;
        }

        $state = $this->stateManager->getState($userId);

        if ($state && $state['state'] === 'awaiting_name') {
            $this->stateManager->updateStateWithTempName($userId, $text, 'awaiting_username');
            $this->telegramBot->sendMessage($chatId, "Теперь введите Telegram username именинника (без @):");
            return;
        }

        if ($state && $state['state'] === 'awaiting_username') {
            $username = trim($text);
            if (empty($username)) {
                $this->telegramBot->sendMessage($chatId, "❌ Username не может быть пустым. Введите Telegram username:");
                return;
            }

            // Получаем chat_id именинника по username
            $birthdayChatId = $this->telegramBot->getChatIdByUsername($username);
            if (!$birthdayChatId) {
                $this->telegramBot->sendMessage($chatId, "❌ Не удалось найти пользователя с username @{$username}. Проверьте правильность username и попробуйте снова:");
                return;
            }

            $this->stateManager->updateStateWithTempNameUsernameAndChatId($userId, $state['temp_name'], $username, $birthdayChatId, 'awaiting_date');
            $this->telegramBot->sendMessage($chatId, "Теперь введите дату рождения в формате ГГГГ-ММ-ДД:");
            return;
        }

        if ($state && $state['state'] === 'awaiting_date') {
            if (!$this->birthdayManager->validateDate($text)) {
                $this->telegramBot->sendMessage($chatId, "❌ Неверный формат. Введите в формате ГГГГ-ММ-ДД:");
                return;
            }

            $this->birthdayManager->addBirthday($userId, $chatId, $state['temp_name'], $state['temp_username'], $state['temp_birthday_chat_id'], $text);
            $this->stateManager->clearState($userId);
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
            $this->birthdayManager->deleteBirthday($id, $userId, $chatId, $callback->getId());
        }

        if (preg_match('/^greet_(.+)_(.+)$/', $data, $m)) {
            $name = urldecode($m[1]);
            $username = urldecode($m[2]);

            $greeting = "🎉 С днём рождения, {$name}!\nЖелаю счастья, радости и тепла!";

            // Отправляем поздравление имениннику
            $birthdayChatId = $this->database->getChatIdByUsername($username);
            if ($birthdayChatId) {
                $this->telegramBot->sendMessage($birthdayChatId, $greeting);
                $this->telegramBot->answerCallbackQuery($callback->getId(), '📨 Поздравление отправлено!');
            } else {
                $this->telegramBot->answerCallbackQuery($callback->getId(), '❌ Не удалось отправить поздравление');
            }
        }
    }
}
