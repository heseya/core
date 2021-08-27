<?php

namespace App\Dtos;

use App\Dtos\Contracts\DtoContract;

abstract class Dto implements DtoContract
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
