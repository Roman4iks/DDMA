<?php
namespace App;

use Exception;
use Longman\TelegramBot\Entities\User;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\TelegramLog;
use PDO;
use PDOException;

class DB
{
    protected static $pdo;

    public static function initialize(array $credentials): PDO
    {
        if (empty($credentials)) {
            throw new Exception('MySQL credentials not provided!');
        }

        $dsn = 'mysql:host=' . $credentials['host'] . ';dbname=' . $credentials['database']. ';charset=utf8mb4';
        $options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'];

        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        self::$pdo = $pdo;

        return self::$pdo;
    }

    public static function insertStudentData(int $user_id, string $group): bool {
        $stmt = self::$pdo->prepare("INSERT INTO students (group_name, user_id) VALUES (?,?)"); 
        return $stmt->execute([$group, $user_id]);
    }
    
    public static function insertTeacherData(int $user_id, string|null $group): bool {
        $stmt = self::$pdo->prepare("INSERT INTO teachers (group_name, user_id) VALUES (?,?)"); 
        return $stmt->execute([$group, $user_id]);
    }

    public static function insertUserData(User $user, array $data): bool
    {
        if (!self::isDbConnected()) {
            return false;
        }

        $telegram_id = $user->getId();
        $telegram_username = $user->getUsername();
        $first_name = $data['first_name'];
        $middle_name = $data['middle_name'];
        $second_name = $data['second_name'];
        $birthday = $data['birthday'];
        $email = $data['email'];
        $phone = $data['phone'];

        try {
            $sth = self::$pdo->prepare('
                INSERT INTO users_data 
                (telegram_id, telegram_username, first_name, middle_name, second_name, email, phone, birthday)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                telegram_username = VALUES(telegram_username),
                first_name = VALUES(first_name),
                middle_name = VALUES(middle_name),
                second_name = VALUES(second_name),
                email = VALUES(email),
                phone = VALUES(phone),
                birthday = VALUES(birthday)
            ');

            return $sth->execute([$telegram_id, $telegram_username, $first_name, $middle_name, $second_name, $email, $phone, $birthday]);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }
    public static function isDbConnected(): bool
    {
        return self::$pdo instanceof PDO;
    }

    public static function selectUserData(int $telegramId)
    {
        if (!self::isDbConnected()) {
            return null;
        }
        
        try {
            $query = '
            SELECT * 
            FROM `users_data`  
            WHERE `telegram_id` = :user_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':user_id', $telegramId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    public static function selectAllGroupsData(int $telegramId)
    {
        if (!self::isDbConnected()) {
            return null;
        }
        
        try {
            $query = '
            SELECT name
            FROM `groups_colleage`  
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
    }

    public static function selectStudentData(int $user_id)
    {
        
        if (!self::isDbConnected()) {
            return null;
        }
        
        try {
            $query = '
            SELECT * 
            FROM `students` 
            WHERE `user_id` = :user_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
    }
    

    public static function selectTeacherData(int $user_id)
    {
        if (!self::isDbConnected()) {
            return null;
        }
    
        try {
            $query = '
                SELECT * 
                FROM `teachers` 
                WHERE `user_id` = :user_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();
    
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new TelegramException($e->getMessage());
        }
    }
    
}
