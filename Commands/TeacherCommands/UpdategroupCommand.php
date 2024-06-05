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

class UpdategroupCommand extends TeacherCommand
{
    protected $name = 'updategroup';

    protected $description = 'Оновити группу';

    protected $usage = '/updategroup';

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

                    $data['text'] = 'Оберіть групу для оновлення даних:';

                    if ($text === '' || $text === 'Оновити групу') {
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
                $groupsColumn = [];
                foreach (DB::selectColumnGroupData() as $column) {
                    if($column['COLUMN_NAME'] === 'id'){
                        continue;
                    }
                    $groupsColumn[] = $column['COLUMN_NAME'];
                }
                
                if ($text === '' || (!in_array($text, $groupsColumn, true))) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($groupsColumn))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Поле, яке хочете змінити:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['column'] = $text;
                $text = '';
            case 2:
                if ($text === '') {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['text'] = 'Введіть нові данні для группи - ' . $notes['group'] . " поля " . $notes['column'];

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['new_data'] = $text;
                $text = '';
            case 3:
                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $out_text = 'Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                try {
                    DB::updateGroupData($notes['column'], $notes['new_data'], $notes['group']);
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
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
