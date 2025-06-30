<?php

namespace Classes;

use Telegram\Bot\Api;

class TelegramBot
{
    private Api $telegram;

    public function __construct(string $botToken)
    {
        $this->telegram = new Api($botToken);
    }

    public function getWebhookUpdate()
    {
        return $this->telegram->getWebhookUpdate();
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $params = ['chat_id' => $chatId, 'text' => $text];
        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup);
        }
        $this->telegram->sendMessage($params);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text): void
    {
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQueryId,
            'text' => $text
        ]);
    }

    public function getChatIdByUsername(string $username): ?int
    {
        try {
            // Попробуем получить информацию о чате
            $response = $this->telegram->getChat(['chat_id' => '@' . $username]);
            return $response->getId();
        } catch (\Exception $e) {
            // Если не удалось получить через getChat, возвращаем null
            // Пользователь может ввести chat_id вручную
            return null;
        }
    }
}
