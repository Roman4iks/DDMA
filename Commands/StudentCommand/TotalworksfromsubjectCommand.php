<?php 
namespace Longman\TelegramBot\Commands\StudentCommands;

use App\DB;
use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class TotalworksfromsubjectCommand extends StudentCommand
{
    protected $name = 'totalWorksFromSubject';

    protected $description = 'Отримати зведення за невиконаними завданнями';

    protected $usage = '/totalWorksFromSubject';

    protected $version = '1.0.0';

    protected $need_mysql = true;

    public function executeNoDb(): ServerResponse
    {
        return $this->removeKeyboard('Немає діалогу');
    }

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chatId = $message->getChat()->getId();
        $userId = $message->getFrom()->getId();

        if (DB::getUserRole($userId) !== "student") {
            return Request::sendMessage(['chat_id' => $chatId, 'text' => 'Ця команда доступна лише студентам.']);
        }

        $subjects_total = DB::getTotalUncompletedWorksFromSubjectWithStudent($userId);
        TelegramLog::debug("Get tasks deadline: ", $subjects_total);
        
        if(count($subjects_total) == 0) {
            return Request::sendMessage(['chat_id' => $chatId, 'text' => "Завдання немає"]);        
        }
        
        $message = "Усього невиконаних завдань з кожного предмета:\n";
        foreach ($subjects_total as $subject_data) {
            $message .= sprintf("Предмет: %s\nУсього завдань: %s\n",
                $subject_data['subject'],
                $subject_data['total_works'],
            );
            Request::sendMessage(['chat_id' => $chatId, 'text' => $message]);        
        }


    }
}