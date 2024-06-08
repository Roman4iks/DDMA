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

class DeletesubjectCommand extends TeacherCommand
{
    protected $name = 'deletesubject';

    protected $description = 'Видалення предмета';

    protected $usage = '/deletesubject';

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
                $subjects = ["/cancel"];
                foreach (DB::selectAllSubjectsData() as $subject) {
                    $subjects[] = $subject->name;
                }
                if ($text === '' || (!in_array($text, $subjects, true))) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($subjects))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть предмет для видалення:';

                    if ($text === '' || $text === 'Видалити предмет') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такого предмета не загестровано';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['subject'] = $text;
                $text = '';
            case 1:
                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $out_text = '/deletesubject Результат:' . PHP_EOL;
                $subject = DB::selectSubjectData($notes['subject']);
                try {
                    $result = DB::deleteSubjectData($subject);
                    if($result){
                        $data['text'] = $out_text . PHP_EOL . "Статус ✅";
                    }else{
                        $data['text'] = $out_text . PHP_EOL . "Щось пішло не так:" . PHP_EOL . "Статус ❌" . $result;
                    }
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
