<?php

require 'vendor/autoload.php';

use Classes\{
    Config,
    Database,
    TelegramBot,
    UserStateManager,
    BirthdayManager,
    NotificationService,
    WebhookHandler,
    Webhook,
    Notification
};

// Load configuration from .env file
Config::load();

// Initialize services
$database = new Database();
$telegramBot = new TelegramBot(Config::getBotToken());
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
