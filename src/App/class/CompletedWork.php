<?php 

namespace App\class;

class CompletedWork
{
    public ?int $teacherId;
    public $studentId;
    public $workId;
    public $finish;
    public ?int $grade;

    public function __construct($studentId, $workId, $finish, $grade = null, $teacherId = null)
    {
        $this->teacherId = $teacherId;
        $this->studentId = $studentId;
        $this->workId = $workId;
        $this->finish = $finish;
        $this->grade = $grade;
    }
}

?>