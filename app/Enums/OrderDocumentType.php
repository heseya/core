<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class OrderDocumentType extends Enum
{
    public const OTHER = 'other';
    public const RECEIPT = 'receipt';
    public const INVOICE = 'invoice';
}
