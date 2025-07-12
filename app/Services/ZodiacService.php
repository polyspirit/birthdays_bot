<?php

namespace App\Services;

use App\Models\Birthday;
use Carbon\Carbon;

class ZodiacService
{
    /**
     * Get zodiac information for input
     */
    public function getZodiacInfo(string $input): array
    {
        $date = null;
        $name = null;

        // Try to parse as date first
        if (preg_match('/^\d{2}-\d{2}$/', $input)) {
            // Format: MM-DD
            $date = Carbon::createFromFormat('m-d', $input);
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $input)) {
            // Format: YYYY-MM-DD
            $date = Carbon::parse($input);
        } else {
            // Try to find by name or telegram username
            $birthday = Birthday::where('name', $input)
                ->orWhere('telegram_username', $input)
                ->first();

            if ($birthday) {
                $date = Carbon::parse($birthday->birth_date);
                $name = $birthday->name;
            } else {
                throw new \Exception('Could not find birthday for: ' . $input);
            }
        }

        if (!$date) {
            throw new \Exception('Invalid date format or could not parse date');
        }

        $result = [
            'date' => $date->format('Y-m-d'),
            'zodiac_sign' => $this->getZodiacSign($date),
        ];

        if ($name) {
            $result['name'] = $name;
        }

        // If specific year (not 9996), get additional information
        if ($date->year != 9996) {
            $result['additional_info'] = [
                'day_of_week' => $date->format('l'),
                'chinese_zodiac' => $this->getChineseZodiac($date->year),
                'moon_phase' => $this->getMoonPhase($date),
            ];
        }

        return $result;
    }

    /**
     * Get zodiac sign based on date
     */
    private function getZodiacSign(Carbon $date): string
    {
        $month = $date->month;
        $day = $date->day;

        if (($month == 3 && $day >= 21) || ($month == 4 && $day <= 19)) {
            return 'Aries';
        } elseif (($month == 4 && $day >= 20) || ($month == 5 && $day <= 20)) {
            return 'Taurus';
        } elseif (($month == 5 && $day >= 21) || ($month == 6 && $day <= 20)) {
            return 'Gemini';
        } elseif (($month == 6 && $day >= 21) || ($month == 7 && $day <= 22)) {
            return 'Cancer';
        } elseif (($month == 7 && $day >= 23) || ($month == 8 && $day <= 22)) {
            return 'Leo';
        } elseif (($month == 8 && $day >= 23) || ($month == 9 && $day <= 22)) {
            return 'Virgo';
        } elseif (($month == 9 && $day >= 23) || ($month == 10 && $day <= 22)) {
            return 'Libra';
        } elseif (($month == 10 && $day >= 23) || ($month == 11 && $day <= 21)) {
            return 'Scorpio';
        } elseif (($month == 11 && $day >= 22) || ($month == 12 && $day <= 21)) {
            return 'Sagittarius';
        } elseif (($month == 12 && $day >= 22) || ($month == 1 && $day <= 19)) {
            return 'Capricorn';
        } elseif (($month == 1 && $day >= 20) || ($month == 2 && $day <= 18)) {
            return 'Aquarius';
        } else {
            return 'Pisces';
        }
    }

    /**
     * Get Chinese zodiac based on year
     */
    private function getChineseZodiac(int $year): string
    {
        $zodiacs = [
            'Rat', 'Ox', 'Tiger', 'Rabbit', 'Dragon', 'Snake',
            'Horse', 'Goat', 'Monkey', 'Rooster', 'Dog', 'Pig'
        ];

        $index = ($year - 1900) % 12;
        return $zodiacs[$index];
    }

    /**
     * Get moon phase (simplified calculation)
     */
    private function getMoonPhase(Carbon $date): string
    {
        // Simplified moon phase calculation
        // This is a basic approximation
        $daysSinceNewMoon = $date->diffInDays(Carbon::create(1900, 1, 6));
        $phase = ($daysSinceNewMoon % 29.53) / 29.53;

        if ($phase < 0.0625) {
            return 'New Moon';
        } elseif ($phase < 0.1875) {
            return 'Waxing Crescent';
        } elseif ($phase < 0.3125) {
            return 'First Quarter';
        } elseif ($phase < 0.4375) {
            return 'Waxing Gibbous';
        } elseif ($phase < 0.5625) {
            return 'Full Moon';
        } elseif ($phase < 0.6875) {
            return 'Waning Gibbous';
        } elseif ($phase < 0.8125) {
            return 'Last Quarter';
        } elseif ($phase < 0.9375) {
            return 'Waning Crescent';
        } else {
            return 'New Moon';
        }
    }
}
