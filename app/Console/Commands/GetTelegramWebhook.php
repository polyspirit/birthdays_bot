<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:get-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get current Telegram webhook info';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('telegram.bot_token');

        if (!$token) {
            $this->error('TELEGRAM_BOT_TOKEN not found in .env file');
            return 1;
        }

        $this->info("Getting webhook info...");

        try {
            $response = file_get_contents("https://api.telegram.org/bot{$token}/getWebhookInfo");
            $result = json_decode($response, true);

            if ($result['ok']) {
                $this->info('✅ Webhook info retrieved successfully!');
                $this->info("Response: " . json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Failed to get webhook info');
                $this->error("Error: " . ($result['description'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
