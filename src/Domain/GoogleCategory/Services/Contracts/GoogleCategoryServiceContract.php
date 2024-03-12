<?php

declare(strict_types=1);

namespace Domain\GoogleCategory\Services\Contracts;

use Illuminate\Support\Collection;

interface GoogleCategoryServiceContract
{
    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function getGoogleProductCategory(string $lang, bool $force = false): Collection;
}
