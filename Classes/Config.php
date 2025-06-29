<?php

namespace Classes;

class Config
{
    private static array $config = [];

    public static function load(string $envFile = '.env'): void
    {
        if (!file_exists($envFile)) {
            throw new \RuntimeException("Environment file {$envFile} not found");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (
                    (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
                ) {
                    $value = substr($value, 1, -1);
                }

                self::$config[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$config[$key] ?? $default;
    }

    public static function getDatabaseHost(): string
    {
        return self::get('DB_HOST', 'localhost');
    }

    public static function getDatabaseName(): string
    {
        return self::get('DB_NAME', 'birthday_bot');
    }

    public static function getDatabaseUser(): string
    {
        return self::get('DB_USER', 'db_user');
    }

    public static function getDatabasePass(): string
    {
        return self::get('DB_PASS', 'db_pass');
    }

    public static function getBotToken(): string
    {
        $token = self::get('BOT_TOKEN');
        if (!$token) {
            throw new \RuntimeException('BOT_TOKEN not found in environment configuration');
        }
        return $token;
    }
}
