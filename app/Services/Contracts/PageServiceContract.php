<?php

namespace App\Services\Contracts;

use App\Http\Requests\PageOrderRequest;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

interface PageServiceContract
{
    public function authorize(Page $page);

    public function getPaginated(int $itemsPerPage);

    public function create(array $attributes): Page;

    public function update(Page $page, array $attributes): Page;

    public function delete(Page $page);

    public function order(PageOrderRequest $request): JsonResponse;
}
