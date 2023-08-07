<?php

declare(strict_types=1);

namespace Domain\Seo\Enums;

use App\Enums\Traits\EnumTrait;

enum TwitterCardType: string
{
    use EnumTrait;

    case SUMMARY = 'summary';
    case SUMMARY_LARGE_IMAGE = 'summary_large_image';
}
