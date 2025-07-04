<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class GetChatId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:get-chat-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get chat_id of users who sent messages to the bot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $telegramBot = new TelegramBotService();

        $this->info("=== Получение chat_id пользователей ===");
        $this->info("Попросите пользователя написать боту любое сообщение, затем нажмите Enter...");

        $this->ask('Press Enter to continue...');

        try {
            // Get updates
            $updates = $telegramBot->getWebhookUpdate();

            if (empty($updates)) {
                $this->error("Нет новых сообщений. Попросите пользователя написать боту.");
                return 1;
            }

            $this->info("\nНайденные пользователи:");
            $this->info("======================");

            foreach ($updates as $update) {
                if ($update->isType('message')) {
                    $message = $update->getMessage();
                    $user = $message->getFrom();
                    $chat = $message->getChat();

                    $this->line("Имя: " . ($user->getFirstName() ?? 'Не указано'));
                    $this->line("Username: @" . ($user->getUsername() ?? 'Не указан'));
                    $this->line("Chat ID: " . $chat->getId());
                    $this->line("User ID: " . $user->getId());
                    $this->line("Текст: " . $message->getText());
                    $this->line("---");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Ошибка: " . $e->getMessage());
            return 1;
        }
    }
}
