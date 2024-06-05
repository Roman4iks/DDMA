<?php

namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class DeleteworkCommand extends TeacherCommand
{
    protected $name = 'deletework';

    protected $description = 'Видалення завдання';

    protected $usage = '/deletework';

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

        switch ($state) {
            case 0:
                $groups = [];
                foreach (DB::selectAllGroupsData() as $group) {
                    if ($group->name === "Null") {
                        continue;
                    }
                    $groups[] = $group->name;
                }
                if ($text === '' || (!in_array($text, $groups, true))) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($groups))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть групу для отримання інформації:';

                    if ($text === '' || $text === 'Видалити завдання') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такої групи не загестровано';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['group'] = $text;
                $text = '';
            case 1:
                $works = [];
                $worksId = [];
                $works_objects = DB::selectWorksData($notes['group'], $user_id);

                if (count($works_objects) === 0) {
                    $data['text'] = 'Завдань за цією групою не знайдено:';
                } else {
                    foreach ($works_objects as $work) {
                        $works[] = $work['task'];
                        $worksId[$work['task']] = $work['id'];
                    }
                }
                if ($text === '' || (!in_array($text, $works, true))) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($works))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть завдання для видалення:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такого завдання не загестровано';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['work'] = $text;
                $notes['workId'] = $worksId[$text];
                $text = '';
            case 2:
                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $work = DB::selectWorkDataById($notes['workId']);
                
                try {
                    DB::deleteWorkData($work);
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }
                $out_text = '/deletework Результат:' . PHP_EOL . $text;

                $data['text'] = $out_text . PHP_EOL . "Статус ✅";

                unset($notes['state']);

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                break;
        }
        return $result;
    }
}
