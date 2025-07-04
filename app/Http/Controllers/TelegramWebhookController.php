<?php

namespace App\Http\Controllers;

use App\Services\WebhookHandlerService;
use App\Services\TelegramBotService;
use App\Services\BirthdayService;
use App\Services\UserStateService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    private WebhookHandlerService $webhookHandler;
    private NotificationService $notificationService;

    public function __construct()
    {
        $telegramBot = new TelegramBotService();
        $birthdayService = new BirthdayService($telegramBot);
        $stateService = new UserStateService();
        $this->webhookHandler = new WebhookHandlerService($telegramBot, $birthdayService, $stateService);
        $this->notificationService = new NotificationService($telegramBot);
    }

    public function handle(Request $request)
    {
        // Handle webhook updates
        $update = $this->webhookHandler->getTelegramBot()->getWebhookUpdate();
        $this->webhookHandler->handleUpdate($update);

        return response()->json(['status' => 'ok']);
    }

    public function testNotifications(Request $request)
    {
        $result = [
            'success' => false,
            'message' => '',
            'birthdays_found' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        try {
            // Get today's birthdays
            $birthdays = $this->notificationService->getTodaysBirthdays();
            $result['birthdays_found'] = count($birthdays);

            if (empty($birthdays)) {
                $result['message'] = 'No birthdays found for today';
                $result['success'] = true;
                return response()->json($result);
            }

            // Send notifications
            $this->notificationService->sendDailyBirthdayNotifications();
            $result['notifications_sent'] = count($birthdays);
            $result['message'] = "Successfully sent {$result['notifications_sent']} notifications";
            $result['success'] = true;
        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            $result['message'] = 'Error occurred while sending notifications';
        }

        return response()->json($result);
    }

    public function sendDailyNotifications()
    {
        $this->notificationService->sendDailyBirthdayNotifications();
        return response()->json(['status' => 'notifications sent']);
    }
}
