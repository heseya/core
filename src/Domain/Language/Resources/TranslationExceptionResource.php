<?php

declare(strict_types=1);

namespace Domain\Language\Resources;

use App\Http\Resources\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class TranslationExceptionResource extends Resource
{
    public static $wrap = 'error';

    /**
     * @return array<mixed, mixed>
     */
    public function base(Request $request): array
    {
        /** @var Collection<int, mixed> $resource */
        $resource = $this->resource;

        return Collection::make($resource)->toArray();
    }
}
