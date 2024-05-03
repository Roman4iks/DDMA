<?php

namespace App\Test;

require_once __DIR__ . '/../src/App/DB.php';

use App\class\Group;
use App\class\Subject;
use App\class\User as ClassUser;
use App\DB;
use Exception;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Longman\TelegramBot\Entities\User;

class DBTest extends TestCase
{
    protected static $pdo;
    private static $users;
    private static $groups;
    private static $subjects;
    private static $works;
    private static $completed_works;
    private static $pairs;


    public static function setUpBeforeClass(): void
    {
        $config = require __DIR__ . '/../config.php';
        self::$pdo = DB::initialize($config['database']);
        self::assertInstanceOf(PDO::class, self::$pdo);

        $data = require __DIR__ . '/varibles.php';
        self::$users = $data['users'];
        self::$groups = $data['groups'];
        self::$subjects = $data['subjects'];
        self::$completed_works = $data['pairs'];
        self::$works = $data['works'];
        self::$completed_works = $data['completed_works'];
    }

    #[Test]
    public function testInitialize(): void
    {
        $config = require __DIR__ . '/../config.php';
        $pdo = DB::initialize($config['database']);
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->expectException(Exception::class);
        DB::initialize([]);
    }

    #[Test]
    public function testInsertUserData(): void
    {
        foreach (self::$users as $role) {
            foreach ($role as $user) {
                $result = DB::insertUserData($user);
                $this->assertTrue($result);
            }
        }

        $user = self::$users['Teachers'][0];
        $result = DB::insertUserData($user);
        $this->assertInstanceOf(Exception::class, $result);
        $this->assertEquals("User is already", $result->getMessage());
    }

    #[Test]
    #[Depends("testInsertUserData")]
    public function testSelectUserData(): void
    {
        foreach (self::$users as $role) {
            foreach ($role as $user) {
                $expectedResult = $user;
                $result = DB::selectUserData($user->telegram_id);
                $this->assertInstanceOf(ClassUser::class, $result);
                $this->assertEquals($expectedResult, $result);
            }
        }
        $result = DB::selectUserData(100000);
        $this->assertFalse($result);
    }

    #[Test]
    public function testInsertGroupData(): void
    {
        foreach (self::$groups['Groups'] as $group) {
            $result = DB::insertGroupData($group);
            $this->assertTrue($result);
        }

        $result = DB::insertGroupData(self::$groups['Groups'][0]);
        $this->assertInstanceOf(Exception::class, $result, "Group has Database");
    }

    #[Test]
    #[Depends("testInsertGroupData")]
    public function testSelectGroupData(): void
    {
        foreach (self::$groups['Groups'] as $group) {
            $result = DB::selectGroupData($group->name);
            $this->assertInstanceOf(Group::class, $result);
            $this->assertEquals($result, $group);
        }

        $result = DB::selectGroupData("DDDD");
        $this->assertFalse($result);
    }

    #[Test]
    #[Depends("testInsertUserData")]
    public function testInsertTeacherData(): void
    {
        $id = 0;
        foreach (self::$users['Teachers'] as $user) {
            foreach (self::$groups['Teachers'] as $group) {
                if ($id % 2 == 0) {
                    $result = DB::insertTeacherData($user->telegram_id, null);
                    $this->assertTrue($result);
                } else if ($id % 2 == 1) {
                    $result = DB::insertTeacherData($user->telegram_id, $group->name);
                    $this->assertTrue($result);
                }
                $id++;
            }
        }
    }

    #[Test]
    #[Depends("testInsertTeacherData")]
    public function testSelectTeacherData(): void
    {
        foreach (self::$users['Teachers'] as $user) {
                $result = DB::selectTeacherData($user->telegram_id);
                $this->assertIsArray($result);
        }
        // Test select method with invalid user ID
        $result = DB::selectTeacherData(10000000);
        $this->assertFalse($result);
    }

