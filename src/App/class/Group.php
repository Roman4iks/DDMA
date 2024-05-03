<?php

namespace App\class;

class Group {
    public $id;
    public $name;
    public $fullname;

    public function __construct($id, $name, $fullname) {
        $this->id = $id;
        $this->name = $name;
        $this->fullname = $fullname;
    }
}

