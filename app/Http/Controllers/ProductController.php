<?php

namespace App\Http\Controllers;

use App\Item;
use App\Media;
use App\Error;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\ProductShortResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *   path="/products",
     *   summary="list products",
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Full text search.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="Brand slug.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="category",
     *     in="query",
     *     description="Category slug.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Product"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $request->validate([
            'brand' => ['string', 'max:255'],
            'category' => ['string', 'max:255'],
            'search' => ['string', 'max:255'],
        ]);

        $query = Product::with([
            'brand',
            'category',
            'media',
        ]);

        $query
            ->where('public', true)
            ->whereHas('brand', fn (Builder $subQuery) => $subQuery->where('public', true))
            ->whereHas('category', fn (Builder $subQuery) => $subQuery->where('public', true));

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

        if ($request->search) {
            $query
                ->where('slug', 'LIKE', '%' . $request->search . '%')
                ->orWhere('name', 'LIKE', '%' . $request->search . '%')
                ->orWhereHas('brand', function (Builder $query) use ($request) {
                    return $query->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('slug', 'LIKE', '%' . $request->search . '%');
                })
                ->orWhereHas('category', function (Builder $query) use ($request) {
                    return $query->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('slug', 'LIKE', '%' . $request->search . '%');
                });
        }

        return ProductShortResource::collection(
            $query->paginate(12)
        );
    }

    /**
     * @OA\Get(
     *   path="/products/{slug}",
     *   summary="single product view",
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="slug",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Product"
     *       )
     *     )
     *   )
     * )
     */

    /**
     * @OA\Get(
     *   path="/products/id:{id}",
     *   summary="alias",
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Product"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function view(Product $product)
    {
        if ($product->isPublic() !== true) {
            return Error::abort('Unauthorized.', 401);
        }

        return new ProductResource($product);
    }

    /**
     * @OA\Post(
     *   path="/products",
     *   summary="create product",
     *   tags={"Products"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="name",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="slug",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="price",
     *         type="number",
     *       ),
     *       @OA\Property(
     *         property="brand_id",
     *         type="integer",
     *       ),
     *       @OA\Property(
     *         property="category_id",
     *         type="integer",
     *       ),
     *       @OA\Property(
     *         property="description",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="digital",
     *         type="boolean",
     *       ),
     *       @OA\Property(
     *         property="public",
     *         type="boolean",
     *       ),
     *       @OA\Property(
     *         property="schemas",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="name",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="type",
     *             type="integer",
     *           ),
     *           @OA\Property(
     *             property="required",
     *             type="boolean",
     *           ),
     *           @OA\Property(
     *             property="items",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(
     *                 property="item_id",
     *                 type="integer",
     *               ),
     *               @OA\Property(
     *                 property="extra_price",
     *                 type="number",
     *               )
     *             )
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Product"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products',
            'price' => 'required|numeric',
            'brand_id' => 'required|integer|exists:brands,id',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'string',
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
                    'item_id' => 'required|integer|exists:items,id',
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
                    400,
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
                'name' => $request->name,
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
                    'extra_price' => $item_id['extra_price'],
                ]);
            }
        }
        
        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @OA\Put(
     *   path="/products",
     *   summary="create product",
     *   tags={"Products"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="name",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="slug",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="price",
     *         type="number",
     *       ),
     *       @OA\Property(
     *         property="brand_id",
     *         type="integer",
     *       ),
     *       @OA\Property(
     *         property="category_id",
     *         type="integer",
     *       ),
     *       @OA\Property(
     *         property="description",
     *         type="string",
     *       ),
     *       @OA\Property(
     *         property="digital",
     *         type="boolean",
     *       ),
     *       @OA\Property(
     *         property="public",
     *         type="boolean",
     *       ),
     *       @OA\Property(
     *         property="schemas",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(
     *             property="name",
     *             type="string",
     *           ),
     *           @OA\Property(
     *             property="type",
     *             type="integer",
     *           ),
     *           @OA\Property(
     *             property="required",
     *             type="boolean",
     *           ),
     *           @OA\Property(
     *             property="items",
     *             type="array",
     *             @OA\Items(
     *               type="object",
     *               @OA\Property(
     *                 property="item_id",
     *                 type="integer",
     *               ),
     *               @OA\Property(
     *                 property="extra_price",
     *                 type="number",
     *               )
     *             )
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Product"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Product $product, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->ignore($product->slug, 'slug'),
            ],
            'price' => 'required|numeric',
            'brand_id' => 'required|integer|exists:brands,id',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'string',
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
                    'item_id' => 'required|integer|exists:items,id',
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
                    400,
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
        
        return (new ProductResource($product))
            ->response()
            ->setStatusCode(200);
    }
}
