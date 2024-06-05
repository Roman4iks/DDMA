<?php
namespace Longman\TelegramBot\Commands\StudentCommands;

use App\class\CompletedWork;
use App\DB;
use App\utils\KeyboardTelegram;
use DateTime;
use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class SendworkCommand extends StudentCommand
{
    protected $name = 'sendWork';

    protected $description = 'Відправити завдання на перевірку';

    protected $usage = '/sendWork';

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

        $download_path = $this->telegram->getDownloadPath();
        if (!is_dir($download_path)) {
            return $this->replyToChat('Упс помилка, будь-ласка звернитесь до адміністратора з кодом помилки (Direction 403)');
        }

        if (DB::getUserRole($user_id) !== "student") {
            return Request::sendMessage(['chat_id' => $chat_id, 'text' => 'Ця команда доступна тільки для студентів.']);
        }

        $data = [
            'chat_id'      => $chat_id,
            'reply_markup' => new Keyboard(["/cancel"]),
        ];

        if ($chat->isGroupChat() || $chat->isSuperGroup()) {
            $data['reply_to_message_id'] = $message->getMessageId();
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        $state = $notes['state'] ?? 0;
        $result = Request::emptyResponse();

        TelegramLog::debug('Start send Task');
        switch ($state) {
            case 0:
                TelegramLog::debug('Start Register subject');
                $subjects = [];
                foreach (DB::selectAllSubjectsData() as $subject) {
                    $subjects[] = $subject->name;
                }

                $subjects[] = "/cancel";
                if ($text === '' || (!in_array($text, $subjects, true))) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($subjects))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Выберите предмет:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такого предмета не зареєстровано на базі. Будь ласка, виберіть існуючий предмет';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['subject'] = $text;
                $text = '';
                TelegramLog::debug('Success teacher Subject:', $notes);
            case 1:
                TelegramLog::debug('Start Register works');
                $works_sub = [];
                $works = [];
                foreach (DB::getUncompletedTasksDetailsForStudent($user_id) as $work) {
                    if ($work['name'] == $notes['subject']) {
                        $works_sub[] = substr($work['task'], 0, 40);
                        $works[substr($work['task'], 0, 40)] = $work['task'];
                    }
                }

                if (count($works) <= 0) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($subjects))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Завдань немає. Будь ласка, виберіть інший предмет:';
                    $result = Request::sendMessage($data);

                    break;
                }

                TelegramLog::debug('works', $works);
                if ($text === '' || (!in_array($text, $works_sub, true))) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($works_sub))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Виберіть завдання:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такого завдання не існує';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['task'] = $works[$text];
                $text = '';
                TelegramLog::debug('Success send work:', $notes);

            case 2:
                $message_type = $message->getType();
                $file_id = '';
                TelegramLog::debug('Start send File');
                if (!(in_array($message_type, ['document', 'photo'], true))) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['text'] = 'Будь ласка, завантажте файл. Це може бути фото, або документ';
                    $result = Request::sendMessage($data);
                    break;
                } else{
                    $doc = $message->{'get' . ucfirst($message_type)}();
                    
                    ($message_type === 'photo') && $doc = end($doc);
                    
                    $file_id = $doc->getFileId();
                    $file    = Request::getFile(['file_id' => $file_id]);
                    if ($file->isOk() && Request::downloadFile($file->getResult())) {
                        $data['text'] = $message_type . ' file is located at: ' . $download_path . '/' . $file->getResult()->getFilePath();
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Не вдалося завантажити.';
                        $result = Request::sendMessage($data);
                    }
                }
                $notes['file_id'] = $file_id;
                
                TelegramLog::debug('Success send File', $notes);
            case 3:
                TelegramLog::debug('Finish create Subject');

                $this->conversation->update();
                $out_text = '/sendWork результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }
                $date = new DateTime();
                try {
                    $work_data = DB::selectWorkData($notes['task'])->id;
                    DB::insertCompletedWorkData(new CompletedWork($user_id, 
                    $work_data, 
                    $date->format('Y-m-d H:i:s')));
                } catch (\PDOException $e) {
                    $data['text'] = 'Виникла помилка при відправленні завдання';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }

                $this->conversation->stop();

                $data['text'] = $out_text . PHP_EOL;
                $data['reply_markup'] = KeyboardTelegram::getKeyboard($user_id);

                $result = Request::sendMessage($data);

                TelegramLog::debug("Finish registration", $notes);
                break;
        }
        return $result;
    }
}
