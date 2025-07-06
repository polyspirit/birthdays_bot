<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = env('OPEN_AI_KEY');

        if (empty($this->apiKey)) {
            throw new \Exception('OPEN_AI_KEY is not set in environment variables');
        }
    }

    /**
     * Generate birthday greeting using GPT-3.5-turbo-0125
     */
    public function generateBirthdayGreeting(string $name, string $style): string
    {
        try {
            $prompt = 'Сгенерируй короткое поздравление с Днём Рождения (не более 30 слов) для человека по имени '
                . $name . '. Стиль поздравления: ' . $style;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-3.5-turbo-0125',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 0.7
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return trim($data['choices'][0]['message']['content']);
            } else {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new \Exception('Failed to generate greeting: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('OpenAI service error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
