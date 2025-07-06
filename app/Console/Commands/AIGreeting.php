<?php

namespace App\Console\Commands;

use App\Services\OpenAIService;
use Illuminate\Console\Command;

class AIGreeting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-greeting {name} {style}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AI greeting generation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $style = $this->argument('style');

        $this->info('Testing AI greeting generation for ' . $name . ' with style: ' . $style);

        try {
            $openAIService = new OpenAIService();
            $greeting = $openAIService->generateBirthdayGreeting($name, $style);

            $this->info('Generated greeting:');
            $this->line($greeting);

            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
