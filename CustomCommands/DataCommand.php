<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class DataCommand extends UserCommand
{
    protected $name = 'data';

    protected $description = 'Retrieve data from MySQL';

    protected $usage = '/data';

    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();

        // Подключение к базе данных
        $mysqli = new \mysqli("localhost", "root", "root", "diploma");

        // Проверка соединения
        if ($mysqli->connect_errno) {
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => 'Ошибка подключения к базе данных: ' . $mysqli->connect_error
            ]);
        }

        // Выполнение запроса к базе данных
        $result = $mysqli->query("SELECT * FROM user");

        // Проверка наличия данных
        if ($result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->close();

            // Отправка данных пользователю в Телеграм
            $data_text = '';
            foreach ($rows as $row) {
                $data_text .= implode(', ', $row) . PHP_EOL;
            }

            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'    => $data_text
            ]);
        } else {
            return Request::sendMessage([
                'chat_id' => $chat_id,
                'text'=> 'Ошибка выполнения запроса: ' . $mysqli->error
            ]);
        }

        // Закрытие соединения с базой данных
        $mysqli->close();
    }
}
