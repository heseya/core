<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Dtos\ProductSearchDto;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

class ProductRepository implements ProductRepositoryContract
{
    public function search(ProductSearchDto $dto): LengthAwarePaginator
    {
        $query = Product::searchByCriteria($dto->toArray())
            ->with(['attributes', 'metadata', 'media', 'tags', 'items'])
            ->sort($dto->getSort());

        if (Gate::denies('products.show_hidden')) {
            $query->where('products.public', true);

            // hide on index: sort and search is always in dto
            if (count($dto->toArray()) <= 2 && $dto->getSearch() === null) {
                $query->whereDoesntHave('sets', function (Builder $query): void {
                    $query->where('hide_on_index', true);
                });
            }
        }

        return $query->paginate(Config::get('pagination.per_page'));
    }
}
