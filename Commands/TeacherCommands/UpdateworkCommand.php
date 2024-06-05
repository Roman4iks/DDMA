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

class UpdateworkCommand extends TeacherCommand
{
    protected $name = 'updatework';

    protected $description = 'Оновити завдання';

    protected $usage = '/updatework';

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
                foreach (DB::selectAllGroupsData() as $group) {
                    if ($group->name === "Null") {
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

                    if ($text === '' || $text === 'Оновити завдання') {
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
                $works = [];
                $worksId = [];
                $works_objects = DB::selectWorksData($notes['group'], $user_id);

                if (count($works_objects) === 0) {
                    $data['text'] = 'Завдань за цією групою не знайдено:';
                } else {
                    foreach ($works_objects as $work) {
                        $works[] = $work['task'];
                        $worksId[$work['task']] = $work['id'];
                    }
                }
                if ($text === '' || (!in_array($text, $works, true))) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($works))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть завдання для видалення:';

                    if ($text === '') {

                        if ($work['subject_id']) {
                            $subject = DB::selectSubjectDataById($work['subject_id']);
                        } else {
                            $subject = null;
                        }
                        $text .= sprintf(
                            "\n\nЗавдання: %s\nПредмет: %s\nПочаток: %s\nКінець: %s",
                            $work['task'],
                            $subject === null ? "Не вказано" : $subject->name,
                            $work['start'],
                            $work['end'],
                        );

                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такого завдання не загестровано';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['work'] = $text;
                $notes['workId'] = $worksId[$text];
                $text = '';
            case 2:
                $worksColumn = [];
                foreach (DB::selectColumnWorkData() as $column) {
                    if ($column['COLUMN_NAME'] === 'id' || $column['COLUMN_NAME'] === 'teacher_id') {
                        continue;
                    }
                    $worksColumn[] = $column['COLUMN_NAME'];
                }

                if ($text === '' || (!in_array($text, $worksColumn, true))) {
                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($worksColumn))
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
            case 3:
                if ($notes['column'] === 'group_id') {
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
                        $notes['state'] = 3;
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
                    $subjects = [];
                    $subjectsId = [];
                    foreach (DB::selectAllSubjectsData() as $subject) {
                        $subjects[] = $subject->name;
                        $subjectsId[$subject->name] = $subject->id;
                    }
                    if ($text === '' || (!in_array($text, $subjects, true))) {
                        $notes['state'] = 3;
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
                } else if($notes['column'] === 'start' || $notes['column'] === 'end'){
                    if ($text === '') {
                        $notes['state'] = 3;
                        $this->conversation->update();
    
                        $data['text'] = 'Напишіть дату завдання за форматом 2001-12-31 08:00:';
    
                        $result = Request::sendMessage($data);
                        break;
                    }
    
                    if (!Validator::validateDateAndTime($text)) {
                        $data['text'] = 'Неправильний формат дати';
    
                        $result = Request::sendMessage($data);
                        break;
                    }
    
                    $notes['new_data'] = date('Y-m-d H:i', strtotime($text));
                    $text = '';
                }else {
                    if ($text === '') {
                        $notes['state'] = 3;
                        $this->conversation->update();

                        $data['text'] = 'Введіть нові данні для предмета - ' . $notes['work'] . " поля " . $notes['column'];

                        $result = Request::sendMessage($data);
                        break;
                    }
                    $notes['new_data'] = $text;
                    $text = '';
                }
            case 4:
                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                $work = DB::selectWorkDataById($notes['workId']);

                $out_text = 'Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                try {
                    DB::updateWorkData($notes['column'], $notes['new_data'], $work);
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
