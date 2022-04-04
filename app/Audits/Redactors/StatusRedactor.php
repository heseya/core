<?php

namespace App\Audits\Redactors;

use App\Models\Status;
use OwenIt\Auditing\Contracts\AttributeRedactor;

class StatusRedactor implements AttributeRedactor
{
    public static function redact(mixed $value): string
    {
        /** @var Status $status */
        $status = Status::find($value);

        if ($status instanceof Status) {
            $value = $status->name;
        }

        return $value;
    }
}
