<?php

namespace App\utils;

class Checker
{
    public static function isTimeAvailable($checkStartTime, $checkEndTime, $schedules)
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
}
