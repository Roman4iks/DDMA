<?php

namespace App\class;

class Work {
    public $id;
    public $task;
    public $subject_name;
    public $teacher_id;
    public $group_name;
    public $start;
    public $end;

    public function __construct($task, $subject_id, $teacher_id, $group_name, $start, $end, $id = null) {
        $this->id = $id;
        $this->task = $task;
        $this->subject_name = $subject_id;
        $this->teacher_id = $teacher_id;
        $this->group_name = $group_name;
        $this->start = $start;
        $this->end = $end;
    }
}
