<?php 
namespace Longman\TelegramBot\Commands\StudentCommands;

use App\DB;
use App\utils\Converter;
use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;

class TasksuncompletedthisweekCommand extends StudentCommand
{
    protected $name = 'tasksUncompletedThisWeek';

    protected $description = 'Отримати невиконані завдання на цей тиждень';

    protected $usage = '/tasksUncompletedThisWeek';

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

        $works = DB::getUncompletedTasksDetailsFromStudentWeek($userId);
        TelegramLog::debug("Get uncompleted works this week: ", $works);
        
                
        if(count($works) == 0) {
            return Request::sendMessage(['chat_id' => $chatId, 'text' => "Завдання немає"]);        
        }

        $message = "Не виконані завдання на цей тиждень:\n";
        foreach ($works as $i => $work) {
            $message = "Робота : ". $i + 1 . PHP_EOL;
            $deadlineDays = Converter::daysUntilDeadline($work['end']);
            $deadlineMessage = $deadlineDays > 0 ? "Залишилось " . $deadlineDays . " днів" : "Просрочено на " . $deadlineDays . " днів" ;
            $message .= sprintf("Завдання: %s\nПредмет: %s\nПочаток: %s (%s)\nКінець: %s\nВчитель: %s\n",
                $work['task'],
                $work['name'],
                $work['start'],
                $deadlineMessage,
                $work['end'],
                $work['teacher_fullname']
            );
            Request::sendMessage(['chat_id' => $chatId, 'text' => $message]);        
        }


    }
}