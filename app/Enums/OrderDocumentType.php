<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class OrderDocumentType extends Enum
{
    const OTHER = 'other';
    const RECEIPT = 'receipt';
    const INVOICE = 'invoice';
}
