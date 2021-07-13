<?php

namespace App\Services;

use App\Models\ProductSet;
use App\Services\Contracts\ProductSetServiceContract;
use Illuminate\Support\Facades\Auth;

class ProductSetService implements ProductSetServiceContract
{
    public function searchAll(array $attributes)
    {
        $query = ProductSet::search($attributes)->orderBy('order');

        if (Auth::check()) {
            $query->private();
        }

        return $query->get();
    }

    public function create(array $attributes): ProductSet
    {
        $attributes['order'] = ProductSet::count() + 1;

        return ProductSet::create($attributes);
    }

    public function update(ProductSet $set, array $attributes): ProductSet
    {
        $set->update($attributes);

        return $set;
    }

    public function reorder(array $sets)
    {
        foreach ($sets as $key => $id) {
            ProductSet::where('id', $id)->update(['order' => $key]);
        }
    }

    public function delete(ProductSet $set)
    {
        if ($category->products()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $category->delete();
    }
}
