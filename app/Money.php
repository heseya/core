<?php

namespace App;

class Money
{
    public static function PLN($value)
    {
        return \number_format($value, 2, ',', ' ') . ' zł';
    }
}
