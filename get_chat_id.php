<?php

/**
 * Скрипт для получения chat_id пользователя
 *
 * Инструкция:
 * 1. Пользователь должен написать боту любое сообщение
 * 2. Запустить этот скрипт
 * 3. Найти в выводе chat_id пользователя
 */

require_once 'vendor/autoload.php';

use Classes\Config;
use Classes\TelegramBot;

// Загружаем конфигурацию
Config::load();

// Создаем экземпляр бота
$bot = new TelegramBot(Config::getBotToken());

echo "=== Получение chat_id пользователей ===\n";
echo "Попросите пользователя написать боту любое сообщение, затем нажмите Enter...\n";
readline();

try {
    // Получаем обновления
    $updates = $bot->getWebhookUpdate();

    if (empty($updates)) {
        echo "Нет новых сообщений. Попросите пользователя написать боту.\n";
        exit;
    }

    echo "\nНайденные пользователи:\n";
    echo "======================\n";

    foreach ($updates as $update) {
        if ($update->isType('message')) {
            $message = $update->getMessage();
            $user = $message->getFrom();
            $chat = $message->getChat();

            echo "Имя: " . ($user->getFirstName() ?? 'Не указано') . "\n";
            echo "Username: @" . ($user->getUsername() ?? 'Не указан') . "\n";
            echo "Chat ID: " . $chat->getId() . "\n";
            echo "User ID: " . $user->getId() . "\n";
            echo "Текст: " . $message->getText() . "\n";
            echo "---\n";
        }
    }
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}
