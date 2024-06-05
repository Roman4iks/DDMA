<?php
namespace Longman\TelegramBot\Commands\StudentCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class GroupCommand extends StudentCommand
{
    protected $name = 'group';

    protected $description = 'Отримати довідку про групу';

    protected $usage = '/group';

    protected $version = '1.0.0';

    protected $conversation;

    protected $need_mysql = true;


    public function executeNoDb(): ServerResponse
    {
        return $this->removeKeyboard('Нет діалога');
    }

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if (DB::getUserRole($user_id) !== "student") {
            return Request::sendMessage(['chat_id' => $chat_id, 'text' => 'Ця команда доступна тільки для студентів.']);
        }

        $user_data = DB::selectStudentData($user_id);
        $group = DB::selectGroupDataById($user_data['group_id']);
        $teacher = DB::selectTeacherDataByGroup($user_data['group_id']);
        $teacher_fullname = "";
        if($teacher){
            $teacher_data = DB::selectUserData($teacher['user_id']);
            $teacher_fullname = $teacher_data->first_name . " " . $teacher_data->second_name . " " . $teacher_data->middle_name;
        }
        $text = sprintf("Назва групи: %s\nПовна назва групи: %s\nПосилання на групу телеграм: %s\nКласний керівник: %s",
        $group->name,
        $group->fullname,
        $group->link ? "Немає" : $group->link,
        $teacher_fullname === "" ? "Немає" : $teacher_fullname );

        $keyboard = KeyboardTelegram::getKeyboard($user_id);

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}