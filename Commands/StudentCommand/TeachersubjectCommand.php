<?php
namespace Longman\TelegramBot\Commands\StudentCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class TeachersubjectCommand extends StudentCommand
{
    protected $name = 'teachersubject';

    protected $description = 'Отримати довідку про вчителів';

    protected $usage = '/teachersubject або /teachersubject <subject>';

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
        $command_str = trim($message->getText(true));
        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if (DB::getUserRole($user_id) !== "student") {
            return Request::sendMessage(['chat_id' => $chat_id, 'text' => 'Ця команда доступна тільки для студентів.']);
        }

        $keyboard = KeyboardTelegram::getKeyboard($user_id);

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        $state = $notes['state'] ?? 0;

        $result = Request::emptyResponse();
        $command_str = $command_str === "Вчителі та предмети" ? "" : $command_str;

        
        if ($command_str !== "") {
            $text = $this->getSubjectsAndTeachers($command_str);
            
            $data = [
                'chat_id' => $chat_id,
                'text'    => $text,
                'reply_markup' => $keyboard,
            ];

            return Request::sendMessage($data);
        } else {
            $data = [
                'chat_id'      => $chat_id,
                'reply_markup' => new Keyboard(["/cancel"]),
            ];

            switch ($state) {
                case 0:
                    $data_objects = DB::selectAllTeacherSubjectData();

        
                    $subjects = [];
                    foreach ($data_objects as $subject_teacher) {
                        $subject = DB::selectSubjectDataById($subject_teacher['subject_id']);
                        $subjects[] = $subject->name;
                    }

                    
                    if ($text === '' || (!in_array($text, $subjects, true))) {
                        $notes['state'] = 0;
                        $this->conversation->update();
                        
                        $data['reply_markup'] = (new Keyboard($subjects))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        if(count($subjects) === 0) {
                            $data['text'] = 'Предметів за вчителями не загестровано:';
                            $result = Request::sendMessage($data);
                            $this->conversation->stop();
                        } else if ($text === '' || $text === 'Вчителі та предмети'){
                            $data['text'] = 'Виберіть предмет:';
                            $result = Request::sendMessage($data);
                        } else {
                            $data['text'] = "Такого предмета не існує" . $text;
                            $result = Request::sendMessage($data);
                        }
                        break;
                    }
                    $notes['subject'] = $text;
                    $text = '';
                case 1:
                    $this->conversation->update();
                    $text = $this->getSubjectsAndTeachers($notes['subject']);
                    unset($notes['state']);

                    $data = [
                        'chat_id' => $chat_id,
                        'text'    => $text,
                        'reply_markup' => $keyboard,
                    ];

                    return Request::sendMessage($data);
                    break;
            }
            return $result;
        }
    }

    private function getSubjectsAndTeachers(string $subject_name) {
        $subject = DB::selectSubjectData($subject_name);
        if (!$subject) {
            $text = "Такого предмета не існує";
        }
        TelegramLog::debug("Message", [$subject]);
        $teacher = DB::selectTeacherSubjectData($subject->name);
        if (!$teacher) {
            $text = "За цим предметом не прив'язан жоден з вчителів";
        }else{
            $teacher_data = DB::selectUserData($teacher['teacher_id']);
            TelegramLog::debug("Hello -> ", [$teacher_data]);
            $teacher_fullname = $teacher_data->first_name . " " . $teacher_data->second_name . " " . $teacher_data->middle_name;
        }
        $text = sprintf(
            "Предмет: %s\nВчитель: %s",
            $subject->name,
            $teacher_fullname !== "" ?  $teacher_fullname : $text
        );

        $this->conversation->stop();
        return $text;
    }
}
