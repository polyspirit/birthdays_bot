<?php

namespace Classes;

use PDO as PDOConnection;

class UserStateManager
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

    public function setState(int $userId, string $state): void
    {
        $stmt = $this->pdo->prepare("REPLACE INTO user_states (user_id, state) VALUES (?, ?)");
        $stmt->execute([$userId, $state]);
    }

    public function getState(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM user_states WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDOConnection::FETCH_ASSOC) ?: null;
    }

    public function updateStateWithTempName(int $userId, string $tempName, string $newState): void
    {
        $stmt = $this->pdo->prepare("UPDATE user_states SET temp_name = ?, state = ? WHERE user_id = ?");
        $stmt->execute([$tempName, $newState, $userId]);
    }

    public function clearState(int $userId): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_states WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
}
