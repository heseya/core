<?php

namespace App\Services\Contracts;

use App\DTO\Page\PageCreateDto;
use App\DTO\Page\PageUpdateDto;
use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PageServiceContract
{
    public function authorize(Page $page): void;

    public function getPaginated(?array $search): LengthAwarePaginator;

    public function create(PageCreateDto $dto): Page;

    public function update(Page $page, PageUpdateDto $dto): Page;

    public function delete(Page $page): void;

    public function reorder(array $pages): void;
}
