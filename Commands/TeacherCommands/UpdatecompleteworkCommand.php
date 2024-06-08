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
use Longman\TelegramBot\Entities\InputFile;

class UpdatecompleteworkCommand extends TeacherCommand
{
    protected $name = 'updatecompletework';

    protected $description = 'Отримання надісланих завдань на перевірку та встановлення оцінки';

    protected $usage = '/updatecompletework';

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
                $completeWorks = [];
                $completeWorksId = [];
                foreach (DB::selectCompletedWorksDataWithoutGrade($user_id) as $work) {
                    $completeWorks[] = $work['task'];
                    $completeWorksId[$work['task']] = [$work['work_id'], $work['student_id'], $work['file_id']];
                }
                if ($text === '' || (!in_array($text, $completeWorks, true))) {
                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($completeWorks))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Оберіть завдання для перевірки:';

                    if ($text === '' || $text === 'Переглянути надіслані завдання') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такого завдання не загестровано';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $response = Request::getFile(['file_id' => $completeWorksId[$text][2]]);
                
                if ($response->isOk()) {
                    $data['text'] = "Йде завантаження файлу...";
                    $result = Request::sendMessage($data);

                    $file_path = $response->getResult()->getFilePath();
                    $file_url = 'https://api.telegram.org/file/bot' . $this->getTelegram()->getApiKey() . '/' . $file_path;

                    $local_file_path = $this->telegram->getDownloadPath() . '/' . basename($file_path);
                    $file_data = file_get_contents($file_url);
                    file_put_contents($local_file_path, $file_data);

                    $result = Request::sendDocument([
                        'chat_id' => $chat_id,
                        'document' => $local_file_path
                    ]);
                }
                $notes['complete_work'] = $text;
                $notes['complete_work_id'] = $completeWorksId[$text][0];
                $notes['complete_work_student_id'] = $completeWorksId[$text][1];
                $text = '';
            case 1:
                $grades = ['1', '2', '3', '4', '5'];
                if ($text === '' || (!in_array($text, $grades, true))) {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($grades))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Поставте оцінку:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Оцінка повинна бути від 1 до 5';
                        $result = Request::sendMessage($data);
                    }
                    break;
                }

                $notes['grade'] = $text;
                $text = '';
            case 2:
                $this->conversation->update();
                $keyboard = KeyboardTelegram::getKeyboard($user_id);
                $data['reply_markup'] = $keyboard;
                $out_text = '/updatecompletework Результат:' . PHP_EOL;

                try {
                    $result = DB::updateCompletedWorkData($notes['complete_work_id'], $notes['complete_work_student_id'], $notes['grade'], $user_id);
                    if ($result) {
                        $data['text'] = $out_text . "Оцінка успішно поставлена" . PHP_EOL . "Статус ✅";
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Статус ❌';
                        $result = Request::sendMessage($data);
                    }
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }

                $keyboard = KeyboardTelegram::getKeyboard($notes['complete_work_student_id']);
                $data['reply_markup'] = $keyboard;
                $data['text'] = "Вам було назначена оцінка за виконене домашне завдання:" . PHP_EOL . $notes['complete_work'] . PHP_EOL . "Оцінка:" . $notes['grade'];
                $data['chat_id'] = $notes['complete_work_student_id'];
                
                $result = Request::sendMessage($data);

                unset($notes['state']);

                $this->conversation->stop();

                break;
        }
        return $result;
    }
}
