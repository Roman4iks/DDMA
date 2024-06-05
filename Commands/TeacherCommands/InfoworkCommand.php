<?php

namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class InfoworkCommand extends TeacherCommand
{
    protected $name = 'infowork';

    protected $description = 'Отримання довідки про надісланні завдання';

    protected $usage = '/infowork';

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

        if ($chat->isWorkChat() || $chat->isSuperWork()) {
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

                    if ($text === '' || $text === 'Отримати інформацію про завдання') {
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

                $works = DB::selectWorksData($notes['group'], $user_id);

                if (count($works) === 0) {
                    $text = "Завдань за цією групою немає";
                } else {
                    foreach ($works as $work) {
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
                    }
                }
                $out_text = '/infowork Результат:' . PHP_EOL . $text;

                unset($notes['state']);

                $data['text'] = $out_text;

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                break;
        }
        return $result;
    }
}
