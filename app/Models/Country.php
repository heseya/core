<?php

namespace App\Models;

class Country extends \Illuminate\Database\Eloquent\Model
{
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    public function getKeyName()
    {
        return 'code';
    }
}
