<?php

namespace App\Enums;

enum MediaAttachmentType: string
{
    case OTHER = 'other';
    case RECEIPT = 'receipt';
    case INVOICE = 'invoice';
}
