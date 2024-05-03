<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\TelegramLog;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use TelegramBot\TelegramBotManager\BotManager;

/*
*
* This configuration file is used as the main script for the PHP Telegram Bot Manager.
*
* For the full list of options, go to:
* https://github.com/php-telegram-bot/telegram-bot-manager#set-extra-bot-parameters
*/


// Load composer
require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/src/App/DB.php';

// Load all configuration options
/** @var array $config */
$config = require __DIR__ . '/config.php';

try {
    TelegramLog::initialize(
       new Logger('telegram_bot', [
           (new StreamHandler($config['logging']['debug'], Logger::DEBUG))->setFormatter(new LineFormatter(null, null, true)),
           (new StreamHandler($config['logging']['error'], Logger::ERROR))->setFormatter(new LineFormatter(null, null, true)),
       ]),
       new Logger('telegram_bot_updates', [
           (new StreamHandler($config['logging']['update'], Logger::INFO))->setFormatter(new LineFormatter('%message%' . PHP_EOL)),
       ])
    );
    
    $bot = new BotManager($config);
    $bot->getTelegram()->enableMySql($config['mysql']);
    
    $pdo = App\DB::initialize($config['database']);
    $bot->run();
    
    TelegramLog::debug("Bot-run");
} catch (TelegramException $e) {
    TelegramLog::error($e);

    //echo $e;
} catch (TelegramLogException $e) {
    TelegramLog::error($e);
    //echo $e;
}
