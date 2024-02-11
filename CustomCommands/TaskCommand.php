<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\TelegramLog;

class TaskCommand extends UserCommand
{
    protected $name = 'task';
    protected $description = 'Displays tasks';
    protected $usage = '/task';

    public function execute() : ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        // Assuming DB::getPdo() returns a PDO instance connected to your database
        $query = DB::getPdo()->query('SELECT * FROM task');
        $results = $query->fetchAll();

        TelegramLog::debug(''. json_encode($results));

        $text = '';
        foreach ($results as $row) {
            $text .= sprintf('Task %s: %s %s %s %s' . PHP_EOL , $row['task_id'], $row['description'], $row['task_name'], $row['deadline'], $row['status'], $row['priority']);
        }

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
        ];

        return Request::sendMessage($data);
    }
}