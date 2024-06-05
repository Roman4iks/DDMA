<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class CancelCommand extends UserCommand
{
    protected $name = 'cancel';

    protected $description = 'Закінчити діалог з ботом';

    protected $usage = '/cancel';

    protected $version = '1.0.0';

    protected $need_mysql = true;

    public function executeNoDb(): ServerResponse
    {
        return $this->removeKeyboard('Немає діалогу');
    }

    public function execute(): ServerResponse
    {
        $text = 'Нема активних діалогів!';
        $message = $this->getMessage();
        $text    = trim($message->getText());
        $chat_id = $message->getChat()->getId();
        $user    = $message->getFrom();
        $user_id = $user->getId();

        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();
            $text = 'Діалог "' . $conversation_command . '" завершено!';
        }else {
            $text = 'Діалогу не знайдено';
        }

        $keyboard = KeyboardTelegram::getKeyboard($user_id);

        $data = [
            'chat_id' => $chat_id,
            'text'    => $text,
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}