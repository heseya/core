<?php

namespace App\Services\Contracts;

use App\Dtos\PageDto;
use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PageServiceContract
{
    public function authorize(Page $page): void;

    public function getPaginated(?array $search): LengthAwarePaginator;

    public function create(PageDto $dto): Page;

    public function update(Page $page, PageDto $dto): Page;

    public function delete(Page $page): void;

    public function reorder(array $pages): void;
}
