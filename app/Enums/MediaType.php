<?php

namespace App\Enums;

enum MediaType: string
{
    case OTHER = 'other';
    case PHOTO = 'photo';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
}
