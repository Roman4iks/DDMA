<?php
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Exception\TelegramException;

class RegisterCommand extends UserCommand
{
    protected $name = 'register';
    protected $description = 'Register User';
    protected $usage = '/register';
    
    protected $version = '0.0.1';

    protected $need_mysql = true;

    protected $private_only = true;
    
    protected $conversation;
    
    public function execute(): ServerResponse
        {
            $message = $this->getMessage();
            $chat    = $message->getChat();
            $user    = $message->getFrom();
            $text    = trim($message->getText(true));
            $chat_id = $chat->getId();
            $user_id = $user->getId();
    
            $data = [
                'chat_id'      => $chat_id,
                'reply_markup' => Keyboard::remove(['selective' => true]),
            ];
    
            if ($chat->isGroupChat() || $chat->isSuperGroup()) {
                $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
            }

            $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
    
            $notes = &$this->conversation->notes;
            !is_array($notes) && $notes = [];
    
            $state = $notes['state'] ?? 0;
    
            $result = Request::emptyResponse();

            TelegramLog::debug('Start Register');
   
            switch ($state) {
                case 0:
                    TelegramLog::debug('Start Register Name');
                    if ($text === '') {
                        $notes['state'] = 0;
                        $this->conversation->update();
    
                        $data['text'] = 'Напишите свое имя:';
    
                        $result = Request::sendMessage($data);
                        break;
                    }
                    $notes['name'] = $text;
                    TelegramLog::debug('Success Register Name:', $notes);
                    $text = '';
                case 1:
                    TelegramLog::debug('Start Register Surname');
                    if ($text === '') {
                        $notes['state'] = 1;
                        $this->conversation->update();
    
                        $data['text'] = 'Напишите свою фамилию:';
    
                        $result = request::sendMessage($data);
                        break;
                    }
    
                    $notes['surname'] = $text;
                    TelegramLog::debug('Success Register Surname:', $notes);
                    $text = '';
                case 2:
                    TelegramLog::debug('Start Register Second Name');
                    if ($text === '') {
                        $notes['state'] = 2;
                        $this->conversation->update();
    
                        $data['text'] = 'Напишите свое отчество:';
    
                        $result = request::sendmessage($data);
                        break;
                    }
    
                    $notes['second_name'] = $text;
                    TelegramLog::debug('Success Register Second Name:', $notes);
                    $text = '';

                case 3:
                    TelegramLog::debug('Start Register Birthday');
                    if ($text === '') {
                        $notes['state'] = 3;
                        $this->conversation->update();
                
                        $data['text'] = 'Напишите год рождения в формате 01.10.2001:';
                
                        $result = Request::sendMessage($data);
                        break;
                    }
                    
                    if (!\DateTime::createFromFormat('d.m.Y', $text)) {
                        $data['text'] = 'Год рождения не соответствует формату';
                        $result = Request::sendMessage($data);
                        break;
                    }
                
                    $notes['birthday'] = $text;
                    TelegramLog::debug('Success Register birthday:', $notes);
                    $text = '';
                case 4:
                    TelegramLog::debug('Start Register Role');
                    if ($text === '' || !in_array($text, ['Преподователь', 'Студент'], true)) {
                        $notes['state'] = 4;
                        $this->conversation->update();
    
                        $data['reply_markup'] = (new Keyboard(['Преподователь', 'Студент']))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);
    
                        $data['text'] = 'Кем вы являетесь? :';
                        if ($text !== '' || !in_array($text, ['Преподователь', 'Студент'], true)) {
                            $data['text'] = 'Выберите кем вы являетесь';
                        }
    
                        $result = Request::sendMessage($data);
                        break;
                    }
    
                    $notes['role'] = $text;
                    TelegramLog::debug('Success Register Role:', $notes);
                    $text = '';
                case 5:
                    TelegramLog::debug('Start Register Contact');
                    if ($message->getContact() === null) {
                        $notes['state'] = 5;
                        $this->conversation->update();
    
                        $data['reply_markup'] = (new Keyboard(
                            (new KeyboardButton('Share Contact'))->setRequestContact(true)
                        ))
                            ->setOneTimeKeyboard(true)
                            ->setResizeKeyboard(true)
                            ->setSelective(true);
    
                        $data['text'] = 'Поделитесь своими контактами для связи:';
    
                        $result = Request::sendMessage($data);
                        break;
                    }
    
                    $notes['phone_number'] = $message->getContact()->getPhoneNumber();
                    TelegramLog::debug('Success Register Phone Number:', $notes);
                case 6:
                    TelegramLog::debug('Finish Register');
                    $this->conversation->update();
                    $out_text = '/register result:' . PHP_EOL;
                    unset($notes['state']);
                    foreach ($notes as $k => $v) {
                        $out_text .= PHP_EOL . ucfirst($k) . ': ' . $v;
                    }
    
                    $data['text'] = $out_text;
    
                    $this->conversation->stop();
    
                    $result = Request::sendMessage($data);
                    break;
            }
    
            return $result;
        }
}