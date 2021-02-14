<?php

namespace App\Schemas;

class BooleanSchema
{
    public function validate($input): bool
    {
        return is_bool($input);
    }
}
