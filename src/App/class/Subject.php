<?php

namespace App\class;

use App\utils\Validator;

class Subject 
{
    public ?int $id;
    public string $name;

    public function __construct($name, $id = null)
    {
        $this->id = $id;

        if(Validator::validateString($name)){
            $this->name = $name;
        }
    }
}