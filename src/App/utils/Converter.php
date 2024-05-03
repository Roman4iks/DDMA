<?php

namespace App\utils;

class Converter 
{
    public static function MultiArrayToString(array $array, string $separator = ' : ', string $indent = ''): string
    {
        $out_text = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $out_text .= $indent . $key . PHP_EOL;
                $out_text .= self::MultiArrayToString($value, $separator, $indent . '  '); // Рекурсивный вызов для вложенных массивов
            } else {
                $out_text .= $indent . $key . $separator . $value . PHP_EOL;
            }
        }

        return $out_text;
    }
}