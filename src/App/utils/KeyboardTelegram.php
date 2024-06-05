<?php

namespace App\utils;

use App\DB;
use Longman\TelegramBot\Entities\Keyboard;

class KeyboardTelegram
{
    private static $userCommand = [['Регістрація', 'Новини'],['Допомога']];
    private static $teacherCommand = [['Інформація про студентів', 'Команди для груп'],['Команди для завдань', 'Команди для предметів'], ['Розсилка повідомлення']];
    private static $studentCommand = [['Розклад пар', 'Завдання'], ['Інформація', 'Відправити завдання'],['Записатися на консультацію']];

    public static function getKeyboard($user_id){
        if (!DB::selectUserData($user_id)) {
            $keyboard = new Keyboard(
                ...self::$userCommand
            );
        }

        if (DB::getUserRole($user_id) === "teacher") {
            $keyboard = new Keyboard(
                ...self::$teacherCommand
            );
        }else if(DB::getUserRole($user_id) === "student"){
            $keyboard = new Keyboard(
                ...self::$studentCommand
            );
        }

        $keyboard->setResizeKeyboard(true)
                ->setOneTimeKeyboard(false);
        return $keyboard;
    }

    public static function getKeyboardKeys($user_id){
        $buttons = [];
        if (!DB::selectUserData($user_id)) {
            $buttons = self::$userCommand;
        }
        if (DB::getUserRole($user_id) === "teacher") {
            $buttons = self::$teacherCommand;
        }else if(DB::getUserRole($user_id) === "student"){
            $buttons = self::$studentCommand;
        }

        return array_merge(...$buttons);
    }
}