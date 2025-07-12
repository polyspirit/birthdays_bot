<?php

namespace App\Console\Commands;

use App\Services\ZodiacService;
use Illuminate\Console\Command;

class GetZodiacInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zodiac:info {input}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get zodiac sign and additional information for a date or person';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $input = $this->argument('input');

        try {
            $zodiacService = new ZodiacService();
            $result = $zodiacService->getZodiacInfo($input);

            $this->info('Zodiac Information:');
            $this->line('Date: ' . $result['date']);
            if (isset($result['name'])) {
                $this->line('Name: ' . $result['name']);
            }
            $this->line('Zodiac Sign: ' . $result['zodiac_sign']);

            if (isset($result['additional_info'])) {
                $this->line('');
                $this->info('Additional Information:');
                $this->line('Day of Week: ' . $result['additional_info']['day_of_week']);
                $this->line('Chinese Zodiac: ' . $result['additional_info']['chinese_zodiac']);
                $this->line('Moon Phase: ' . $result['additional_info']['moon_phase']);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
