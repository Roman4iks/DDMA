<?php

use App\utils\Converter;
use PHPUnit\Framework\TestCase;

class ConverterTest extends TestCase
{
    public function testMultiArrayToString()
    {
        $array = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'subkey1' => 'subvalue1',
                'subkey2' => 'subvalue2',
            ],
            'key4' => [
                'subkey3' => [
                    'subsubkey1' => 'subsubvalue1'
                ]
            ]
        ];

        $expectedOutput = "key1 : value1\r\nkey2 : value2\r\nkey3\r\n  subkey1 : subvalue1\r\n  subkey2 : subvalue2\r\nkey4\r\n  subkey3\r\n    subsubkey1 : subsubvalue1\r\n";

        $this->assertEquals($expectedOutput, Converter::MultiArrayToString($array));
    }

    public function testMultiArrayToStringWithCustomSeparator()
    {
        $array = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $expectedOutput = "key1 <=> value1\r\nkey2 <=> value2\r\n";

        $this->assertEquals($expectedOutput, Converter::MultiArrayToString($array, ' <=> '));
    }

    public function testMultiArrayToStringWithCustomIndent()
    {
        $array = [
            'key1' => [
                'subkey1' => 'subvalue1',
                'subkey2' => 'subvalue2',
            ],
        ];

        $expectedOutput = "  key1\r\n    subkey1 : subvalue1\r\n    subkey2 : subvalue2\r\n";

        $this->assertEquals($expectedOutput, Converter::MultiArrayToString($array, ' : ', '  '));
    }

    public function testMultiArrayToStringWithEmptyArray()
    {
        $array = [];

        $this->assertEquals('', Converter::MultiArrayToString($array));
    }
}