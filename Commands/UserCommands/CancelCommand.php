<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

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

        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();
            $text = 'Діалог "' . $conversation_command . '" завершено!';
        }

        return $this->removeKeyboard($text);
    }
    private function removeKeyboard(string $text): ServerResponse
    {
        return $this->replyToChat($text, [
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ]);
    }
}