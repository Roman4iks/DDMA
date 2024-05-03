<?php

use App\utils\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidateStringValid()
    {
        $this->assertTrue(Validator::validateString("ValidString"));
        $this->assertTrue(Validator::validateString("Привет"));
    }
    
    public function testValidateStringInvalid()
    {
        $this->assertFalse(Validator::validateString("123"));
        $this->assertFalse(Validator::validateString("InvalidString123"));
        $this->assertFalse(Validator::validateString(""));
        $this->assertFalse(Validator::validateString("string with spaces"));
        $this->assertFalse(Validator::validateString("string_with_underscores"));
    }

    public function testValidateDateValid()
    {
        $this->assertTrue(Validator::validateDate("2024-05-03"));
        $this->assertTrue(Validator::validateDate("2023-12-31"));
    }

    public function testValidateDateInvalid()
    {
        $this->assertFalse(Validator::validateDate("05-03-2024"));
        $this->assertFalse(Validator::validateDate("2024/05/03"));
        $this->assertFalse(Validator::validateDate("2024-13-01"));
        $this->assertFalse(Validator::validateDate("2024-02-30"));
        $this->assertFalse(Validator::validateDate(""));
    }

    public function testValidateEmailValid()
    {
        $this->assertTrue(Validator::validateEmail("test@example.com"));
        $this->assertTrue(Validator::validateEmail("user123@gmail.com"));
    }

    public function testValidateEmailInvalid()
    {
        $this->assertFalse(Validator::validateEmail("invalid-email")); 
        $this->assertFalse(Validator::validateEmail("email@example")); 
        $this->assertFalse(Validator::validateEmail("email@.com")); 
        $this->assertFalse(Validator::validateEmail("email.com")); 
        $this->assertFalse(Validator::validateEmail("@example")); 
        $this->assertFalse(Validator::validateEmail("@example.com")); 
        $this->assertFalse(Validator::validateEmail("")); 
    }
}

