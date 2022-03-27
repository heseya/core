<?php

namespace App\Repositories\Contracts;

use App\Dtos\ProductSearchDto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProductRepositoryContract
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator;
}
