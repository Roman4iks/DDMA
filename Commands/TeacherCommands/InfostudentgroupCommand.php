<?php
namespace Longman\TelegramBot\Commands\TeacherCommands;

use App\DB;
use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\TeacherCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class InfostudentgroupCommand extends TeacherCommand
{
    protected $name = 'infostudentgroup';

    protected $description = 'Отримання довідки про студентів групи';

    protected $usage = '/infostudentgroup';

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

                    $data['text'] = 'Оберіть групу для отримання інформації:';

                    if ($text === '' || $text === 'Інформація по всій групі') {
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
                $this->conversation->update();
                $students = DB::selectAllStudentsDataByGroup($notes['group_id']);
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;

                if (!$students) {
                    $out_text = "Студентів не було знайдено в базі даних";
                    $data['text'] = $out_text . PHP_EOL . "Статус ❌";
                    $this->conversation->stop();
                    $result = Request::sendMessage($data);
                    break;
                }

                $data['text'] = $notes['group_message'];
                $out_text = '/infostudentgroup Результат:';
                $error_text = '';
                $result_text = '';

                $group = DB::selectGroupData($notes['group']);

                foreach ($students as $student) {
                    $student_user = DB::selectUserData($student['user_id']);

                    if ($result->isOk()) {
                        $result_text .= sprintf(
                            "\n\nФІО: %s\nСкорочена назва групи: %s\nПовна назва групи: %s\nНомер телефону: %s\nEmail: %s\nДень народження %s",
                            "$student_user->first_name $student_user->second_name $student_user->middle_name",
                            $group->name,
                            $group->fullname,
                            $student_user->phone,
                            $student_user->email ? 'Немає' : $student_user->email,
                            $student_user->birthday
                        );
                    }
                }
                $data['chat_id'] = $user_id;

                $out_text .= $result_text . PHP_EOL . $error_text;

                unset($notes['state']);

                $data['text'] = $out_text . PHP_EOL . "Статус ✅";

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                break;
        }
        return $result;
    }
}
