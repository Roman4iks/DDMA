<?php
namespace Longman\TelegramBot\Commands\UserCommands;

require_once 'src/utils/validateValues.php';
use App\DB;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Exception\TelegramException;

use function App\utils\validateDate;
use function App\utils\validateEmail;
use function App\utils\validateString;

class RegisterCommand extends UserCommand
{
    protected $name = 'register';
    protected $description = 'Register User';
    protected $usage = '/register';
    
    protected $version = '0.1.8';

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

            if(DB::selectUserData($user_id)){
                return $this->replyToChat('Вы зарегистрированы!');
            }
            
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

                    if (validateString($text)) {
                        $data['text'] = 'Имя не должно содержать специальные символы или пробелы.';
                        $result = Request::sendMessage($data);
                        break;
                    }
                    
                    $notes['first_name'] = $text;
                    $text = '';
                    TelegramLog::debug('Success Register Name:', $notes);
                case 1:
                    TelegramLog::debug('Start Register Surname');
                    if ($text === '') {
                        $notes['state'] = 1;
                        $this->conversation->update();
    
                        $data['text'] = 'Напишите свою фамилию:';
    
                        $result = request::sendMessage($data);
                        break;
                    }
    
                    if (validateString($text)) {
                        $data['text'] = 'Фамилия не должна содержать специальные символы или пробелы.';
                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['middle_name'] = $text;
                    $text = '';
                    TelegramLog::debug('Success Register Surname:', $notes);
                case 2:
                    TelegramLog::debug('Start Register Second Name');
                    if ($text === '') {
                        $notes['state'] = 2;
                        $this->conversation->update();
    
                        $data['text'] = 'Напишите свое отчество:';
    
                        $result = request::sendmessage($data);
                        break;
                    }
    
                    if (validateString($text)) {
                        $data['text'] = 'Отчество не должно содержать специальные символы или пробелы.';
                        $result = Request::sendMessage($data);
                        break;
                    }

                    $notes['second_name'] = $text;
                    $text = '';
                    TelegramLog::debug('Success Register Second Name:', $notes);
                case 3:
                    TelegramLog::debug('Start Register Birthday');
                    if ($text === '') {
                        $notes['state'] = 3;
                        $this->conversation->update();
                
                        $data['text'] = 'Напишите год рождения в формате 2001-12-31:';
                
                        $result = Request::sendMessage($data);
                        break;
                    }
                    
                    $data['text'] = validateDate($text);
                    if($data['text'])
                    {
                        $result = Request::sendMessage($data);
                        break;
                    }
                
                    $notes['birthday'] = date('Y-m-d', strtotime($text));
                    $text = '';
                    TelegramLog::debug('Success Register birthday:', $notes);
                case 4:
                    TelegramLog::debug('Start Register Contact');
                    if ($message->getContact() === null) {
                        $notes['state'] = 4;
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
        
                    $notes['phone'] = $message->getContact()->getPhoneNumber();
                    TelegramLog::debug('Success Register Phone Number:', $notes);
                case 5:
                    TelegramLog::debug('Start Register Email');
                    if ($text === '') {
                        $notes['state'] = 5;
                        $this->conversation->update();
        
                        $data['text'] = 'Напишите ваш електронный адрес:';
        
                        $result = request::sendmessage($data);
                        break;
                    }
        
                     if (validateEmail($text)) {
                        if($text !== "Next"){
                            $data['text'] = 'Неверно указан електронный адрес.';
                            $result = Request::sendMessage($data);
                            break;
                        }
                    }

                    $notes['email'] = ($text == 'Next') ? null : $text;
                    $text = '';
                    TelegramLog::debug('Success Register Email:', $notes);
                case 6:
                    TelegramLog::debug('Start Register Role');
                    
                    if ($text === '' || !in_array($text, ['Преподователь', 'Студент'], true)) {
                        $notes['state'] = 6;
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
                    $text = '';
                    TelegramLog::debug('Success Register Role:', $notes);
                 case 7:
                    TelegramLog::debug('Start Register Group');

                    $groups = [];
                    foreach (DB::selectAllGroupsData($user_id) as $group) {
                        $groups[] = $group['name'];
                    }

                    if ($notes['role'] === 'Преподователь') {
                        $groups[] = 'Next';
                    }

                    if ($text === '' || ($text !== 'Next' && !in_array($text, $groups, true))) {
                        $notes['state'] = 7;
                        $this->conversation->update();
                
                        $data['reply_markup'] = (new Keyboard($groups))
                            ->setResizeKeyboard(true)
                            ->setOneTimeKeyboard(true)
                            ->setSelective(true);
                
                        $data['text'] = 'Выберите группу:';

											if ($text === '') {
													$result = Request::sendMessage($data);
											} else {
													$data['text'] = 'Такой группы не зарегистрировано в базе. Пожалуйста, выберите существующую группу';
													$result = Request::sendMessage($data);
											}
											break;
                    }

										$notes['group'] = ($text === 'Next') ? null : $text;
                    $text = '';
                    TelegramLog::debug('Success Register Group:', $notes);
                case 8:
                    TelegramLog::debug('Finish Register');

                    $this->conversation->update();
                    $out_text = '/register result:' . PHP_EOL;
                    unset($notes['state']);
                    
										foreach ($notes as $k => $v) {
											$value = ($v !== null) ? $v : 'Nothing';
											$out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
										}									

                    try {
                        DB::insertUserData($user, $notes);
                        TelegramLog::debug("Success insert user data");
            
                        if($notes['role'] == "Студент"){
                            DB::insertStudentData($user_id, $notes['group']);
                            TelegramLog::debug("Success insert student");
                        }elseif($notes['role'] == "Преподователь"){
                            DB::insertTeacherData($user_id, $notes['group']);
                            TelegramLog::debug("Success insert teacher");
                        }
                    } catch (\PDOException $e) {
                        $data['text'] = 'Произошла ошибка при регистрации';
                        $result = Request::sendMessage($data);
                        TelegramLog::error("Error insert - ". $e);
                        break;
                    }

										$data['text'] = $out_text . PHP_EOL . "Insert database Success";
    
                    $this->conversation->stop();
    
                    $result = Request::sendMessage($data);
                    
                    TelegramLog::debug("Finish registration", $notes);
                    break;
            }
            return $result;
        }
}