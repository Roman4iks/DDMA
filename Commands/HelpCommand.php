<?php
namespace App\Commands\UserCommands;

use App\DB;
use Longman\TelegramBot\Commands\Command;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class HelpCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'help';

    /**
     * @var string
     */
    protected $description = 'Показати всі команди бота';

    /**
     * @var string
     */
    protected $usage = '/help або /help <command>';

    /**
     * @var string
     */
    protected $version = '1.4.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message     = $this->getMessage();
        $command_str = trim($message->getText(true));

        if($command_str === "Допомога") {
            return $this->replyToChat("Help You");
        } 
        
        // Admin commands shouldn't be shown in group chats
        $safe_to_show = $message->getChat()->isPrivateChat();

        [$all_commands, $user_commands, $admin_commands, $teacher_commands, $student_commands] = $this->getAllCommandsWithRoles();

        // If no command parameter is passed, show the list.
        if ($command_str === '') {
            $text = '*Список команд*:' . PHP_EOL;
            foreach ($user_commands as $user_command) {
                $text .= '/' . $user_command->getName() . ' - ' . $user_command->getDescription() . PHP_EOL;
            }

            if (DB::getUserRole($this->getMessage()->getFrom()->getId()) == "teacher") {
                $text .= PHP_EOL . '*Команди для вчителів*:' . PHP_EOL;
                foreach ($teacher_commands as $teacher_command) {
                    $text .= '/' . $teacher_command->getName() . ' - ' . $teacher_command->getDescription() . PHP_EOL;
                }
            } else if (DB::getUserRole($this->getMessage()->getFrom()->getId()) == "student"){
                $text .= PHP_EOL . '*Команди для студентів*:' . PHP_EOL;
                foreach ($student_commands as $student_command) {
                    $text .= '/' . $student_command->getName() . ' - ' . $student_command->getDescription() . PHP_EOL;
                }
            }

            if ($safe_to_show && count($admin_commands) > 0) {
                $text .= PHP_EOL . '*Команди для адміністратора*:' . PHP_EOL;
                foreach ($admin_commands as $admin_command) {
                    $text .= '/' . $admin_command->getName() . ' - ' . $admin_command->getDescription() . PHP_EOL;
                }
            }

            $text .= PHP_EOL . 'Щоб отримати довідку щодо точної команди, введіть: /help <команда>';

            return $this->replyToChat($text, ['parse_mode' => 'markdown']);
        }

        $command_str = str_replace('/', '', $command_str);
        if (isset($all_commands[$command_str]) && ($safe_to_show || !$all_commands[$command_str]->isAdminCommand())) {
            $command = $all_commands[$command_str];

            return $this->replyToChat(sprintf(
                'Команда: %s (v%s)' . PHP_EOL .
                    'Опис: %s' . PHP_EOL .
                    'Користування: %s',
                $command->getName(),
                $command->getVersion(),
                $command->getDescription(),
                $command->getUsage()
            ), ['parse_mode' => 'markdown']);
        }

        return $this->replyToChat('Немає довідки: команда `/' . $command_str . '` не знайдено', ['parse_mode' => 'markdown']);
    }

    /**
     * Get all available User and Admin commands to display in the help list.
     *
     * @return Command[][]
     * @throws TelegramException
     */
    protected function getAllCommandsWithRoles(): array
    {
        /** @var Command[] $all_commands */
        $all_commands = $this->telegram->getCommandsList();

        // Only get enabled Admin and User commands that are allowed to be shown.
        $commands = array_filter($all_commands, function ($command): bool {
            return !$command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });

        // Filter out all User commands
        $user_commands = array_filter($commands, function ($command): bool {
            return $command->isUserCommand();
        });

        // Filter out all Admin commands
        $admin_commands = array_filter($commands, function ($command): bool {
            return $command->isAdminCommand();
        });

        $teacher_commands = array_filter($commands, function ($command): bool {
            return $command->isTeacherCommand();
        });

        $student_commands = array_filter($commands, function ($command): bool {
            return $command->isStudentCommand();
        });

        if(count($teacher_commands) > 0){
            $user_commands = array_filter($user_commands, function ($user_command) use ($teacher_commands) {
                foreach ($teacher_commands as $teacher_command) {
                    if ($user_command->getName() === $teacher_command->getName()) {
                        return false;
                    }
                }
                return true;
            });
        }

        if(count($student_commands) > 0){
            $user_commands = array_filter($user_commands, function ($user_command) use ($student_commands) {
                foreach ($student_commands as $student_command) {
                    if ($user_command->getName() === $student_command->getName()) {
                        return false;
                    }
                }
                return true;
            });
        }

        ksort($commands);
        ksort($user_commands);
        ksort($teacher_commands);
        ksort($student_commands);
        ksort($admin_commands);

        return [$commands, $user_commands, $admin_commands, $teacher_commands, $student_commands];
    }

    protected function getTeacherCommands(): array
    {
        $all_commands = $this->telegram->getCommandsList();

        // Получить все доступные команды
        $commands = array_filter($all_commands, function ($command) {
            return !$command->isSystemCommand() && $command->showInHelp() && $command->isEnabled();
        });

        // Отфильтровать команды по роли учителя
        $teacher_commands = [];


        ksort($teacher_commands);

        return $teacher_commands;
    }
}
