<?php

namespace App\class;

class Group {
    public string $name;
    public string $fullname;
    public ?string $link;
    public ?int $id;

    public function __construct($name, $fullname, $link = null, $id = null) {
        $this->id = $id;
        $this->name = $name;
        $this->fullname = $fullname;
        $this->link = $link;
    }
}

