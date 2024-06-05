<?php
namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class InfogroupCommand extends TeacherCommand
{
    protected $name = 'infogroup';

    protected $description = 'Отримання довідки про групу';

    protected $usage = '/infogroup';

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
        
        $teacher_user = DB::selectUserData($user_id);

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
                    if($group->name === "Null"){
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

                    if ($text === '' || $text === 'Показати довідку по групі') {
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
                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $group = DB::selectGroupData($notes['group']);
                $teacher_data = DB::selectTeacherDataByGroup($group->id);

                $teacher_user = DB::selectUserData($teacher_data['user_id']);
                $teacher_fullname = $teacher_user->first_name . " " . $teacher_user->second_name . " " . $teacher_user->middle_name;
                
                $text = sprintf(
                    "Назва групи: %s\nПовна назва групи: %s\nПосилання на групу телеграм: %s\nКласний керівник: %s",
                    $group->name,
                    $group->fullname,
                    $group->link ? "Немає" : $group->link,
                    $teacher_fullname === "" ? "Немає" : $teacher_fullname
                );
                
                $out_text = '/infogroup Результат:' . PHP_EOL . $text;

                unset($notes['state']);

                $data['text'] = $out_text . PHP_EOL . "Статус ✅";

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                break;
        }
        return $result;
    }
}
