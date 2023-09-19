<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Dtos\ProductSearchDto;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

class ProductRepository
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::searchByCriteria($dto->toArray())
            ->with(['attributes', 'metadata', 'media', 'tags', 'items'])
            ->sort($dto->getSort());

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }
}
