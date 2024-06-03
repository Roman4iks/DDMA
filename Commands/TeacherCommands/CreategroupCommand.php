<?php
namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\class\Group;
use App\DB;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class CreategroupCommand extends TeacherCommand
{
    protected $name = 'createGroup';

    protected $description = 'Створити группу';

    protected $usage = '/createGroup Name';

    protected $version = '1.0.0';

    protected $conversation;

    protected $need_mysql = true;

    public function executeNoDb(): ServerResponse
    {
        return $this->removeKeyboard('Немає діалогу');
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

        TelegramLog::debug('Start create Group');
        
        switch ($state) {
            case 0:
                TelegramLog::debug('Start Name Group');
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть скорочену назву групи:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['group_name'] = $text;
                $text = '';
                TelegramLog::debug('Success group Name:', $notes);
            case 1:
                TelegramLog::debug('Start Full name Group');
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть повну назву групи:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['group_fullname'] = $text;
                $text = '';
                TelegramLog::debug('Success group fullName:', $notes);
            case 2:
                    TelegramLog::debug('Start link Group');
                    if ($text === '') {
                        $notes['state'] = 2;
                        $this->conversation->update();
    
                        $data['text'] = 'Напишіть посилання на групу в телеграмі:';
    
                        $result = Request::sendMessage($data);
                        break;
                    }
    
                    $notes['group_link'] = $text;
                    $text = '';
                    TelegramLog::debug('Success group link:', $notes);
            case 3:
                TelegramLog::debug('Finish create Group');
    
                $this->conversation->update();
                $out_text = '/createGroup Результат:' . PHP_EOL;
                unset($notes['state']);
    
                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                try {
                    DB::insertGroupData(new Group($notes['group_name'], $notes['group_fullname'], $notes['group_link']));
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }

                $data['text'] = $out_text . PHP_EOL . "Статус ✅";

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                TelegramLog::debug("Finish registration", $notes);
                break;
        }
        return $result;
    }
}