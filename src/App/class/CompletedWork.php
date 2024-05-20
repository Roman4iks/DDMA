<?php 

namespace App\class;

class CompletedWork
{
    public $teacherId;
    public $studentId;
    public $workId;
    public $finish;
    public $grade;

    public function __construct($teacherId, $studentId, $workId, $finish, $grade)
    {
        $this->teacherId = $teacherId;
        $this->studentId = $studentId;
        $this->workId = $workId;
        $this->finish = $finish;
        $this->grade = $grade;
    }
}

?>