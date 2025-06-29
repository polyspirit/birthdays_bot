<?php

namespace Classes;

class NotificationTester
{
    private NotificationService $notificationService;
    private Database $database;

    public function __construct(NotificationService $notificationService, Database $database)
    {
        $this->notificationService = $notificationService;
        $this->database = $database;
    }

    public function testNotifications(): array
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
            $birthdays = $this->database->getTodaysBirthdays();
            $result['birthdays_found'] = count($birthdays);

            if (empty($birthdays)) {
                $result['message'] = 'No birthdays found for today';
                $result['success'] = true;
                return $result;
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

        return $result;
    }

    public function getTodaysBirthdays(): array
    {
        return $this->database->getTodaysBirthdays();
    }
}
