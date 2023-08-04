<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum MediaAttachmentType: string
{
    use EnumTrait;

    case OTHER = 'other';

    case INVOICE = 'invoice';
    case RECEIPT = 'receipt';
}
