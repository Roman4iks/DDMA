<?php
namespace Longman\TelegramBot\Commands\StudentCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

// TODO
class ConsultregisterCommand extends StudentCommand
{
    protected $name = 'consultregister';

    protected $description = 'Записатися на консультацію';

    protected $usage = '/consultregister';

    protected $private_only = false;

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
                $data_objects = DB::selectAllTeacherData();

                $teachers = [];
                $teachersId = [];
                foreach ($data_objects as $teacher) {
                    $teachers[] = $teacher['teacher_fullname'];
                    $teachersId[$teacher['teacher_fullname']] = $teacher['user_id'];
                }
                
                if ($text === '' || (!in_array($text, $teachers, true))) {
                    $notes['state'] = 0;
                    $this->conversation->update();
                    
                    $data['reply_markup'] = (new Keyboard($teachers))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = "Виберіть вчителя";

                    if ($text === '' || $text === "Записатися на консультацію") {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Вчителя не знайдено в базі даних:';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }
                $notes['teacher'] = $text;
                $notes['teacher_id'] = $teachersId[$text];
                $text = '';
            case 1:
                if ($text === '') {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть дату та час консультації (Можете додати текст):';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['consult'] = $text;
                $text = '';
                
            case 2:
            
                $this->conversation->update();
                $student = DB::selectUserData($user_id);

                $data['text'] = "До вас записався на консультацію " . $student->first_name . ' ' . $student->second_name . PHP_EOL . $notes['consult'];
                $data['chat_id'] = $notes['teacher_id'];
  

                Request::sendMessage($data);
                
                $out_text = '/consultregister Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;
                $data['text'] = $out_text . PHP_EOL . "Статус ✅";

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                break;
        }
        return $result;
    }
}