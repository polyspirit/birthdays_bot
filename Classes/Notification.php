<?php

namespace Classes;

class Notification
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function sendDailyNotifications(): void
    {
        $this->notificationService->sendDailyBirthdayNotifications();
    }
}
