<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\ProductControllerSwagger;
use App\Http\Resources\ProductResource;
use App\Models\Item;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller implements ProductControllerSwagger
{
    public function index(Request $request): JsonResource
    {
        $request->validate([
            'brand' => 'string|max:255',
            'category' => 'string|max:255',
            'search' => 'string|max:255',
            'sort' => 'string',
        ]);

        $query = Product::with([
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

        if ($request->input('brand')) {
            $query->whereHas('brand', function (Builder $query) use ($request) {
                return $query->where('slug', $request->input('brand'));
            });
        }

        if ($request->input('category')) {
            $query->whereHas('category', function (Builder $query) use ($request) {
                return $query->where('slug', $request->input('category'));
            });
        }

        if ($request->search) {
            $query
                ->where('slug', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhere('name', 'LIKE', '%' . $request->input('search') . '%')
                ->orWhereHas('brand', function (Builder $query) use ($request) {
                    return $query->where('name', 'LIKE', '%' . $request->input('search') . '%')
                        ->orWhere('slug', 'LIKE', '%' . $request->input('search') . '%');
                })
                ->orWhereHas('category', function (Builder $query) use ($request) {
                    return $query->where('name', 'LIKE', '%' . $request->input('search') . '%')
                        ->orWhere('slug', 'LIKE', '%' . $request->input('search') . '%');
                });
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

    public function show(Product $product)
    {
        if (!Auth::check() && $product->isPublic() !== true) {
            return Error::abort('Unauthorized.', 401);
        }

        return ProductResource::make($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products|alpha_dash',
            'price' => 'required|numeric',
            'brand_id' => 'required|uuid|exists:brands,id',
            'category_id' => 'required|uuid|exists:categories,id',
            'description_md' => 'string|nullable',
            'digital' => 'required|boolean',
            'public' => 'required|boolean',
            'schemas' => 'array|nullable',
            'media' => 'array|nullable',
        ]);

        $schemas = isset($request->schemas) ? $request->schemas : [];

        foreach ($schemas as $schema) {
            Validator::make($schema, [
                'name' => 'required|string|max:255',
                // Kiedyś trzeba dodać jakieś obiekty typów w kodzie
                'type' => 'required|integer|min:0|max:1',
                'required' => 'required|boolean',
                'items' => 'exclude_unless:type,0|required|array|min:1',
            ])->validate();

            $items = isset($schema['items']) ? $schema['items'] : [];

            foreach ($items as $item) {
                Validator::make($item, [
                    'item_id' => 'required|uuid|exists:items,id',
                    'extra_price' => 'required|numeric',
                ])->validate();
            }
        }

        $media = isset($request->media) ? $request->media : [];

        foreach ($media as $id) {
            $thisMedia = Media::find($id);

            if ($thisMedia === null) {
                return Error::abort(
                    'Media with ID ' . $id . ' does not exist.',
                    404,
                );
            }
        }

        $product = Product::create($request->all());

        $product->update([
            'original_id' => $product->id,
        ]);

        $requiredPhysicalSchemas = array_filter($schemas, function ($schema) {
            return $schema['required'] === true && $schema['type'] === 0;
        });

        if (count($requiredPhysicalSchemas) === 0) {
            $schema = $product->schemas()->create([
                'name' => null,
                'type' => 0,
                'required' => true,
            ]);

            $item = Item::create([
                'name' => $request->input('name'),
                'sku' => null,
            ]);

            $schema->schemaItems()->create([
                'item_id' => $item->id,
                'extra_price' => 0,
            ]);
        }

        foreach ($schemas as $schema) {
            $newSchema = $product->schemas()->create([
                'name' => $schema['name'],
                'type' => $schema['type'],
                'required' => $schema['required'],
            ]);

            if ($schema['type'] !== 0) {
                continue;
            }

            foreach ($schema['items'] as $item) {
                $newSchema->schemaItems()->create([
                    'item_id' => $item['item_id'],
                    'extra_price' => $item['extra_price'],
                ]);
            }
        }

        $product->media()->sync($media);

        return ProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Product $product, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('products')->ignore($product->slug, 'slug'),
            ],
            'price' => 'required|numeric',
            'brand_id' => 'required|uuid|exists:brands,id',
            'category_id' => 'required|uuid|exists:categories,id',
            'description_md' => 'string|nullable',
            'digital' => 'required|boolean',
            'public' => 'required|boolean',
            'schemas' => 'required|array|min:1',
            'media' => 'array|nullable',
        ]);

        $schemas = $request->schemas;

        foreach ($schemas as $schema) {
            Validator::make($schema, [
                'name' => 'nullable|string|max:255',
                // Kiedyś trzeba dodać jakieś obiekty typów w kodzie
                'type' => 'required|integer|min:0|max:1',
                'required' => 'required|boolean',
                'items' => 'exclude_unless:type,0|required|array|min:1',
            ])->validate();

            $items = isset($schema['items']) ? $schema['items'] : [];

            foreach ($items as $item) {
                Validator::make($item, [
                    'item_id' => 'required|uuid|exists:items,id',
                    'extra_price' => 'required|numeric',
                ])->validate();
            }
        }

        $requiredPhysicalSchemas = array_filter($schemas, function ($schema) {
            return $schema['required'] === true && $schema['type'] === 0;
        });

        if (count($requiredPhysicalSchemas) === 0) {
            return Error::abort('No required physical schemas.', 400);
        }

        $media = isset($request->media) ? $request->media : [];

        foreach ($media as $id) {
            $thisMedia = Media::find($id);

            if ($thisMedia === null) {
                return Error::abort(
                    'Media with ID ' . $id . ' does not `exist`.',
                    404,
                );
            }
        }

        $originalId = $product->original_id;

        $product->delete();

        $product = Product::create($request->all() + [
            'original_id' => $originalId
        ]);

        foreach ($schemas as $schema) {
            $newSchema = $product->schemas()->create([
                'name' => $schema['name'],
                'type' => $schema['type'],
                'required' => $schema['required'],
            ]);

            if ($schema['type'] !== 0) {
                continue;
            }

            foreach ($schema['items'] as $item) {
                $newSchema->schemaItems()->create([
                    'item_id' => $item['item_id'],
                    'extra_price' => $item['extra_price'],
                ]);
            }
        }

        $product->media()->sync($media);

        return ProductResource::make($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
