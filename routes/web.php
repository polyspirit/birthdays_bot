<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;

Route::get('/', function () {
    return view('welcome');
});

// Telegram Webhook Routes
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');
Route::get('/telegram/test-notifications', [TelegramWebhookController::class, 'testNotifications'])->name('telegram.test-notifications');
Route::get('/telegram/send-notifications', [TelegramWebhookController::class, 'sendDailyNotifications'])->name('telegram.send-notifications');
