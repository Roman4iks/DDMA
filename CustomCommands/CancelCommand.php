<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;

class CancelCommand extends UserCommand
{
    protected $name = 'cancel';

    protected $description = 'Закончить диалог с ботом';

    protected $usage = '/cancel';

    protected $version = '0.3.0';

    protected $need_mysql = true;

    public function executeNoDb(): ServerResponse
    {
        return $this->removeKeyboard('Нет диалога');
    }

    public function execute(): ServerResponse
    {
        $text = 'Нет активных диалогов!';

        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();
            $text = 'Диалог "' . $conversation_command . '" завершен!';
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