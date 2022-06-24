<?php

namespace App\Enums;

use App\Traits\EnumUtilities;

enum OrderDocumentType: string
{
    use EnumUtilities;

    case OTHER = 'other';
    case RECEIPT = 'receipt';
    case INVOICE = 'invoice';
}
