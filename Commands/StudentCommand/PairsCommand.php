<?php
namespace Longman\TelegramBot\Commands\StudentCommands;

use Longman\TelegramBot\Commands\StudentCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class PairsCommand extends StudentCommand
{
    protected $name = 'pair';

    protected $description = 'Отримати довідку про пари';

    protected $usage = '/pair';

    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        return $this->replyToChat(
            "PARA 1"
        );
    }
}