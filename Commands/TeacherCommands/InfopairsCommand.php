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

class InfopairsCommand extends TeacherCommand
{
    protected $name = 'infopairs';

    protected $description = 'Отримати всі пари';

    protected $usage = '/infopairs';

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
                $groupsId = [];
                foreach (DB::selectAllGroupsData() as $group) {
                    if ($group->name === "Null") {
                        continue;
                    }
                    $groups[] = $group->name;
                    $groupsId[$group->name] = $group->id;
                }
                if ($text === '' || (!in_array($text, $groups, true))) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($groups))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть групу для отримання інформації:';

                    if ($text === '' || $text === 'Переглянути пари по групі') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такої групи не загестровано';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['group'] = $text;
                $notes['group_id'] = $groupsId[$notes['group']];
                $text = '';
            case 1:

                TelegramLog::debug("HELo", $notes);
                $pairs = DB::selectAllPairsDataByGroup($notes['group_id']);

                if($pairs){
                    foreach($pairs as $index => $pair){
                        $text .= sprintf("Пара: %s\nПредмет: %s\nВчитель: %s\nПочаток: %s\nКінець: %s\nТиждень: %s",
                        $index + 1,
                        $pair['subject_name'],
                        $pair['teacher_fullname'],
                        $pair['start'],
                        $pair['end'],
                        $pair['top_week'] ? "Верхній" : "Ніжній");
                    }
                }else{
                    $text = "Пар не загестровано на цій групі";
                }
        
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
        
                $data = [
                    'chat_id' => $chat_id,
                    'text'    => $text,
                    'reply_markup' => $keyboard,
                ];

                $this->conversation->stop();
        
                return Request::sendMessage($data);
        }
        return $result;
    }
}