    #[Test]
    #[Depends("testInsertGroupData")]
    public function testSelectAllGroupsData(): void
    {
        $expectedResult = self::$groups['Groups'];
        $result = DB::selectAllGroupsData();
        $this->assertIsArray($result);
        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    #[Depends("testInsertUserData")]
    public function testInsertStudentData(): void
    {
        $numStudents = count(self::$users['Students']);
        $numGroups = count(self::$groups['Students']);
        $studentsPerGroup = ceil($numStudents / $numGroups);

        $studentIndex = 0;
        foreach (self::$groups['Students'] as $group) {
            for ($i = 0; $i < $studentsPerGroup && $studentIndex < $numStudents; $i++) {
                $student = self::$users['Students'][$studentIndex];
                $result = DB::insertStudentData($student->telegram_id, $group->name);
                $this->assertTrue($result);
                $studentIndex++;
            }
        }
    }

    #[Test]
    #[Depends("testInsertUserData")]
    #[Depends("testInsertStudentData")]
    public function testSelectStudentData(): void
    {
        foreach (self::$users['Students'] as $user) {
            $result = DB::selectStudentData($user->telegram_id);
            $expectedKeys = ["user_id", "group_id"];
            // Проверяем, что результат не равен false
            $this->assertNotFalse($result, "Result is false for user ID: " . $user->telegram_id);

            // Проверяем, что результат содержит ожидаемые ключи
            $this->assertIsArray($result, "Result is not an array for user ID: " . $user->telegram_id);
            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $result, "Key '$key' is missing in result for user ID: " . $user->telegram_id);
            }
        }
        // Test select method with invalid user ID
        $result = DB::selectStudentData(10000000);
        $this->assertInstanceOf(Exception::class, $result);
    }

    #[Test]
    public function testInsertSubjectData(): void
    {
        foreach (self::$subjects as $subject) {
            $result = DB::insertSubjectData($subject->name);
            $this->assertTrue($result);
        }
    }

    #[Test]
    #[Depends("testInsertSubjectData")]
    public function testSelectSubjectData(): void
    {
        foreach (self::$subjects as $subject) {
            $result = DB::selectSubjectData($subject->name);
            $this->assertInstanceOf(Subject::class, $result);
        }

        $result = DB::selectSubjectData("Nothing");
        $this->assertFalse($result);
    }

    #[Test]
    #[Depends("testInsertTeacherData")]
    #[Depends("testInsertSubjectData")]
    public function testInsertTeacherSubjectData(): void
    {
        foreach (self::$users['Teachers'] as $user) {

            $user_id = $user->telegram_id;

            foreach (self::$subjects as $subject) {
                $status = DB::insertTeacherSubjectData($user_id, $subject->name);
                $this->assertTrue($status);
            }
        }
    }

    // TODO PAIR
    // #[Test] 
    // #[Depends("testInsertTeacherData")]
    // #[Depends("testInsertGroupData")]
    // #[Depends("testInsertSubjectData")]
    // public function testInsertPairData(): void 
    // {
    //     foreach (self::$pairs as $pair) {
    //         foreach (self::$subjects as $subject){
    //             foreach (self::$groups["Groups"] as $group){
    //                 foreach (self::$users['Teachers'] as $teacher){
    //                     $result = DB::insertPairData($subject->name, $teacher->telegram_id, $group->name, $pair['start'], $pair['end'], $pair['week']);
    //                     $this->assertTrue($result);
    //                 }
    //             }
    //         }
    //     }
    // }

    #[Test]
    #[Depends("testInsertTeacherSubjectData")]
    public function testSelectTeacherSubjectData(): void
    {
        foreach (self::$subjects as $subject) {
            $result = DB::selectSubjectData($subject->name);
            $this->assertInstanceOf(Subject::class ,$result);
        }

        $result = DB::selectSubjectData("Nothing");
        $this->assertFalse($result);
    }

    #[Test]
    #[Depends("testInsertTeacherData")]
    #[Depends("testInsertGroupData")]
    #[Depends("testInsertSubjectData")]
    public function testInsertWorkData(): void
    {
        foreach (self::$works[1] as $work) {
            $result = DB::insertWorkData($work['task'], $work['subject_id'], $work['teacher_id'], $work['group_id'], $work['start'], $work['end']);
            $this->assertTrue($result);
        }

        foreach (self::$works[0] as $work) {
            $result = DB::insertWorkData($work['task'], $work['subject_id'], $work['teacher_id'], $work['group_id'], $work['start'], $work['end']);
            $this->assertFalse($result);
        }
    }

    // #[Test]
    // #[Depends("testInsertWorkData")]
    // public function testSelectWorkData(): void
    // {
    //     foreach (self::$works[1] as $work) {
    //         $result = DB::selectWorkData($work['group_id'], $work['teacher_id']);
    //         $this->assertIsArray($result);

    //         // Убедимся, что результат не пустой
    //         $this->assertNotEmpty($result);

    //         if (count($result) > 1) {
    //             // Перебираем все записи в результате
    //             foreach ($result as $row) {
    //                 // Сравниваем каждую запись с ожидаемыми данными
    //                 $this->assertEquals($work['task'], $row['task']);
    //                 $this->assertEquals(DB::selectSubjectData($work['subject_id'])['id'], $row['subject_id']);
    //                 $this->assertEquals((string) $work['teacher_id'], $row['teacher_id']);
    //                 $this->assertEquals(DB::selectGroupData($work['group_id'])['id'], $row['group_id']);
    //                 $this->assertEquals(date("Y-m-d H:i:s", strtotime($work['start'])), $row['start']);
    //                 $this->assertEquals(date("Y-m-d H:i:s", strtotime($work['end'])), $row['end']);
    //             }
    //     }
    // }
    //     $result = DB::selectWorkData("PPPP", self::$users['Teachers'][0]->telegram_id);
    //     $this->assertEquals($result->getMessage(), "Group not found");

    //     $result = DB::selectWorkData(self::$works[1][0]['group_id'], 100000000);
    //     $this->assertEquals($result->getMessage(), "Teacher not found");
    // }
    #[Test]
    #[Depends('testInsertWorkData')]
    #[Depends('testInsertStudentData')]
    #[Depends('testInsertTeacherData')]
    public function testInsertCompletedWorkData(): void 
    {   
        foreach(self::$completed_works as $i => $completeWork){
                self::$completed_works[$i]['task'] = self::$works[1][$i]['task'];
                $result = DB::insertCompletedWorkData(self::$works[1][$i]['task'], $completeWork['student_id'], $completeWork['grade']);
                $this->assertTrue($result);
            }
    }

    #[Test]
    #[Depends('testInsertCompletedWorkData')]
    public function testSelectCompletedWorksData(): void 
    {
        foreach(self::$completed_works as $completedWorks){
            $result = DB::selectCompletedWorksData($completedWorks['task']);
            $this->assertIsArray($result);

            $expectedKeys = ['teacher_id', 'student_id', 'work_id', 'finish', 'grade'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }


    #[Test]
    #[Depends('testInsertWorkData')]
    #[Depends('testInsertStudentData')]
    public function testGetWorksGroupData(): void
    {
        foreach (self::$groups['Students'] as $group) {
            $result = DB::getWorksGroupData($group->name);
            $this->assertIsArray($result);

            $expectedKeys = ['work_id', 'task', 'teacher_full_name', 'subject', 'start', 'end'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    #[Test]
    public function testGetWorksThisWeek(): void
    {
        foreach(self::$users['Students'] as $student){
            $result = DB::getWorksThisWeek($student->telegram_id);
            
            $expectedKeys = ['task', 'name', 'start', 'end', 'teacher_fullname'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    #[Test]
    public function testGetUncompletedTasksDetailsFromStudentWeek(): void 
    {
        foreach(self::$users['Students'] as $student){
            $result = DB::getUncompletedTasksDetailsFromStudentWeek($student->telegram_id);
            
            $expectedKeys = ['task', 'name', 'start', 'end', 'teacher_fullname'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    #[Test]
    public function testGetUncompletedTasksDetailsForStudentDeadline(): void
    {
        foreach(self::$users['Students'] as $student){
            $result = DB::getUncompletedTasksDetailsForStudentDeadline($student->telegram_id);
            
            $expectedKeys = ['task', 'name', 'start', 'end', 'teacher_fullname'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    #[Test]
    public function testGetUncompletedTasksDetailsForStudent(): void 
    {
        foreach(self::$users['Students'] as $student){
            $result = DB::getUncompletedTasksDetailsForStudent($student->telegram_id);
            
            $expectedKeys = ['task', 'name', 'start', 'end', 'teacher_fullname'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    #[Test]
    public function testGetTotalWorksFromSubjectWithGroup(): void 
    {
        foreach(self::$groups['Groups'] as $group){
            $result = DB::getTotalWorksFromSubjectWithGroup($group->name);
            
            $expectedKeys = ['subject', 'total_works', 'group_name'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    #[Test]
    public function testGetTotalStudentsFromGroup(): void 
    {
        foreach(self::$groups['Groups'] as $group){
            $result = DB::getTotalStudentsFromGroup($group->name);
            
            $expectedKeys = ['group_name', 'total_students'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    #[Test]
    public function testGetCompletedWorksPerStudentPerSubject(): void
    {
        foreach(self::$users['Students'] as $student){
            $result = DB::getCompletedWorksPerStudentPerSubject($student->telegram_id);
            
            $expectedKeys = ['student_fullname', 'subject_name', 'completed_works_count', 'pending_works_count', 'all_works_count'];
            foreach ($result as $row) {
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $row, "Key '$key' is missing in result row");
                }
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        try {
            foreach (self::$users as $role) {
                foreach ($role as $user) {
                    $user_id = $user->telegram_id;
                    $stmt = self::$pdo->prepare('DELETE FROM users WHERE telegram_id = :telegram_id');
                    $stmt->bindParam(':telegram_id', $user_id, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        try {
            foreach (self::$groups['Groups'] as $group) {
                $stmt = self::$pdo->prepare('DELETE FROM groups WHERE name = :name');
                $stmt->bindParam(':name', $group->name, PDO::PARAM_STR);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        try {
            foreach (self::$subjects as $subject) {
                $stmt = self::$pdo->prepare('DELETE FROM subjects WHERE name = :name');
                $stmt->bindParam(':name', $subject->name, PDO::PARAM_STR);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        try {
            foreach (self::$works as $work) {
                $stmt = self::$pdo->prepare('DELETE FROM works WHERE task = :name');
                $stmt->bindParam(':name', $work['task'], PDO::PARAM_STR);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        try {
            foreach (self::$completed_works as $completeWork) {
                $stmt = self::$pdo->prepare('DELETE FROM completed_works WHERE work_id = :work_id');

                $work_data = DB::selectWorkData($completeWork['task']);
                if ($work_data !== false && isset($work_data['work_id'])) {
                    $work_id = $work_data['work_id'];
                } else {
                   continue;
                }
                
                $stmt->bindParam(':work_id', $work_id, PDO::PARAM_STR);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }

        try {
            $stmt = self::$pdo->prepare('
            ALTER TABLE groups AUTO_INCREMENT = 1;
            ALTER TABLE works AUTO_INCREMENT = 1;
            ALTER TABLE subjects AUTO_INCREMENT = 1;');
            $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
        // Clean up PDO instance after testing
        self::$pdo = null;
    }
}


class CustomUser extends User
{

    public function __construct(int $id, string $username)
    {
        $this->id = $id;
        $this->username = $username;
    }
    // Добавим метод getId, если его нет в базовом классе User
    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }
}
