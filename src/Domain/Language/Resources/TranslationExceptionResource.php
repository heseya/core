<?php

declare(strict_types=1);

namespace Domain\Language\Resources;

use App\Http\Resources\Resource;
use Illuminate\Support\Collection;

final class TranslationExceptionResource extends Resource
{
    public static $wrap = 'error';

    public function base($request): array
    {
        return Collection::make($this->resource)->toArray();
    }
}
