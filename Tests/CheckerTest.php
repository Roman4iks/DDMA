<?php

use App\utils\Checker;
use DateTime;
use PHPUnit\Framework\TestCase;

class CheckerTest extends TestCase
{
    public function testIsTimeAvailable()
    {
        // Подготовка данных
        $checkStartTime = new DateTime('2024-05-03 10:00:00');
        $checkEndTime = new DateTime('2024-05-03 11:00:00');
        $schedules = [
            ['start' => new DateTime('2024-05-03 09:00:00'), 'end' => new DateTime('2024-05-03 10:30:00')],
            ['start' => new DateTime('2024-05-03 11:30:00'), 'end' => new DateTime('2024-05-03 12:30:00')],
        ];

        // Проверка доступности времени
        $this->assertTrue(Checker::isTimeAvailable($checkStartTime, $checkEndTime, $schedules));

        // Проверка времени, пересекающегося с существующими расписаниями
        $checkStartTime = new DateTime('2024-05-03 22:50:00');
        $checkEndTime = new DateTime('2024-05-03 20:30:00');
        $this->assertFalse(Checker::isTimeAvailable($checkStartTime, $checkEndTime, $schedules));
    }

    public function testCheckMinimumBirthYearValid()
    {
        // Дата рождения, соответствующая минимальному возрасту
        $dateOfBirth = new DateTime('2012-05-03');
        $this->assertTrue(Checker::checkMinimumBirthYear($dateOfBirth));
    }

    public function testCheckMinimumBirthYearInvalid()
    {
        // Дата рождения младше минимального возраста (12 лет)
        $dateOfBirth = new DateTime('2013-05-03');
        $this->assertFalse(Checker::checkMinimumBirthYear($dateOfBirth));
    }
}
