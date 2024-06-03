<?php

namespace App\Commands\UserCommands;

use App\class\User;
use App\DB;
use App\utils\Validator;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\TelegramLog;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;

class RegisterCommand extends UserCommand
{
    protected $name = 'register';
    protected $description = 'Регістрація користувача в системі';
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

        if (DB::selectUserData($user_id)) {
            return $this->replyToChat('Ви зареєстровані!');
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

                    $data['text'] = "Напишіть своє ім'я:";

                    $result = Request::sendMessage($data);
                    break;
                }

                if (!Validator::validateString($text)) {
                    $data['text'] = "Ім'я не повинно містити спеціальні символи та прогалини.";
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

                    $data['text'] = 'Напишіть своє прізвище:';

                    $result = request::sendMessage($data);
                    break;
                }

                if (!Validator::validateString($text)) {
                    $data['text'] = 'Прізвище не повинно містити спеціальних символів або пробілів.';
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

                    $data['text'] = 'Напишіть своє по батькові:';

                    $result = request::sendmessage($data);
                    break;
                }

                if (!Validator::validateString($text)) {
                    $data['text'] = 'По-батькові не повинно містити спеціальні символи та прогалини.';
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

                    $data['text'] = 'Напишіть рік народження у форматі 2001-12-31:';

                    $result = Request::sendMessage($data);
                    break;
                }

                if (!Validator::validateDate($text)) {
                    $data['text'] = 'Неправильний формат дати';

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

                    $data['text'] = "Поділіться своїми контактами для зв'язку:";

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

                    $data['text'] = 'Напишіть вашу електронну адресу, якщо не бажаєте вказувати напішіть Next: ';

                    $result = request::sendmessage($data);
                    break;
                }

                if (Validator::validateEmail($text)) {
                    if ($text !== "Next") {
                        $data['text'] = 'Неправильно вказана електронна адреса.';
                        $result = Request::sendMessage($data);
                        break;
                    }
                }

                $notes['email'] = ($text == 'Next') ? null : $text;
                $text = '';
                TelegramLog::debug('Success Register Email:', $notes);
            case 6:
                TelegramLog::debug('Start Register Role');

                if ($text === '' || !in_array($text, ['Викладач', 'Студент'], true)) {
                    $notes['state'] = 6;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard(['Викладач', 'Студент']))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Ким ви є? :';

                    if ($text !== '' || !in_array($text, ['Викладач', 'Студент'], true)) {
                        $data['text'] = 'Виберіть ким ви є';
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
                    $groups[] = $group->name;
                }

                if ($notes['role'] === 'Викладач') {
                    $groups[] = 'Next';
                }

                if ($text === '' || ($text !== 'Next' && !in_array($text, $groups, true))) {
                    $notes['state'] = 7;
                    $this->conversation->update();

                    $data['reply_markup'] = (new Keyboard($groups))
                        ->setResizeKeyboard(true)
                        ->setOneTimeKeyboard(true)
                        ->setSelective(true);

                    $data['text'] = 'Виберіть групу:';

                    if ($text === '') {
                        $result = Request::sendMessage($data);
                    } else {
                        $data['text'] = 'Такої групи не зареєстровано у базі. Будь ласка, виберіть існуючу групу';
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
                $out_text = '/register Результат:' . PHP_EOL;
                unset($notes['state']);

                foreach ($notes as $k => $v) {
                    $value = ($v !== null) ? $v : 'Nothing';
                    $out_text .= PHP_EOL . ucfirst($k) . ': ' . $value;
                }

                try {
                    DB::insertUserData(new User($user_id, $user->getUsername(), $notes['first_name'], $notes['middle_name'], $notes['second_name'], $notes['birthday'], $notes['phone'], $notes['email']));
                    TelegramLog::debug("Success insert user data");

                    if ($notes['role'] == "Студент") {
                        DB::insertStudentData($user_id, $notes['group']);
                        TelegramLog::debug("Success insert student");
                    } elseif ($notes['role'] == "Викладач") {
                        DB::insertTeacherData($user_id, $notes['group']);
                        TelegramLog::debug("Success insert teacher");
                    }
                } catch (\PDOException $e) {
                    $data['text'] = 'Статус ❌';
                    $result = Request::sendMessage($data);
                    TelegramLog::error("Error insert - " . $e);
                    break;
                }

                $data['text'] = $out_text . PHP_EOL . "Статус ✅";

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                TelegramLog::debug("Finish registration", $notes);
                break;
        }
        return $result;
    }
}
