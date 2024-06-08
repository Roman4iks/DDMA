<?php

namespace Longman\TelegramBot\Commands\StudentCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class PairCommand extends StudentCommand
{
    protected $name = 'pair';

    protected $description = 'Отримати довідку про пару';

    protected $usage = '/pair';

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
        $pairs = DB::selectPairsDataByGroupId($user_data['group_id']);

        if (!$pairs) {
            $text = "Пар немає";
        } else {
            foreach ($pairs as $index => $pair) {
                $text .= sprintf(
                    "Пара: %s\nПредмет: %s\nВчитель: %s\nПочаток: %s\nКінець: %s\nТиждень: %s",
                    $index + 1,
                    $pair['subject_name'],
                    $pair['teacher_fullname'],
                    $pair['start'],
                    $pair['end'],
                    $pair['top_week'] ? "Верхній" : "Ніжній"
                );
            }
        }

        $keyboard = KeyboardTelegram::getKeyboard($user_id);

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}
