<?php
namespace App\Commands\UserCommands;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class NewsCommand extends UserCommand
{
    protected $name = 'news';

    protected $description = 'Отримати новини колледжу';

    protected $usage = '/news';

    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        return $this->replyToChat(
            'Hi there!' . PHP_EOL .
            'Type /help to see all commands!'
        );
    }
}