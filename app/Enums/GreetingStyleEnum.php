<?php

namespace App\Enums;

enum GreetingStyleEnum: string
{
    case FUN = 'fun';
    case FORMAL = 'formal';
    case ROMANTIC = 'romantic';
    case FRIENDLY = 'friendly';
    case POETIC = 'poetic';
    case HUMOROUS = 'humorous';

    /**
     * Get display name for the style
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::FUN => '🎉 Весёлое',
            self::FORMAL => '💼 Официальное',
            self::ROMANTIC => '💕 Романтичное',
            self::FRIENDLY => '🤝 Дружеское',
            self::POETIC => '📝 Поэтичное',
            self::HUMOROUS => '😄 Юмористическое',
        };
    }

    /**
     * Get Russian description for OpenAI prompt
     */
    public function getRussianDescription(): string
    {
        return match ($this) {
            self::FUN => 'весёлое',
            self::FORMAL => 'официальное',
            self::ROMANTIC => 'романтичное',
            self::FRIENDLY => 'дружеское',
            self::POETIC => 'поэтичное',
            self::HUMOROUS => 'юмористическое',
        };
    }

    /**
     * Get callback data for the style
     */
    public function getCallbackData(string $name, string $username): string
    {
        return 'style_' . $this->value . '_' . urlencode($name) . '_' . urlencode($username);
    }

    /**
     * Get all styles as array for keyboard
     */
    public static function getAllStyles(string $name, string $username): array
    {
        $styles = self::cases();
        $keyboard = [];

        // Group styles into pairs for keyboard layout
        for ($i = 0; $i < count($styles); $i += 2) {
            $row = [
                [
                    'text' => $styles[$i]->getDisplayName(),
                    'callback_data' => $styles[$i]->getCallbackData($name, $username)
                ]
            ];

            // Add second button if exists
            if (isset($styles[$i + 1])) {
                $row[] = [
                    'text' => $styles[$i + 1]->getDisplayName(),
                    'callback_data' => $styles[$i + 1]->getCallbackData($name, $username)
                ];
            }

            $keyboard[] = $row;
        }

        // Add custom style button
        $keyboard[] = [
            [
                'text' => '✏️ Свой стиль',
                'callback_data' => 'style_custom_' . urlencode($name) . '_' . urlencode($username)
            ]
        ];

        return $keyboard;
    }

    /**
     * Try to create from string value (custom method)
     */
    public static function fromString(string $value): ?self
    {
        return match ($value) {
            'fun' => self::FUN,
            'formal' => self::FORMAL,
            'romantic' => self::ROMANTIC,
            'friendly' => self::FRIENDLY,
            'poetic' => self::POETIC,
            'humorous' => self::HUMOROUS,
            default => null,
        };
    }
}
