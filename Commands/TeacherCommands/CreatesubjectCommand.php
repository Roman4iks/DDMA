<?php
namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\class\Subject;
use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class CreatesubjectCommand extends TeacherCommand
{
    protected $name = 'createsubject';

    protected $description = 'Створити предмет';

    protected $usage = '/createsubject';

    protected $version = '1.0.0';

    protected $conversation;

    protected $need_mysql = true;

    public function executeNoDb(): ServerResponse
    {
        return $this->removeKeyboard('Нет диалога');
    }

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat    = $message->getChat();
        $user    = $message->getFrom();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if (DB::getUserRole($user_id) !== "teacher") {
            return Request::sendMessage(['chat_id' => $chat_id, 'text' => 'Ця команда доступна лише для вчителів.']);
        }

        $data = [
            'chat_id'      => $chat_id,
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        $state = $notes['state'] ?? 0;
        $result = Request::emptyResponse();

        TelegramLog::debug('Start create Subject');

        switch ($state) {
            case 0:
                TelegramLog::debug('Start subject Name');
                if ($text === '' || $text === "Створити предмет") {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть назву предмета:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['subject_name'] = $text;
                $text = '';
                TelegramLog::debug('Success subject Name:', $notes);
            case 1:
                TelegramLog::debug('Finish create Subject');

                $this->conversation->update();
                $out_text = '/createSubject Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                try {
                    DB::insertSubjectData(new Subject($notes['subject_name']));
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }

                $data['text'] = $out_text . PHP_EOL . "Статус ✅";
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                TelegramLog::debug("Finish registration", $notes);
                break;
        }
        return $result;
    }
}
