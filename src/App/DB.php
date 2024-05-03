<?php

namespace App;

use Exception;
use Longman\TelegramBot\Entities\User;
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

    public static function insertCompletedWorkData(string $task, int $student_id, int $grade): bool
    {
        $work = self::selectWorkData($task);

        if (!$work) {
            return false;
        }

        $stmt = self::$pdo->prepare("INSERT INTO completed_works (teacher_id, student_id, work_id, finish, grade) VALUES (?,?,?,?,?)");

        return $stmt->execute([$work['teacher_id'], $student_id, $work['id'], date('Y-m-d H:i:s'), $grade]);
    }

    public static function insertWorkData(string $task, string $subject_name, int $teacher_id, string $group_name, string $start, string $end): bool
    {
        $group = self::selectGroupData($group_name);
        $subject = self::selectSubjectData($subject_name);
        $teacher = self::selectTeacherData($teacher_id);

        $stmt = self::$pdo->prepare("INSERT INTO works (task, subject_id, teacher_id, group_id, start, end) VALUES (?,?,?,?,?,?)");

        if ($group && $subject && $teacher) {
            return $stmt->execute([$task, $subject['id'], $teacher['user_id'], $group['id'], $start, $end]);
        } else {
            return false;
        }
    }

    public static function insertTeacherSubjectData(int $teacher_id, string $subject_name): bool
    {
        if (self::selectTeacherData($teacher_id)) {
            $stmt = self::$pdo->prepare("INSERT INTO teacher_subject (teacher_id, subject_id) VALUES (?,?)");
            $subject = self::selectSubjectData($subject_name);
            return $stmt->execute([$teacher_id, $subject['id']]);
        } else {
            return false;
        }
    }

    public static function insertSubjectData(string $subject_name): bool
    {
        $subject = DB::selectSubjectData($subject_name);

        if (!$subject) {
            $stmt = self::$pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
            return $stmt->execute([$subject_name]);
        } else {
            return false;
        }
    }

    public static function insertStudentData(int $user_id, string $group_name): bool
    {
        $stmt = self::$pdo->prepare("INSERT INTO students (user_id, group_id) VALUES (?,?)");
        $group = self::selectGroupData($group_name);
        return $stmt->execute([$user_id, $group['id']]);
    }

    public static function insertTeacherData(int $user_id, string|null $group_name): bool
    {
        $stmt = self::$pdo->prepare("INSERT INTO teachers (user_id, group_id) VALUES (?,?)");
        $group = $group_name ? self::selectGroupData($group_name) : ['id' => null];
        return $stmt->execute([$user_id, $group['id']]);
    }

    public static function insertGroupData(string $name, string $fullname): bool|Exception
    {
        if (self::selectGroupData($name)) {
            return new Exception("Group data is available");
        }
        $stmt = self::$pdo->prepare("INSERT INTO groups (name, fullname) VALUES (?,?)");
        return $stmt->execute([$name, $fullname]);
    }

    public static function insertUserData(User $user, array $data): bool|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        if (self::selectUserData($user->getId())) {
            return new Exception("User is already");
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

            return $sth->execute([$telegram_id, $telegram_username, $first_name, $middle_name, $second_name, $email, $phone, $birthday]);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    // TODO PAIR
    public static function insertPairData(string $subject_name, int $teacher_id, string $group_name, string $start, string $end, int $week, int $top_week = 0): bool|Exception
    {
        $group = self::selectGroupData($group_name);
        
        if (!$group) {
            return new Exception("Group data is not available");
        }
        
        $teacher = self::selectTeacherData($teacher_id);
        
        if (!$teacher){
            return new Exception("Teacher data is not available");
        }
        
        $subject = self::selectSubjectData($subject_name);
        if (!$subject){
            return new Exception("Subject data is not available");
        }
        
        if(strtotime($start) > strtotime($end)){
            return new Exception("Data time is not variable");
        }

        // isTimeAvailable Проверка на пересечение с другими парами

        $stmt = self::$pdo->prepare("INSERT INTO pairs (subject_id, teacher_id, group_id, start, end, week, top_week) VALUES (?,?,?,?,?,?,?)");
        return $stmt->execute([$subject['id'], $teacher['user_id'], $group['id'], $start, $end, $week, $top_week]);
    }

    public static function selectUserData(int $telegramId): array|false|Exception
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

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectWorkData(string $task): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        try {
            $query = '
            SELECT * 
            FROM works 
            WHERE task = :task;
            ';

            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':task', $task, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);
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
            $stmt->bindParam(':group_id', $group['id'], PDO::PARAM_STR);
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

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectGroupData(string $name): array|false|Exception
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

            return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public static function selectSubjectData(string $name): array|false|Exception
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

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return new Exception($e->getMessage());
        }
    }

    public static function selectCompletedWorksData(string $task): array|false|Exception
    {
        if (!self::isDbConnected()) {
            return new Exception("DB connection is not connected");
        }

        $work = self::selectWorkData($task);
        if (!$work) {
            return new Exception("Work in not found");
        }

        try {
            $query = '
                SELECT * 
                FROM `completed_works` 
                WHERE `work_id` = :work_id
            ';
            $stmt = self::$pdo->prepare($query);
            $stmt->bindParam(':work_id', $work['id'], PDO::PARAM_STR);
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
            WHERE 
                s.user_id = :student_id
            GROUP BY 
                s.user_id, w.subject_id) AS completed_works ON all_subjects.id = completed_works.subject_id
        LEFT JOIN 
            (SELECT 
                w.subject_id,
                COUNT(*) AS pending_works_count
            FROM 
                works w
            WHERE 
                w.id NOT IN (SELECT work_id FROM completed_works WHERE student_id = :student_id)
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
                CONCAT(u.first_name, ' ', u.middle_name, ' ', u.second_name) AS teacher_fullname
            FROM
                works w
            LEFT JOIN
                completed_works cw ON w.id = cw.work_id AND cw.student_id = :student_id
            LEFT JOIN
                subjects s ON w.subject_id = s.id
            LEFT JOIN
                users u ON w.teacher_id = u.telegram_id
            WHERE
                cw.student_id IS NULL;
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
                completed_works cw ON w.id = cw.work_id AND cw.student_id = :student_id
            LEFT JOIN
                subjects s ON w.subject_id = s.id
            LEFT JOIN
                users u ON w.teacher_id = u.telegram_id
            WHERE
                cw.student_id IS NULL
                AND w.end < CURRENT_DATE;
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
        WHERE
            YEARWEEK(w.end, 1) <= YEARWEEK(CURRENT_DATE(), 1) AND cw.student_id IS NULL;
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

            // Добавляем условие по студенту, если передан его идентификатор
            if ($student_id === null) {
                return new Exception("User id is null");
            }

            // Добавляем условие по названию предмета, если передано его название
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
}
