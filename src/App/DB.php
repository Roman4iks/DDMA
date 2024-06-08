<?php

namespace App;

use App\class\CompletedWork;
use App\class\Group;
use App\class\Subject;
use App\class\User;
use App\class\Work;
use Exception;
use Longman\TelegramBot\TelegramLog;
use PDO;
use PDOException;

class DB
{
    protected static $pdo;

    public static function initialize(array $credentials): PDO|Exception
    {
        if (empty($credentials)) {
            throw new Exception('MySQL credentials not provided!');
        }

        $dsn = 'mysql:host=' . $credentials['host'] . ';dbname=' . $credentials['database'] . ';charset=utf8mb4';
        $options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'];

        try {
            $pdo = new PDO($dsn, $credentials['user'], $credentials['password'], $options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }

        self::$pdo = $pdo;

        return self::$pdo;
    }

    public static function isDbConnected(): bool
    {
        return self::$pdo instanceof PDO;
    }

    public static function insertUserData(User $user): bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        if (self::selectUserData($user->telegram_id)) {
            return new Exception("User is already");
        }

        try {
            $stmt = self::$pdo->prepare('
                INSERT INTO users
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

            return $stmt->execute([
                $user->telegram_id,
                $user->telegram_username,
                $user->first_name,
                $user->middle_name,
                $user->second_name,
                $user->email,
                $user->phone,
                $user->birthday
            ]);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectUserData(string $telegramId): User|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT * 
            FROM `users`  
            WHERE `telegram_id` = :user_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':user_id', $telegramId, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new User(
                    $data['telegram_id'],
                    $data['telegram_username'],
                    $data['first_name'],
                    $data['middle_name'],
                    $data['second_name'],
                    $data['birthday'],
                    $data['phone'],
                    $data['email']
                );
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function insertGroupData(Group $group): bool|Exception
    {
        if (self::selectGroupData($group->name)) {
            return new Exception("The group already exists");
        }
        try {
            $stmt = self::$pdo->prepare("INSERT INTO groups (name, fullname, group_link) VALUES (?,?,?)");
            return $stmt->execute([$group->name, $group->fullname, $group->link]);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function deleteGroupData(Group $group): bool|Exception
    {
        try {
            $stmt = self::$pdo->prepare("
            DELETE FROM teachers WHERE group_id = :group_id;
            DELETE FROM groups WHERE id = :group_id");
            $stmt->bindParam(':group_id', $group->id, PDO::PARAM_STR);
            $result = $stmt->execute();

            return $result;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function deleteWorkData(Work $work): bool|Exception
    {
        try {
            $stmt = self::$pdo->prepare("
            DELETE FROM works WHERE id = :work_id;");
            $stmt->bindParam(':work_id', $work->id, PDO::PARAM_STR);
            $result = $stmt->execute();

            return $result;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectGroupData(string $name): Group|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT * 
            FROM `groups` 
            WHERE `name` = :name
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                $link = !$data['group_link'] ? "null" : $data['group_link'];
                return new Group($data['name'], $data['fullname'], $link, $data['id']);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectGroupDataById(int $id): Group|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT * 
            FROM `groups` 
            WHERE `id` = :id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new Group($data['name'], $data['fullname'], !$data['group_link'] ? null : $data['group_link'], $data['id']);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectPairsDataByGroupId(int $group_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
SELECT pairs.*, 
       CONCAT(users.first_name, ' ', users.middle_name, ' ', users.second_name) AS teacher_fullname,
       subjects.name AS subject_name
FROM pairs
JOIN users ON pairs.teacher_id = users.telegram_id
JOIN subjects ON pairs.subject_id = subjects.id
WHERE pairs.group_id = :group_id
AND pairs.top_week = IF(WEEK(CURRENT_DATE()) % 2 = 0, 1, 0);
            ";
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':group_id', $group_id, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($data) {
                return $data;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function deletePairDataById(int $pair_id): bool|Exception
    {
        try {
            $stmt = self::$pdo->prepare("DELETE FROM `pairs` WHERE id = :pair_id");
            $stmt->bindParam(':pair_id', $pair_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function insertPairData(int $subject_id, int $teacher_id, int $group_id, $start, $end, $week, $top_week): bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $sql = "INSERT INTO `pairs` (`subject_id`, `teacher_id`, `group_id`, `start`, `end`, `week`, `top_week`) 
            VALUES (:subject_id, :teacher_id, :group_id, :start, :end, :week, :top_week)";

            // Подготовка запроса
            $stmt = self::$pdo->prepare($sql);

            // Параметры запроса

            // Привязка параметров к меткам
            $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_INT);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_STR);
            $stmt->bindParam(':group_id', $group_id, PDO::PARAM_INT);
            $stmt->bindParam(':start', $start, PDO::PARAM_STR);
            $stmt->bindParam(':end', $end, PDO::PARAM_STR);
            $stmt->bindParam(':week', $week, PDO::PARAM_INT);
            $stmt->bindParam(':top_week', $top_week, PDO::PARAM_INT);

            // Выполнение запроса
            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function updatePairData(string $column, string $new_data, int $pair_id, int $teacher_id): bool|Exception
    {
        try {
            $stmt = self::$pdo->prepare("UPDATE `pairs` SET `$column` = :new_data WHERE `id` = :pair_id AND `teacher_id` = :teacher_id;");
            $stmt->bindParam(':pair_id', $pair_id, PDO::PARAM_STR);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_STR);
            $stmt->bindParam(':new_data', $new_data, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectAllPairsDataByGroup(int $group_id, int $subject_id = null): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
SELECT pairs.*, 
       subjects.name AS subject_name,
       CONCAT(users.first_name, ' ', users.middle_name, ' ', users.second_name) AS teacher_fullname
FROM pairs
JOIN users ON pairs.teacher_id = users.telegram_id
JOIN subjects ON pairs.subject_id = subjects.id
WHERE pairs.group_id = :group_id
            ";
            if($subject_id){
                $query .= 'AND pairs.subject_id = :subject_id;';
            }
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':group_id', $group_id, PDO::PARAM_STR);
            if($subject_id){
                $stmt->bindParam(':subject_id', $subject_id, PDO::PARAM_STR);
            }
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($data) {
                return $data;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectColumnPairData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = 'collage' AND TABLE_NAME = 'pairs';
            ";
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectColumnGroupData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = 'collage' AND TABLE_NAME = 'groups';
            ";
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectColumnSubjectData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = 'collage' AND TABLE_NAME = 'subjects';
            ";
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectColumnWorkData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = 'collage' AND TABLE_NAME = 'works';
            ";
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $data;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function updateGroupData(string $column, string $new_data, string $group_name): bool|Exception
    {
        try {
            $stmt = self::$pdo->prepare("UPDATE `groups` SET `$column` = :new_data WHERE `name` = :group_name;");
            $stmt->bindParam(':group_name', $group_name, PDO::PARAM_STR);
            $stmt->bindParam(':new_data', $new_data, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function updateWorkData(string $column, string $new_data, Work $work): bool|Exception
    {
        try {
            $stmt = self::$pdo->prepare("UPDATE `works` SET `$column` = :new_data WHERE `id` = :work_id;");
            $stmt->bindParam(':work_id', $work->id, PDO::PARAM_STR);
            $stmt->bindParam(':new_data', $new_data, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }


    public static function updateSubjectData(string $column, string $new_data, string $subject_name): bool|Exception
    {
        try {
            $stmt = self::$pdo->prepare("UPDATE `subjects` SET `$column` = :new_data WHERE `name` = :subject_name;");
            $stmt->bindParam(':subject_name', $subject_name, PDO::PARAM_STR);
            $stmt->bindParam(':new_data', $new_data, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectAllStudentsDataByGroup(int $group_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT * 
            FROM `students` 
            WHERE `group_id` = :group_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':group_id', $group_id, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($data) {
                return $data;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectAllTeacherData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT t.*, CONCAT(u.first_name, ' ', u.middle_name, ' ', u.second_name) AS teacher_fullname
            FROM teachers t
            JOIN users u ON t.user_id = u.telegram_id;
            ";
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($data) {
                return $data;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function insertSubjectData(Subject $subject): bool
    {
        if (!DB::selectSubjectData($subject->name)) {
            $stmt = self::$pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
            return $stmt->execute([$subject->name]);
        } else {
            return false;
        }
    }

    public static function selectSubjectData(string $name): Subject|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }
        try {
            $query = '
                SELECT * 
                FROM `subjects` 
                WHERE `name` = :name
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new Subject($data['name'], $data['id']);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectSubjectDataById(int $id): Subject|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }
        try {
            $query = '
                SELECT * 
                FROM `subjects` 
                WHERE `id` = :id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new Subject($data['name'], $data['id']);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function deleteSubjectData(Subject $subject): bool|Exception
    {
        TelegramLog::debug("HERE");
        try {
            $stmt = self::$pdo->prepare("DELETE FROM `subjects` WHERE `subjects`.`id` = :subject_id");
            $stmt->bindParam(':subject_id', $subject->id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function insertWorkData(Work $work): bool
    {
        $group = self::selectGroupData($work->group_name);
        $subject = self::selectSubjectData($work->subject_name);
        $teacher = self::selectTeacherData($work->teacher_id);

        $stmt = self::$pdo->prepare("INSERT INTO works (task, subject_id, teacher_id, group_id, start, end) VALUES (?,?,?,?,?,?)");

        if ($group && $subject && $teacher) {
            return $stmt->execute([$work->task, $subject->id, $teacher['user_id'], $group->id, $work->start, $work->end]);
        } else {
            return false;
        }
    }

    public static function insertCompletedWorkData(CompletedWork $completedWork): bool
    {
        $work = self::selectWorkDataById($completedWork->workId);

        if (!$work) {
            return false;
        }

        $stmt = self::$pdo->prepare("INSERT INTO completed_works (teacher_id, student_id, work_id, finish, grade, file_id) VALUES (?,?,?,?,?,?)");

        return $stmt->execute([$completedWork->teacherId, $completedWork->studentId, $completedWork->workId, $completedWork->finish, $completedWork->grade, $completedWork->file_id]);
    }



    public static function insertTeacherSubjectData(int $teacher_id, string $subject_name): bool
    {
        if (self::selectTeacherData($teacher_id)) {
            $stmt = self::$pdo->prepare("INSERT INTO teacher_subject (teacher_id, subject_id) VALUES (?,?)");
            $subject = self::selectSubjectData($subject_name);
            return $stmt->execute([$teacher_id, $subject->id]);
        } else {
            return false;
        }
    }

    public static function insertStudentData(int $user_id, string $group_name): bool
    {
        $stmt = self::$pdo->prepare("INSERT INTO students (user_id, group_id) VALUES (?,?)");
        $group = self::selectGroupData($group_name);
        return $stmt->execute([$user_id, $group->id]);
    }

    public static function insertTeacherData(int $user_id, string $group_name): bool
    {
        $group = self::selectGroupData($group_name);
        if ($group) {
            $group_id = $group->id;
        } else {
            return false;
        }

        $stmt = self::$pdo->prepare("INSERT INTO teachers (user_id, group_id) VALUES (?,?)");

        return $stmt->execute([$user_id, $group_id]);
    }

    public static function selectWorkData(string $task): Work|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT w.*, g.name AS group_name, t.user_id AS teacher_id, s.name AS subject_name
            FROM works w
            LEFT JOIN groups g ON w.group_id = g.id
            LEFT JOIN teachers t ON w.teacher_id = t.user_id
            LEFT JOIN subjects s ON w.subject_id = s.id
            WHERE w.task = :task;            
            ';

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':task', $task, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new Work($data['task'], $data['subject_name'], $data['teacher_id'], $data['group_name'], $data['start'], $data['end'], $data['id']);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectWorkDataById(int $id): Work|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT w.*, g.name AS group_name, t.user_id AS teacher_id, s.name AS subject_name
            FROM works w
            LEFT JOIN groups g ON w.group_id = g.id
            LEFT JOIN teachers t ON w.teacher_id = t.user_id
            LEFT JOIN subjects s ON w.subject_id = s.id
            WHERE w.id = :id;            
            ';

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                return new Work($data['task'], $data['subject_name'], $data['teacher_id'], $data['group_name'], $data['start'], $data['end'], $data['id']);
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectWorksData(string $group_name, int $teacher_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        $group = self::selectGroupData($group_name);

        if (!$group) {
            return new Exception("Group not found");
        }

        $teacher = self::selectTeacherData($teacher_id);

        if (!$teacher) {
            return new Exception("Teacher not found");
        }

        try {
            $query = '
            SELECT * 
            FROM works 
            WHERE group_id = :group_id AND teacher_id = :teacher_id
            ';

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':group_id', $group->id, PDO::PARAM_STR);
            $stmt->bindParam(':teacher_id', $teacher['user_id'], PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectAllGroupsData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT *
            FROM `groups`  
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $groups = [];
            if ($data) {
                foreach ($data as $group) {
                    array_push($groups, new Group($group['name'], $group['fullname'], isset($group['link']) ? $group['link'] : "Null", $group['id']));
                }
            } else {
                return false;
            }
            return $groups;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectAllSubjectsData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT *
            FROM `subjects`  
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $subjects = [];
            if ($data) {
                foreach ($data as $subject) {
                    array_push($subjects, new Subject($subject['name'], $subject['id']));
                }
            } else {
                return false;
            }
            return $subjects;
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectStudentData(int $user_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        $user = self::selectUserData($user_id);

        if (!$user) {
            return new Exception("User data is not available");
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
            return new Exception($e->getMessage());
        }
    }

    public static function selectTeacherData(int $user_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
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
            return new Exception($e->getMessage());
        }
    }

    public static function selectAllTeachers(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT teachers.*, 
            CONCAT(users.first_name, ' ', users.middle_name, ' ', users.second_name) AS teacher_full_name
            FROM teachers
            JOIN users ON teachers.user_id = users.telegram_id;            
            ";
            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectTeacherDataByGroup(int $group_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
                SELECT * 
                FROM `teachers` 
                WHERE `group_id` = :group_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':group_id', $group_id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectTeacherSubjectData(string $subject_name = null, int $teacher_id = null): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        $subject = self::selectSubjectData($subject_name);

        try {
            $query = '
                SELECT * 
                FROM `teacher_subject` 
            ';

            if ($teacher_id) {
                $query .= "WHERE `teacher_id` = :teacher_id";
            } else if ($subject) {
                $query .= "WHERE `subject_id` = :subject_id";
            } else {
                return false;
            }

            $stmt = self::$pdo->prepare($query);

            if ($teacher_id) {
                $stmt->bindParam(':teahcer_id', $teacher_id, PDO::PARAM_INT);
            } else if ($subject) {
                $stmt->bindParam(':subject_id', $subject->id, PDO::PARAM_INT);
            }

            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectAllTeacherSubjectData(): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
                SELECT * 
                FROM `teacher_subject` 
            ';

            $stmt = self::$pdo->prepare($query);

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectCompletedWorksData(int $id, int $student_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        $work = self::selectWorkDataById($id);
        if (!$work) {
            return new Exception("Work in not found");
        }

        try {
            $query = '
                SELECT * 
                FROM `completed_works` 
                WHERE `work_id` = :work_id
                AND `student_id` = :student_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':work_id', $work->id, PDO::PARAM_STR);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function updateCompletedWorkData(int $work_id, int $student_id, int $grade, int $teacher_id): bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = 'UPDATE completed_works SET grade = :grade, teacher_id = :teacher_id WHERE student_id = :student_id AND work_id = :work_id;';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':work_id', $work_id, PDO::PARAM_STR);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
            $stmt->bindParam(':grade', $grade, PDO::PARAM_STR);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectCompletedWorksDataWithoutGrade(int $teacher_id): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT cw.*, w.task
            FROM completed_works AS cw
            JOIN works AS w ON cw.work_id = w.id
            WHERE cw.grade IS NULL
              AND cw.teacher_id IS NULL
              AND w.teacher_id = :teacher_id;
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getWorksGroupData(string $group_name): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT
            w.id AS work_id,
            w.task,
            CONCAT(u.first_name, ' ', u.middle_name, ' ', u.second_name) AS teacher_full_name,
            s.name AS subject,
            w.start,
            w.end
        FROM
            works w
        JOIN
            groups g ON w.group_id = g.id
        JOIN
            users u ON w.teacher_id = u.telegram_id
        JOIN
            subjects s ON w.subject_id = s.id
        WHERE
            g.name = :group_name;
            ";

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':group_name', $group_name, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getCompletedWorksPerStudentPerSubject(int $student_id): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT 
            CONCAT(u.first_name, ' ', u.middle_name, ' ', u.second_name) AS student_fullname, 
            all_subjects.name AS subject_name,
            (IFNULL(completed_works_count, 0) + IFNULL(pending_works_count, 0)) AS all_works_count,
            IFNULL(completed_works_count, 0) AS completed_works_count,
            IFNULL(pending_works_count, 0) AS pending_works_count
        FROM 
            (SELECT DISTINCT id, name FROM subjects) AS all_subjects
        LEFT JOIN 
            (SELECT 
                s.user_id, 
                w.subject_id, 
                COUNT(cw.work_id) AS completed_works_count
            FROM 
                students s
            LEFT JOIN 
                completed_works cw ON s.user_id = cw.student_id
            LEFT JOIN 
                works w ON cw.work_id = w.id
            LEFT JOIN 
                groups g ON s.group_id = g.id
            WHERE 
                s.user_id = :student_id
                AND g.id = w.group_id -- Проверка соответствия группы студента и группы для задания
            GROUP BY 
                s.user_id, w.subject_id) AS completed_works ON all_subjects.id = completed_works.subject_id
        LEFT JOIN 
            (SELECT 
                w.subject_id,
                COUNT(*) AS pending_works_count
            FROM 
                works w
            LEFT JOIN 
                groups g ON w.group_id = g.id
            WHERE 
                w.id NOT IN (SELECT work_id FROM completed_works WHERE student_id = :student_id)
                AND g.id = (SELECT g.id FROM students st JOIN groups g ON st.group_id = g.id WHERE st.user_id = :student_id) -- Проверка соответствия группы студента и группы для задания
            GROUP BY 
                w.subject_id) AS pending_works ON all_subjects.id = pending_works.subject_id
        LEFT JOIN 
            students s ON s.user_id = :student_id
        LEFT JOIN 
            users u ON s.user_id = u.telegram_id;            
                ";

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getTotalStudentsFromGroup(): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
                SELECT g.name AS group_name, COUNT(*) AS total_students
                FROM students s
                JOIN groups g ON s.group_id = g.id
                GROUP BY s.group_id;
            ";

            $stmt = self::$pdo->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getTotalUncompletedWorksFromSubjectWithStudent(int $user_id): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
                SELECT 
                s.name AS subject, 
                COUNT(*) AS total_works
            FROM 
                works w 
            JOIN 
                subjects s ON w.subject_id = s.id 
            JOIN 
                groups g ON w.group_id = g.id 
            LEFT JOIN 
                completed_works cw ON w.id = cw.work_id AND cw.student_id = :student_id
            WHERE 
                g.name = (SELECT g.name FROM students st JOIN groups g ON st.group_id = g.id WHERE st.user_id = :student_id)
            GROUP BY 
                w.subject_id;
                    ";

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':student_id', $user_id, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getTotalWorksFromSubjectWithGroup(string $group_name): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
                SELECT s.name AS subject, COUNT(*) AS total_works, g.name AS group_name
                FROM works w
                JOIN subjects s ON w.subject_id = s.id
                JOIN groups g ON w.group_id = g.id
                WHERE g.name = :group_name
                GROUP BY w.subject_id;
            ";

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':group_name', $group_name, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }


    public static function getUncompletedTasksDetailsForStudent(int $student_id): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT
            w.task,
            s.name,
            w.start,
            w.end,
            CONCAT(teacher.first_name, ' ', teacher.middle_name, ' ', teacher.second_name) AS teacher_fullname
        FROM
            works w
        LEFT JOIN
            completed_works cw ON w.id = cw.work_id
        LEFT JOIN
            subjects s ON w.subject_id = s.id
        LEFT JOIN
            users teacher ON w.teacher_id = teacher.telegram_id
        LEFT JOIN
            groups g ON w.group_id = g.id   
        LEFT JOIN 
            students st ON st.user_id = :student_id
        WHERE
            cw.student_id IS NULL AND g.id = st.group_id;
        ";

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getUncompletedTasksDetailsForStudentDeadline(int $student_id): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT
                w.task,
                s.name,
                w.start,
                w.end,
                CONCAT(u.first_name, ' ', u.middle_name, ' ', u.second_name) AS teacher_fullname
            FROM
                works w
            LEFT JOIN
                completed_works cw ON w.id = cw.work_id
            LEFT JOIN
                subjects s ON w.subject_id = s.id
            LEFT JOIN
                users u ON w.teacher_id = u.telegram_id
            LEFT JOIN
                groups g ON w.group_id = g.id   
            LEFT JOIN 
                students st ON st.user_id = :student_id
            WHERE
                cw.student_id IS NULL
                AND w.end < CURRENT_DATE
                AND g.id = st.group_id;
        ";

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }


    public static function getUncompletedTasksDetailsFromStudentWeek(int $student_id): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT
            w.task,
            s.name,
            w.start,
            w.end,
            CONCAT(
                u.first_name,
                ' ',
                u.middle_name,
                ' ',
                u.second_name
            ) AS teacher_fullname
        FROM
            works w
        LEFT JOIN completed_works cw ON
            w.id = cw.work_id AND cw.student_id = :student_id
        LEFT JOIN subjects s ON
            w.subject_id = s.id
        LEFT JOIN users u ON
            w.teacher_id = u.telegram_id
        LEFT JOIN
            groups g ON w.group_id = g.id   
        LEFT JOIN 
            students st ON st.user_id = :student_id
        WHERE
            YEARWEEK(w.end, 1) <= YEARWEEK(CURRENT_DATE(), 1) 
            AND cw.student_id IS NULL
            AND g.id = st.group_id;;
        ";

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getWorksThisWeek($student_id, $subject_name = null): array|bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = "
            SELECT
                w.task,
                s.name,
                w.start,
                w.end,
                CONCAT(
                    teacher.first_name,
                    ' ',
                    teacher.middle_name,
                    ' ',
                    teacher.second_name
                ) AS teacher_fullname
            FROM
                works w
            LEFT JOIN completed_works cw ON
                w.id = cw.work_id AND cw.student_id = :student_id
            LEFT JOIN subjects s ON
                w.subject_id = s.id
            LEFT JOIN users teacher ON
                w.teacher_id = teacher.telegram_id
            LEFT JOIN students st ON
                w.group_id = st.group_id
            LEFT JOIN users student ON
                st.user_id = student.telegram_id
            WHERE
                YEARWEEK(w.start, 1) = YEARWEEK(CURRENT_DATE(), 1) AND st.user_id = :student_id";

            if ($student_id === null) {
                return new Exception("User id is null");
            }

            if ($subject_name !== null) {
                $query .= " AND s.name = :subject_name";
            }

            $stmt = self::$pdo->prepare($query);

            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);

            if ($subject_name !== null) {
                $stmt->bindParam(':subject_name', $subject_name, PDO::PARAM_STR);
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function getUserRole($telegramId)
    {
        $stmt = self::$pdo->prepare("SELECT 'student' AS role FROM students WHERE user_id = :telegram_id");
        $stmt->bindParam(':telegram_id', $telegramId);
        $stmt->execute();

        if ($stmt->fetchColumn()) {
            return 'student';
        }

        $stmt = self::$pdo->prepare("SELECT 'teacher' AS role FROM teachers WHERE user_id = :telegram_id");
        $stmt->bindParam(':telegram_id', $telegramId);
        $stmt->execute();

        if ($stmt->fetchColumn()) {
            return 'teacher';
        }

        return 'Guest';
    }
}
