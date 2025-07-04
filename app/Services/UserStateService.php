<?php

namespace App\Services;

use App\Models\UserState;

class UserStateService
{
    public function setState(int $userId, string $state): void
    {
        UserState::updateOrCreate(
            ['user_id' => $userId],
            ['state' => $state]
        );
    }

    public function getState(int $userId): ?array
    {
        $state = UserState::where('user_id', $userId)->first();
        return $state ? $state->toArray() : null;
    }

    public function updateStateWithTempName(int $userId, string $tempName, string $newState): void
    {
        UserState::updateOrCreate(
            ['user_id' => $userId],
            [
                'temp_name' => $tempName,
                'state' => $newState
            ]
        );
    }

    public function updateStateWithTempUsername(int $userId, string $tempUsername, string $newState): void
    {
        UserState::updateOrCreate(
            ['user_id' => $userId],
            [
                'temp_username' => $tempUsername,
                'state' => $newState
            ]
        );
    }

    public function updateStateWithTempNameAndUsername(int $userId, string $tempName, string $tempUsername, string $newState): void
    {
        UserState::updateOrCreate(
            ['user_id' => $userId],
            [
                'temp_name' => $tempName,
                'temp_username' => $tempUsername,
                'state' => $newState
            ]
        );
    }

    public function updateStateWithTempNameUsernameAndChatId(int $userId, string $tempName, string $tempUsername, int $tempBirthdayChatId, string $newState): void
    {
        UserState::updateOrCreate(
            ['user_id' => $userId],
            [
                'temp_name' => $tempName,
                'temp_username' => $tempUsername,
                'temp_birthday_chat_id' => $tempBirthdayChatId,
                'state' => $newState
            ]
        );
    }

    public function clearState(int $userId): void
    {
        UserState::where('user_id', $userId)->delete();
    }
}
