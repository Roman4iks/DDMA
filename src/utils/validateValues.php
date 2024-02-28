<?php
namespace App\utils;

use Longman\TelegramBot\TelegramLog;

function validateString(string $text): bool
{
    if (!preg_match('/^[a-zA-Zа-яА-ЯёЁҐґЄєІіЇї]+$/u', $text)) {
        TelegramLog::debug("Invalid string -> " . $text);
        return true;
    }
    return false;    
}

function validateDate(string $text): string
{
    $date = \DateTime::createFromFormat('Y-m-d', $text);
    if (!$date || $date->format('Y-m-d') !== $text) {
        TelegramLog::debug("Invalid string date format -> " . $text);
        return 'Год рождения не соответствует формату';
    }

    $min_year = (new \DateTime())->modify('-12 years')->format('Y');

    if ($date->format('Y') > $min_year) {
        TelegramLog::debug("Invalid string date min_year -> " . $text);
        return 'Год рождения должен быть не ранее ' . $min_year;
    }

    list($year, $month, $day) = explode('-', $text);
    if (!checkdate((int)$month, (int)$day, (int)$year)) {
        TelegramLog::debug("Invalid string date existence -> " . $text);
        return 'Указанная дата не существует';
    }

    return false;
}

function validateEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) === false;
}