<?php
namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\class\Work;
use App\DB;
use App\utils\KeyboardTelegram;
use App\utils\Validator;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class CreateworkCommand extends TeacherCommand
{
    protected $name = 'createWork';

    protected $description = 'Створити завдання';

    protected $usage = '/createWork';

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

        TelegramLog::debug('Start create Work');

        switch ($state) {
            case 0:
                TelegramLog::debug('Start Task Work');
                if ($text === '') {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть завдання:';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['work_task'] = $text;
                $text = '';
                TelegramLog::debug('Success work Task:', $notes);
            case 1:
                TelegramLog::debug('Start work Subject');

                $subjects = [];
                foreach (DB::selectAllSubjectsData() as $subject) {
                    $subjects[] = $subject->name;
                }

                if ($text === '' || (!in_array($text, $subjects, true))) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($subjects))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Виберіть предмет:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такого предмета не зареєстровано на базі. Будь ласка, виберіть існуючий предмет';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['work_subject'] = $text;
                $text = '';
                TelegramLog::debug('Success work Subject:', $notes);
            case 2:
                TelegramLog::debug('Start work Group');

                $groups = [];
                foreach (DB::selectAllGroupsData() as $group) {
                    $groups[] = $group->name;
                }

                if ($text === '' || (!in_array($text, $groups, true))) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($groups))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Виберіть групу:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такої групи не зареєстровано у базі. Будь ласка, виберіть існуючу групу';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['work_group'] = $text;
                $text = '';
                TelegramLog::debug('Success work Group:', $notes);
            case 3:
                TelegramLog::debug('Start work Start');
                if ($text === '') {
                    $notes['state'] = 3;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть дату початку публікації завдання за форматом 2001-12-31 08:00:';

                    $result = Request::sendMessage($data);
                    break;
                }

                if (!Validator::validateDateAndTime($text)) {
                    $data['text'] = 'Неправильний формат дати';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['work_start'] = date('Y-m-d H:i', strtotime($text));
                $text = '';
                TelegramLog::debug('Success work Start:', $notes);
            case 4:
                TelegramLog::debug('Start work End');
                if ($text === '') {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть дату закінчення завдання за форматом 2001-12-31 08:00:';

                    $result = Request::sendMessage($data);
                    break;
                }

                if (!Validator::validateDateAndTime($text)) {
                    $data['text'] = 'Неправильний формат дати';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['work_end'] = date('Y-m-d H:i', strtotime($text));
                $text = '';
                TelegramLog::debug('Success work End:', $notes);

            case 5:
                TelegramLog::debug('Finish create Group');

                $this->conversation->update();
                $out_text = '/createWork Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                try {
                    DB::insertWorkData(new Work($notes['work_task'], $notes['work_subject'], $user_id, $notes['work_group'], $notes['work_start'], $notes['work_end']));
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
