<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class StartCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Головне меню бота, та вітання';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user    = $message->getFrom();
        $user_id = $user->getId();

        $keyboard = KeyboardTelegram::getKeyboard($user_id);

        $data = [
            'chat_id' => $chat_id,
            'text'    => 'Вітаю вас у боті технікуму ДДМА' . PHP_EOL . 'Оберіть групу команд:',
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}