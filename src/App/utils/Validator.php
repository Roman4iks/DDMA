<?php

namespace App\utils;

use Longman\TelegramBot\TelegramLog;

class Validator
{
    public static function validateString(string $str): bool
    {
        if (!preg_match('/^[a-zA-Zа-яА-ЯёЁҐґЄєІіЇї]+$/u', $str)) {
            TelegramLog::debug("Invalid string -> " . $str);
            return false;
        }
        return true;
    }

    public static function validateDate(string $date): bool
    {
        $formattedDate = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$formattedDate || $formattedDate->format('Y-m-d') !== $date) {
            TelegramLog::debug("Invalid string date format -> " . $date);
            return false;
        }
    
        return true;
    }

    public static function validateDateAndTime(string $date): bool
    {
        $formattedDate = \DateTime::createFromFormat('Y-m-d H:i', $date);
        if (!$formattedDate || $formattedDate->format('Y-m-d H:i') !== $date) {
            TelegramLog::debug("Invalid string date format -> " . $date);
            return false;
        }
    
        return true;
    }

    public static function validateTime(string $date): bool
    {
        $formattedDate = \DateTime::createFromFormat('H:i', $date);
        if (!$formattedDate || $formattedDate->format('H:i') !== $date) {
            TelegramLog::debug("Invalid string time format -> " . $date);
            return false;
        }
    
        return true;
    }

    public static function validateEmail(string $email): bool
    {
        $validate = filter_var($email, FILTER_VALIDATE_EMAIL);
        if(!$validate){
            TelegramLog::debug("Invalid email format -> " . $email);
            return false;
        }

        return true;
    }
}
