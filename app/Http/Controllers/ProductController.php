<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ProductControllerSwagger;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller implements ProductControllerSwagger
{
    public function index(ProductIndexRequest $request): JsonResource
    {
        $query = Product::search($request->validated())->with([
            'brand',
            'category',
            'media',
        ]);

        if (!Auth::check()) {
            $query
                ->where('public', true)
                ->whereHas('brand', fn (Builder $subQuery) => $subQuery->where('public', true))
                ->whereHas('category', fn (Builder $subQuery) => $subQuery->where('public', true));
        }

        if ($request->input('sort')) {
            $sort = explode(',', $request->input('sort'));

            foreach ($sort as $option) {
                $option = explode(':', $option);

                Validator::make($option, [
                    '0' => 'required|in:price,name,created_at,id',
                    '1' => 'in:asc,desc',
                ])->validate();

                $order = count($option) > 1 ? $option[1] : 'asc';
                $query->orderBy($option[0], $order);
            }

        } else {
            $query->orderBy('created_at', 'desc');
        }

        return ProductResource::collection(
            $query->paginate(12),
        );
    }

    public function show(ProductShowRequest $request, Product $product): JsonResource
    {
        return ProductResource::make($product);
    }

    public function store(ProductCreateRequest $request): JsonResource
    {
        foreach ($request->input('schemas', []) as $schema) {
            $items = $schema['items'] ?? [];

            foreach ($items as $item) {
                Validator::make($item, [
                    'item_id' => 'required|uuid|exists:items,id',
                    'extra_price' => 'required|numeric',
                ])->validate();
            }
        }

        $product = Product::create($request->validated());

        $product->update([
            'original_id' => $product->getKey(),
        ]);

//        $requiredPhysicalSchemas = array_filter($schemas, function ($schema) {
//            return $schema['required'] === true && $schema['type'] === 0;
//        });
//
//        if (count($requiredPhysicalSchemas) === 0) {
//            $schema = $product->schemas()->create([
//                'name' => null,
//                'type' => 0,
//                'required' => true,
//            ]);
//
//            $item = Item::create([
//                'name' => $request->input('name'),
//                'sku' => null,
//            ]);
//
//            $schema->schemaItems()->create([
//                'item_id' => $item->id,
//                'extra_price' => 0,
//            ]);
//        }

//        foreach ($schemas as $schema) {
//            $newSchema = $product->schemas()->create([
//                'name' => $schema['name'],
//                'type' => $schema['type'],
//                'required' => $schema['required'],
//            ]);
//
//            if ($schema['type'] !== 0) {
//                continue;
//            }
//
//            foreach ($schema['items'] as $item) {
//                $newSchema->schemaItems()->create([
//                    'item_id' => $item['item_id'],
//                    'extra_price' => $item['extra_price'],
//                ]);
//            }
//        }

        $product->media()->sync($request->input('media', []));

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResponse
    {
//        $schemas = $request->schemas;
//
//        foreach ($schemas as $schema) {
//            Validator::make($schema, [
//                'name' => 'nullable|string|max:255',
//                // Kiedyś trzeba dodać jakieś obiekty typów w kodzie
//                'type' => 'required|integer|min:0|max:1',
//                'required' => 'required|boolean',
//                'items' => 'exclude_unless:type,0|required|array|min:1',
//            ])->validate();
//
//            $items = isset($schema['items']) ? $schema['items'] : [];
//
//            foreach ($items as $item) {
//                Validator::make($item, [
//                    'item_id' => 'required|uuid|exists:items,id',
//                    'extra_price' => 'required|numeric',
//                ])->validate();
//            }
//        }
//
//        $requiredPhysicalSchemas = array_filter($schemas, function ($schema) {
//            return $schema['required'] === true && $schema['type'] === 0;
//        });
//
//        if (count($requiredPhysicalSchemas) === 0) {
//            return Error::abort('No required physical schemas.', 400);
//        }

        $originalId = $product->original_id;

        $product->delete();

        $product = Product::create($request->validated() + [
            'original_id' => $originalId
        ]);

//        foreach ($schemas as $schema) {
//            $newSchema = $product->schemas()->create([
//                'name' => $schema['name'],
//                'type' => $schema['type'],
//                'required' => $schema['required'],
//            ]);
//
//            if ($schema['type'] !== 0) {
//                continue;
//            }
//
//            foreach ($schema['items'] as $item) {
//                $newSchema->schemaItems()->create([
//                    'item_id' => $item['item_id'],
//                    'extra_price' => $item['extra_price'],
//                ]);
//            }
//        }

        $product->media()->sync($request->input('media', []));

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(200);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
