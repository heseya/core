<?php

namespace App\Http\Controllers\Store;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'brand' => ['string', 'max:80'],
            'category' => ['string', 'max:80'],
            'q' => ['string', 'max:80'],
        ]);

        $query = Product::select([
            'id',
            'name',
            'slug',
            'price',
            'brand_id',
            'category_id',
        ])->with([
            'brand',
            'category',
            'gallery',
        ]);

        if ($request->brand) {
            $query->whereHas('brand', function (Builder $query) use ($request) {
                return $query->where('slug', $request->brand);
            });
        }

        if ($request->category) {
            $query->whereHas('category', function (Builder $query) use ($request) {
                return $query->where('slug', $request->category);
            });
        }

        if ($request->q) {
            $query->where('slug', 'LIKE', '%' . $request->q . '%')
                ->orWhere('name', 'LIKE', '%' . $request->q . '%');
        }

        return response()->json($query->simplePaginate(12));
    }

    public function single(String $slug): JsonResponse
    {
        $product = Product::where(['slug' => $slug])->with([
            'brand',
            'category',
            'gallery',
            'shema',
        ])->first();

        if (empty($product)) {
            abort(404);
        }

        return response()->json($product);
    }
}
