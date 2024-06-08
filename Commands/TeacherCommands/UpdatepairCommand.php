<?php

namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use App\utils\Validator;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class UpdatepairCommand extends TeacherCommand
{
    protected $name = 'updatepair';

    protected $description = 'Оновити пару';

    protected $usage = '/updatepair';

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

        if ($chat->isPairChat() || $chat->isSuperPair()) {
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        $state = $notes['state'] ?? 0;
        $result = Request::emptyResponse();

        switch ($state) {
            case 0:
                $groups = ['/cancel'];
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

                    if ($text === '' || $text === 'Оновити пару') {
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
                TelegramLog::debug('Start pair Subject');

                $subjects = ['/cancel'];
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
                $week = ['Понеділок', 'Вівторок', 'Середа', 'Четвер', "П'ятниця", 'Субота', 'Неділя'];
                if ($text === '' || (!in_array($text, $week, true))) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($week))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть тиждень:';

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
            case 3:
                $pairs = [];
                $pairsId = [];
                foreach (DB::selectAllPairsDataByGroup($notes['group_id'], $notes['subject_id']) as $pair) {
                    if ($pair) {
                        $week_top = $pair['top_week'] === 0 ? 'Верхня' : 'Ніжня';
                        $pairs[] = $pair['subject_name'] . " час " . substr($pair['start'], 0, -3) . ' - ' . substr($pair['end'], 0, -3) . ' (' . $week_top . ')';
                        TelegramLog::debug("HERE", $pair);
                        $pairsId[$pair['subject_name'] . " час " . substr($pair['start'], 0, -3) . ' - ' . substr($pair['end'], 0, -3) . ' (' . $week_top . ')'] = $pair['id'];
                    }else{
                        $data['text'] = 'Пар за цими даними немає:';
                        $result = Request::sendMessage($data);
                        $notes['state'] = 0;
                        break;
                    }
                }
                if ($text === '' || (!in_array($text, $pairs, true))) {
                    $notes['state'] = 3;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($pairs))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть пару для оновлення даних:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такої пари не загестровано';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }
                $notes['pair'] = $text;
                $notes['pair_id'] = $pairsId[$text];
                TelegramLog::debug("MESSAGE", $notes);
                $text = '';
            case 4:
                $pairsColumn = ['/cancel'];
                foreach (DB::selectColumnPairData() as $column) {
                    if ($column['COLUMN_NAME'] === 'id' || $column['COLUMN_NAME'] === 'teacher_id') {
                        continue;
                    }
                    $pairsColumn[] = $column['COLUMN_NAME'];
                }

                if ($text === '' || (!in_array($text, $pairsColumn, true))) {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($pairsColumn))
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
            case 5:
                if ($notes['column'] === 'group_id') {
                    $groups = ['/cancel'];
                    $groupsId = [];
                    foreach (DB::selectAllGroupsData() as $group) {
                        if ($group->name === "Null") {
                            continue;
                        }
                        $groups[] = $group->name;
                        $groupsId[$group->name] = $group->id;
                    }

                    if ($text === '' || (!in_array($text, $groups, true))) {
                        $notes['state'] = 5;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard($groups))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = 'Оберіть групу:';

                        if ($text === '' || $text === 'group_id') {
                            $result = Request::sendMessage($data);
                        } else {
                            $data['text'] = 'Такої групи не загестровано';
                            $result = Request::sendMessage($data);
                        }
                        break;
                    }
                    $notes['new_data'] = $groupsId[$text];
                    $text = '';
                } else if ($notes['column'] === 'subject_id') {
                    $subjects = ['/cancel'];
                    $subjectsId = [];
                    foreach (DB::selectAllSubjectsData() as $subject) {
                        $subjects[] = $subject->name;
                        $subjectsId[$subject->name] = $subject->id;
                    }
                    if ($text === '' || (!in_array($text, $subjects, true))) {
                        $notes['state'] = 5;
                        $this->conversation->update();

                        $data['reply_markup'] = (new Keyboard($subjects))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);

                        $data['text'] = 'Оберіть предмет для оновлення даних:';

                        if ($text === '' || $text === 'subject_id') {
                            $result = Request::sendMessage($data);
                        } else {
                            $data['text'] = 'Такого предмета не загестровано';
                            $result = Request::sendMessage($data);
                        }
                        break;
                    }
                    $notes['new_data'] = $subjectsId[$text];
                    $text = '';
                } else if ($notes['column'] === 'start' || $notes['column'] === 'end') {
                    if ($text === '') {
                        $notes['state'] = 5;
                        $this->conversation->update();

                        $data['text'] = 'Напишіть час пари за форматом 08:00:';

                        $result = Request::sendMessage($data);
                        break;
                    }

                    if (!Validator::validateTime($text)) {
                        $data['text'] = 'Неправильний формат часу';

                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['new_data'] = date('H:i', strtotime($text));
                    $text = '';
                } else {
                    if ($text === '') {
                        $notes['state'] = 5;
                        $this->conversation->update();

                        $data['text'] = 'Введіть нові данні для предмета - ' . $notes['pair'] . " поля " . $notes['column'];

                        $result = Request::sendMessage($data);
                        break;
                    }
                    $notes['new_data'] = $text;
                    $text = '';
                }
            case 6:
                $this->conversation->update();
                TelegramLog::debug("MESSAGE", $notes);
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $out_text = 'Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }
                

                try {
                    $result = DB::updatePairData($notes['column'], $notes['new_data'], $notes['pair_id'], $user_id);
                    if ($result) {
                        $data['text'] = $out_text . PHP_EOL . "Статус ✅";
                    } else {
                        $data['text'] = $out_text . PHP_EOL . "Щось пішло не так:" . PHP_EOL . "Статус ❌" . $result;
                    }
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    $this->conversation->stop();
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
