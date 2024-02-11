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

use Longman\TelegramBot\TelegramLog;

/**
 * This configuration file is used as the main script for the PHP Telegram Bot Manager.
 *
 * For the full list of options, go to:
 * https://github.com/php-telegram-bot/telegram-bot-manager#set-extra-bot-parameters
 */

// Load composer
require_once __DIR__ . '/vendor/autoload.php';

// Load all configuration options
/** @var array $config */
$config = require __DIR__ . '/config.php';

try {
    Longman\TelegramBot\TelegramLog::initialize(
       new Monolog\Logger('telegram_bot', [
           (new Monolog\Handler\StreamHandler($config['logging']['debug'], Monolog\Logger::DEBUG))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
           (new Monolog\Handler\StreamHandler($config['logging']['error'], Monolog\Logger::ERROR))->setFormatter(new Monolog\Formatter\LineFormatter(null, null, true)),
       ]),
       new Monolog\Logger('telegram_bot_updates', [
           (new Monolog\Handler\StreamHandler($config['logging']['update'], Monolog\Logger::INFO))->setFormatter(new Monolog\Formatter\LineFormatter('%message%' . PHP_EOL)),
       ])
    );
    
    $bot = new TelegramBot\TelegramBotManager\BotManager($config);
    $bot->getTelegram()->enableMySql($config['mysql']);
    
    $bot->run();
    
    Longman\TelegramBot\TelegramLog::debug("Bot-run");
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    Longman\TelegramBot\TelegramLog::error($e);

    //echo $e;
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    Longman\TelegramBot\TelegramLog::error($e);
    
    //echo $e;
}
