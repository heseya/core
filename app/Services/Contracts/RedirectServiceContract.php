<?php

namespace App\Services\Contracts;

use App\Dtos\RedirectCreateDto;
use App\Dtos\RedirectUpdateDto;
use App\Models\Redirect;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface RedirectServiceContract
{
    public function getPaginated(): LengthAwarePaginator;

    public function create(RedirectCreateDto $dto): Redirect;

    public function update(Redirect $page, RedirectUpdateDto $dto): Redirect;

    public function delete(Redirect $page): void;
}
