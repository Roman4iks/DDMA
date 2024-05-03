<?php

namespace App\utils;

use DateTime;
use Longman\TelegramBot\TelegramLog;

class Checker
{
    public static function isTimeAvailable($checkStartTime, $checkEndTime, $schedules): bool
    {
        foreach ($schedules as $schedule) {
            $scheduleStartTime = $schedule['start'];
            $scheduleEndTime = $schedule['end'];

            // Проверяем наличие пересечения времени
            if (($checkStartTime >= $scheduleStartTime && $checkStartTime < $scheduleEndTime) ||
                ($checkEndTime > $scheduleStartTime && $checkEndTime <= $scheduleEndTime) ||
                ($checkStartTime <= $scheduleStartTime && $checkEndTime >= $scheduleEndTime)
            ) {
                return false; // Время пересекается с одним из занятий
            }
        }
        return true; // Время доступно
    }

    public static function checkMinimumBirthYear(DateTime $date, int $year = 12): bool
    {
        $min_year = (new \DateTime())->modify('-' . $year . ' years')->format('Y');

        if ($date->format('Y') > $min_year) {
            TelegramLog::debug("Invalid date min_year -> " . $date);
            return false;
        }

        return true;
    }
}
