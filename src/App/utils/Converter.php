<?php

namespace App\utils;

use DateTime;

class Converter 
{
    public static function MultiArrayToString(array $array, string $separator = ' : ', string $indent = ''): string
    {
        $out_text = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $out_text .= $indent . $key . PHP_EOL;
                $out_text .= self::MultiArrayToString($value, $separator, $indent . '  ');
            } else {
                $out_text .= $indent . $key . $separator . $value . PHP_EOL;
            }
        }

        return $out_text;
    }

    public static function daysUntilDeadline($deadline) {
        $deadlineDate = new DateTime($deadline);
        
        $today = new DateTime();
        $interval = $today->diff($deadlineDate);

        if ($today <= $deadlineDate) {
            return $interval->days;
        }
        
        return -$interval->days;
    }
}