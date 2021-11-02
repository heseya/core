<?php

namespace App\Audits\Redactors;

use App\Models\Status;
use OwenIt\Auditing\Contracts\AttributeRedactor;

class StatusRedactor implements AttributeRedactor
{
    /**
     * {@inheritdoc}
     */
    public static function redact($value): string
    {
        /** @var Status $status */
        $status = Status::find($value);

        if ($status instanceof Status) {
            $value = $status->name;
        }

        return $value ?? '';
    }
}
