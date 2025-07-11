<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class SendBirthdayNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'birthday:send-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily birthday notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $telegramBot = new TelegramBotService();
        $notificationService = new NotificationService($telegramBot);

        try {
            $todayBirthdays = $notificationService->getTodaysBirthdays();
            $tomorrowBirthdays = $notificationService->getTomorrowBirthdays();
            $totalBirthdays = count($todayBirthdays) + count($tomorrowBirthdays);

            if ($totalBirthdays === 0) {
                $this->info('No birthdays found for today or tomorrow.');
                return 0;
            }

            $notificationService->sendDailyBirthdayNotifications();

            $this->info("Successfully sent birthday notifications: " . count($todayBirthdays) . " for today, " . count($tomorrowBirthdays) . " for tomorrow.");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error sending notifications: ' . $e->getMessage());
            return 1;
        }
    }
}
