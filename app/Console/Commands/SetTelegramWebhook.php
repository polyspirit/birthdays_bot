<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Telegram webhook URL';

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

        $url = $this->argument('url');

        if (!$url) {
            $url = $this->ask('Enter your webhook URL (e.g., https://your-domain.com/telegram/webhook)');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL format');
            return 1;
        }

        $this->info("Setting webhook to: {$url}");

        try {
            $response = file_get_contents("https://api.telegram.org/bot{$token}/setWebhook?url={$url}");
            $result = json_decode($response, true);

            if ($result['ok']) {
                $this->info('✅ Webhook set successfully!');
                $this->info("Response: " . json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Failed to set webhook');
                $this->error("Error: " . ($result['description'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
