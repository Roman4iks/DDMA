<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use App\utils\KeyboardTelegram;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'genericmessage';
    protected $description = 'Обробляти загальні повідомлення';
    protected $version = '1.0.0';
    protected $show_in_help = false;

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $text    = trim($message->getText());
        $chat_id = $message->getChat()->getId();
        $user    = $message->getFrom();
        $user_id = $user->getId();

        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $message->getChat()->getId()
        );

        if ($conversation->exists() && $command = $conversation->getCommand()) {
            return $this->telegram->executeCommand($command);
        }

        $keyboard_buttons = [];
        $response_text = 'Невідома команда';

        if ($text === 'Назад') {
            $keyboard_buttons = KeyboardTelegram::getKeyboardKeys($user_id);
            $response_text = 'Вітаю вас у боті технікуму ДДМА' . PHP_EOL . 'Оберіть групу команд:';
        } else {
            switch ($text) {
                case 'Інформація':
                    $keyboard_buttons = ['Група', 'Вчителі та предмети', 'Пари на цей тиждень', 'Назад'];
                    $response_text = 'Команди для отримання інформації:';
                    break;
                case 'Завдання':
                    $keyboard_buttons = ['За цей тиждень', 'Довідка', 'Невиконані за цей тиждень', 'Невиконані за весь час', 'Назад'];
                    $response_text = 'Команди для отримання інформації по задванням:';
                    break;
                case 'Команди для груп':
                    $keyboard_buttons = ['Створити групу', 'Оновити групу', 'Видалити групу', 'Показати довідку по групі', 'Назад'];
                    $response_text = 'Команди для керування групами:';
                    break;
                case 'Інформація про студентів':
                    $keyboard_buttons = ['Інформація по всій групі', 'Інформація по конкретному студенту', 'Назад'];
                    $response_text = 'Команди для отримання інформації по студентам:';
                    break;
                case 'Команди для предметів':
                    $keyboard_buttons = ['Створити предмет', 'Оновити предмет', 'Видалити предмет', 'Викладати предмет', 'Назад'];
                    $response_text = 'Команди для керування предметами колледжу:';
                    break;
                case 'Команди для завдань':
                    $keyboard_buttons = ['Створити завдання', 'Оновити завдання', 'Видалити завдання', 'Переглянути надіслані завдання', 'Отримати інформацію про завдання', 'Назад'];
                    $response_text = 'Команди для керування завданнями колледжу:';
                    break;
                case 'Команди для пар':
                    $keyboard_buttons = ['Створити пару', 'Оновити пару', 'Видалити пару', 'Переглянути пари по групі', 'Назад'];
                    $response_text = 'Команди для керування парами колледжу:';
                    break;
                default:
                    $commands = [
                        'Відміна' => 'Longman\TelegramBot\Commands\UserCommands\CancelCommand',
                        'Регістрація' => 'App\Commands\UserCommands\RegisterCommand',
                        'Новини' => 'App\Commands\UserCommands\NewsCommand',
                        'Допомога' => 'App\Commands\UserCommands\HelpCommand',
                        'Група' => 'Longman\TelegramBot\Commands\StudentCommands\GroupCommand',
                        'Вчителі та предмети' => 'Longman\TelegramBot\Commands\StudentCommands\TeachersubjectCommand',
                        'Записатися на консультацію' => 'Longman\TelegramBot\Commands\StudentCommands\ConsultRegisterCommand',
                        'Відправити завдання' => 'Longman\TelegramBot\Commands\StudentCommands\SendworkCommand',
                        'За цей тиждень' => 'Longman\TelegramBot\Commands\StudentCommands\TasksuncompleteddeadlineCommand',
                        'Невиконані за цей тиждень' => 'Longman\TelegramBot\Commands\StudentCommands\TasksuncompletedthisweekCommand',
                        'Невиконані за весь час' => 'Longman\TelegramBot\Commands\StudentCommands\TasksuncompletedCommand',
                        'Довідка' => 'Longman\TelegramBot\Commands\StudentCommands\TotalworksfromsubjectCommand',
                        'Розклад пар' => 'Longman\TelegramBot\Commands\StudentCommands\PairsCommand',
                        'Пари на цей тиждень' => 'Longman\TelegramBot\Commands\StudentCommands\PairCommand',
                        'Розсилка повідомлення' => 'Longman\TelegramBot\Commands\TeacherCommands\MailingCommand',
                        'Створити групу' => 'Longman\TelegramBot\Commands\TeacherCommands\CreategroupCommand',
                        'Видалити групу' => 'Longman\TelegramBot\Commands\TeacherCommands\DeletegroupCommand',
                        'Оновити групу' => 'Longman\TelegramBot\Commands\TeacherCommands\UpdategroupCommand',
                        'Показати довідку по групі' => 'Longman\TelegramBot\Commands\TeacherCommands\InfogroupCommand',
                        'Інформація по всій групі' => 'Longman\TelegramBot\Commands\TeacherCommands\InfostudentgroupCommand',
                        'Створити предмет' => 'Longman\TelegramBot\Commands\TeacherCommands\CreatesubjectCommand',
                        'Видалити предмет' => 'Longman\TelegramBot\Commands\TeacherCommands\DeletesubjectCommand',
                        'Оновити предмет' => 'Longman\TelegramBot\Commands\TeacherCommands\UpdatesubjectCommand',
                        'Викладати предмет' => 'Longman\TelegramBot\Commands\TeacherCommands\TakesubjectCommand',
                        'Створити завдання' => 'Longman\TelegramBot\Commands\TeacherCommands\CreateworkCommand',
                        'Отримати інформацію про завдання' => 'Longman\TelegramBot\Commands\TeacherCommands\InfoworkCommand',
                        'Видалити завдання' => 'Longman\TelegramBot\Commands\TeacherCommands\DeleteworkCommand',
                        'Оновити завдання' => 'Longman\TelegramBot\Commands\TeacherCommands\UpdateworkCommand',
                        'Переглянути надіслані завдання' => 'Longman\TelegramBot\Commands\TeacherCommands\UpdatecompleteworkCommand',
                        'Створити пару' => 'Longman\TelegramBot\Commands\TeacherCommands\CreatepairCommand',
                        'Оновити пару' => 'Longman\TelegramBot\Commands\TeacherCommands\UpdatepairCommand',
                        'Видалити пару' => 'Longman\TelegramBot\Commands\TeacherCommands\DeletepairCommand',
                        'Переглянути пари по групі' => 'Longman\TelegramBot\Commands\TeacherCommands\InfopairsCommand',
                    ];
                    if (array_key_exists($text, $commands)) {
                        $class_name = $commands[$text];
                        if (class_exists($class_name)) {
                            $command_instance = new $class_name($this->telegram, $this->update);
                            return $command_instance->preExecute();
                        }
                    }
                    $response_text = 'Неизвестная команда';
                    break;
            }
        }

        if (!empty($keyboard_buttons)) {
            $keyboard = new Keyboard(...array_chunk($keyboard_buttons, 2));
            $keyboard->setResizeKeyboard(true)
                ->setOneTimeKeyboard(false);
            $data = [
                'chat_id'      => $chat_id,
                'text'         => $response_text,
                'reply_markup' => $keyboard,
            ];
        } else {
            $data = [
                'chat_id' => $chat_id,
                'text'    => $response_text,
            ];
        }

        return Request::sendMessage($data);
    }
}
