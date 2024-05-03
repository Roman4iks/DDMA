<?php

namespace App\class;

use App\utils\Validator;
use InvalidArgumentException;
use Longman\TelegramBot\Entities\User as UserTelegram;

class User {
    public string $telegram_id;
    public string $telegram_username;
    public string $first_name;
    public string $middle_name;
    public string $second_name;
    public string $birthday;
    public string $email;
    public string $phone;

    public function __construct($telegram_id, $telegram_username, $firstName = null, $middleName = null, $secondName = null, $birthday = null, $phone = null, $email = null)
    {
        $this->telegram_id = $telegram_id;
        $this->telegram_username = $telegram_username;
        if ($email !== null) {
            $this->addEmail($email);
        }
        if ($birthday !== null) {
            $this->addBirthday($birthday);
        }
        if ($firstName !== null) {
            $this->addFirstName($firstName);
        }
        if ($secondName !== null) {
            $this->addSecondName($secondName);
        }
        if ($middleName !== null) {
            $this->addMiddleName($middleName);
        }
        if ($phone !== null){
            $this->addPhone($phone);
        }
    }

    public static function createFromData($id, $username, $data = [])
    {
        $newUser = new self($id, $username);
        foreach ($data as $key => $value) {
            $method = 'add' . ucfirst($key);
            if (method_exists($newUser, $method)) {
                $newUser->$method($value);
            }
        }
        return $newUser;
    }

    public function addEmail(string $email)
    {
        if (!Validator::validateEmail($email)) {
            throw new InvalidArgumentException("Invalid email address");
        }
        $this->email = $email;
    }

    public function addBirthday(string $date){
        if (!Validator::validateDate($date)) {
            throw new InvalidArgumentException("Invalid birthday date");
        }
        $this->birthday = $date;
    }

    public function addFirstName(string $firstName)
    {
        if (!Validator::validateString($firstName)) {
            throw new InvalidArgumentException("Invalid full name");
        }
        $this->first_name = $firstName;
    }

    public function addSecondName(string $secondName)
    {
        if (!Validator::validateString($secondName)) {
            throw new InvalidArgumentException("Invalid full name");
        }
        $this->second_name = $secondName;
    }

    
    public function addMiddleName(string $middleName)
    {
        if (!Validator::validateString($middleName)) {
            throw new InvalidArgumentException("Invalid full name");
        }
        $this->middle_name = $middleName;
    }

    // TODO add Validate Phone
    public function addPhone(string $phone)
    {
        $this->phone = $phone;
    }
}