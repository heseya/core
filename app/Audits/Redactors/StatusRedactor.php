<?php

namespace App\Audits\Redactors;

use App\Models\Status;
use OwenIt\Auditing\Contracts\AttributeRedactor;

class StatusRedactor implements AttributeRedactor
{
    public static function redact(mixed $value): string
    {
        $status = Status::query()->find($value);

        if ($status instanceof Status) {
            return $status->name;
        }

        return $value;
    }
}
