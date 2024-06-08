<?php

namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\class\Pair;
use App\DB;
use App\utils\KeyboardTelegram;
use App\utils\Validator;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class CreatepairCommand extends TeacherCommand
{
    protected $name = 'createpair';

    protected $description = 'Створити пару';

    protected $usage = '/createpair';

    protected $version = '1.0.0';

    protected $conversation;

    protected $need_mysql = true;

    public function executeNoDb(): ServerResponse
    {
        return $this->removeKeyboard('Нет диалога');
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

        TelegramLog::debug('Start create Pair');

        switch ($state) {
            case 0:
                TelegramLog::debug('Start pair Group');

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

                    $data['text'] = 'Виберіть групу:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такої групи не зареєстровано у базі. Будь ласка, виберіть існуючу групу';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['group_id'] = $groupsId[$text];
                $text = '';
                TelegramLog::debug('Success pair Group:', $notes);
            case 1:
                TelegramLog::debug('Start pair Subject');

                $subjects = [];
                $subjectsId = [];
                foreach (DB::selectAllSubjectsData() as $subject) {
                    $subjects[] = $subject->name;
                    $subjectsId[$subject->name] = $subject->id;
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

                $notes['subject_id'] = $subjectsId[$text];
                $text = '';
                TelegramLog::debug('Success pair Subject:', $notes);
            case 2:
                TelegramLog::debug('Start pair Start');
                if ($text === '') {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть дату початку пари пари за форматом 08:00:';

                    $result = Request::sendMessage($data);
                    break;
                }

                if (!Validator::validateTime($text)) {
                    $data['text'] = 'Неправильний формат часу';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['start'] = date('H:i', strtotime($text));
                $text = '';
                TelegramLog::debug('Success pair Start:', $notes);
            case 3:
                TelegramLog::debug('Start pair End');
                if ($text === '') {
                    $notes['state'] = 3;
                    $this->conversation->update();

                    $data['text'] = 'Напишіть дату закінчення пари за форматом 08:00:';

                    $result = Request::sendMessage($data);
                    break;
                }

                if (!Validator::validateTime($text)) {
                    $data['text'] = 'Неправильний формат часу';

                    $result = Request::sendMessage($data);
                    break;
                }

                $notes['end'] = date('H:i', strtotime($text));
                $text = '';
                TelegramLog::debug('Success pair End:', $notes);

            case 4:
                $week = ['Понеділок', 'Вівторок', 'Середа', 'Четвер', "П'ятниця", 'Субота', 'Неділя'];
                if ($text === '' || (!in_array($text, $week, true))) {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($week))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Поставте оцінку:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Оберіть тиждень';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['week'] = array_search($text, $week) + 1;
                $text = '';
            case 5:
                $weekUp = ["Верхній", "Ніжній", "Завжди"];
                if ($text === '' || (!in_array($text, $weekUp, true))) {
                    $notes['state'] = 5;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($weekUp))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Тиждень вверхній чи ніжній:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Оберіть між Верхній та Ніжній';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['weekUp'] = array_search($text, $weekUp);
                $text = '';
            case 6:
                TelegramLog::debug('Finish create Pair');

                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $out_text = '/createPair Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                try {
                    if ($notes['weekUp'] === 2) {
                        $result = DB::insertPairData($notes['subject_id'], $user_id, $notes['group_id'], $notes['start'], $notes['end'], $notes['week'], 0);
                        $result = DB::insertPairData($notes['subject_id'], $user_id, $notes['group_id'], $notes['start'], $notes['end'], $notes['week'], 1);
                    } else {
                        $result = DB::insertPairData($notes['subject_id'], $user_id, $notes['group_id'], $notes['start'], $notes['end'], $notes['week'], $notes['weekUp']);
                    }
                    if ($result) {
                        $data['text'] = $out_text . PHP_EOL . "Статус ✅";
                    } else {
                        $data['text'] = $out_text . PHP_EOL . "Щось пішло не так:" . PHP_EOL . "Статус ❌" . $result;
                    }
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                TelegramLog::debug("Finish registration", $notes);
                break;
        }
        return $result;
    }
}
