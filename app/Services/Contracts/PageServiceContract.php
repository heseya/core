<?php

namespace App\Services\Contracts;

use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PageServiceContract
{
    public function authorize(Page $page): void;

    public function getPaginated(int $itemsPerPage): LengthAwarePaginator;

    public function create(array $attributes): Page;

    public function update(Page $page, array $attributes): Page;

    public function delete(Page $page): void;

    public function reorder(array $pages): void;
}
