<?php

namespace Classes;

use Telegram\Bot\Api;

class Webhook
{
    private WebhookHandler $webhookHandler;

    public function __construct(WebhookHandler $webhookHandler)
    {
        $this->webhookHandler = $webhookHandler;
    }

    public function handle(): void
    {
        $update = $this->webhookHandler->getTelegramBot()->getWebhookUpdate();
        $this->webhookHandler->handleUpdate($update);
    }
}
