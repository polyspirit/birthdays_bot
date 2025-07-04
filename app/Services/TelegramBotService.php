<?php

namespace App\Services;

use Telegram\Bot\Api;

class TelegramBotService
{
    private Api $telegram;

    public function __construct()
    {
        $this->telegram = new Api(config('telegram.bot_token'));
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
            // Try to get chat information
            $response = $this->telegram->getChat(['chat_id' => '@' . $username]);
            return $response->getId();
        } catch (\Exception $e) {
            // If we can't get through getChat, return null
            // User can enter chat_id manually
            return null;
        }
    }
}
