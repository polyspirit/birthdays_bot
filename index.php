<?php

require 'vendor/autoload.php';

use Classes\{
    Database,
    TelegramBot,
    UserStateManager,
    BirthdayManager,
    NotificationService,
    WebhookHandler,
    Webhook,
    Notification
};

// Configuration
const BOT_TOKEN = '7575800715:AAFdIdu0w1C0q57Kw3VCrTyy-AkW4pHVJIo';

// Initialize services
$database = new Database();
$telegramBot = new TelegramBot(BOT_TOKEN);
$stateManager = new UserStateManager();
$birthdayManager = new BirthdayManager($database, $telegramBot);
$notificationService = new NotificationService($database, $telegramBot);
$webhookHandler = new WebhookHandler($telegramBot, $database, $stateManager, $birthdayManager);

// ===== Webhook Logic (Incoming Updates) =====
if (php_sapi_name() !== 'cli') {
    $webhook = new Webhook($webhookHandler);
    $webhook->handle();
}

// ===== Daily Notification Script (run with cron) =====
if (php_sapi_name() === 'cli') {
    $notification = new Notification($notificationService);
    $notification->sendDailyNotifications();
}
