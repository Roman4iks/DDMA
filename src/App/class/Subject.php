<?php

namespace App\class;

use App\utils\Validator;

class Subject 
{
    public $id;
    public $name;

    public function __construct($id, $name)
    {
        $this->id = $id;

        if(Validator::validateString($name)){
            $this->name = $name;
        }
    }
}