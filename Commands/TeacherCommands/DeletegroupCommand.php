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

class DeletegroupCommand extends TeacherCommand
{
    protected $name = 'deletegroup';

    protected $description = 'Видалення групи';

    protected $usage = '/deletegroup';

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
                $groups = ["/cancel"];
                $groupsId = [];
                foreach (DB::selectAllGroupsData() as $group) {
                    if($group->name === "Null"){
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

                    $data['text'] = 'Оберіть групу для видалення:';

                    if ($text === '' || $text === 'Видалити групу') {
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
                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $out_text = '/deletegroup Результат:' . PHP_EOL;
                $group = DB::selectGroupData($notes['group']);
                try {
                    TelegramLog::debug("DELETE HERE", [$group]);
                    DB::deleteGroupData($group);
                    TelegramLog::debug("DELETE END", [$group]);
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }

                unset($notes['state']);

                $data['text'] = $out_text . PHP_EOL . "Статус ✅";

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                break;
        }
        return $result;
    }
}
