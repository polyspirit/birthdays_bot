<?php

namespace Classes;

use PDO as PDOConnection;

class Database
{
    private PDOConnection $pdo;

    public function __construct()
    {
        $host = Config::getDatabaseHost();
        $dbname = Config::getDatabaseName();
        $user = Config::getDatabaseUser();
        $pass = Config::getDatabasePass();

        $this->pdo = new PDOConnection("mysql:host={$host};dbname={$dbname}", $user, $pass);
    }

    public function saveUser(int $userId, int $chatId): void
    {
        $stmt = $this->pdo->prepare("REPLACE INTO users (user_id, chat_id) VALUES (?, ?)");
        $stmt->execute([$userId, $chatId]);
    }

    public function getUserBirthdays(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT id, name, telegram_username, birth_date FROM birthdays WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDOConnection::FETCH_ASSOC);
    }

    public function addBirthday(int $userId, string $name, string $telegramUsername, string $birthDate): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO birthdays (user_id, name, telegram_username, birth_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $name, $telegramUsername, $birthDate]);
    }

    public function deleteBirthday(int $id, int $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM birthdays WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }

    public function getTodaysBirthdays(): array
    {
        $today = date('m-d');
        $stmt = $this->pdo->prepare("SELECT b.name, b.telegram_username, b.birth_date, u.chat_id FROM birthdays b JOIN users u ON b.user_id = u.user_id WHERE DATE_FORMAT(b.birth_date, '%m-%d') = ?");
        $stmt->execute([$today]);
        return $stmt->fetchAll(PDOConnection::FETCH_ASSOC);
    }

    public function getChatIdByUsername(string $username): ?int
    {
        $stmt = $this->pdo->prepare("SELECT chat_id FROM users WHERE user_id = (SELECT user_id FROM birthdays WHERE telegram_username = ? LIMIT 1)");
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDOConnection::FETCH_ASSOC);
        return $result ? (int) $result['chat_id'] : null;
    }
}
