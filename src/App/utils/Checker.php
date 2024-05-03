<?php

namespace App\utils;

use DateTime;
use Longman\TelegramBot\TelegramLog;

class Checker
{
    // TODO
    public static function isTimeAvailable($checkStartTime, $checkEndTime, $schedules): bool
    {
        foreach ($schedules as $schedule) {
            $scheduleStartTime = $schedule['start'];
            $scheduleEndTime = $schedule['end'];
    
            // Определение номера недели и текущей недели
            $checkWeek = $checkStartTime->format('W');
            $scheduleWeek = $scheduleStartTime->format('W');
            $isUpperWeek = $checkWeek % 2 === 0;
    
            // Проверка, находится ли текущая неделя выше или ниже расписания
            if (($isUpperWeek && $checkWeek < $scheduleWeek) || (!$isUpperWeek && $checkWeek > $scheduleWeek)) {
                continue; // Пропускаем проверку, если текущая неделя выше или ниже расписания
            }
    
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
            TelegramLog::debug("Invalid date min_year -> " . $date->format('Y-m-d H:i:s'));
            return false;
        }

        return true;
    }
}
