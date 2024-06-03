<?php

namespace App\class;

use App\DB;

class User {
    public string $telegram_id;
    public string $telegram_username;
    public string $first_name;
    public string $middle_name;
    public string $second_name;
    public string $birthday;
    public ?string $email = null;
    public string $phone;

    public function __construct($telegram_id, $telegram_username, $firstName = '', $middleName = '', $secondName = '', $birthday = '', $phone = '', $email = null)
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

    public function printData(): string {
        return sprintf(
            "Telegram ID: %s\nTelegram Username: %s\nFirst Name: %s\nMiddle Name: %s\nSecond Name: %s\nBirthday: %s\nEmail: %s\nPhone: %s",
            $this->telegram_id,
            $this->telegram_username,
            $this->first_name,
            $this->middle_name,
            $this->second_name,
            $this->birthday,
            $this->email ?? 'null',
            $this->phone
        );
    }
    

    public function addEmail(?string $email)
    {
        $this->email = $email;
    }

    public function addBirthday(string $date){
        $this->birthday = $date;
    }

    public function addFirstName(string $firstName)
    {
        $this->first_name = $firstName;
    }

    public function addSecondName(string $secondName)
    {
        $this->second_name = $secondName;
    }

    public function addMiddleName(string $middleName)
    {
        $this->middle_name = $middleName;
    }

    // TODO add Validate Phone
    public function addPhone(string $phone)
    {
        $this->phone = $phone;
    }

    public function isReadyForSave(): bool {
        return isset($this->first_name, $this->middle_name, $this->second_name, $this->birthday, $this->phone);
    }

    public function saveToDatabase() {
        if (!$this->isReadyForSave()) {
            throw new \Exception('Varibles are required before saving to database.');
        }

        DB::insertUserData($this);
    }
}