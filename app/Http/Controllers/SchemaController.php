<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexSchemaRequest;
use App\Http\Requests\SchemaStoreRequest;
use App\Http\Resources\SchemaResource;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class SchemaController extends Controller
{
    public function __construct(
        protected OptionServiceContract $optionService,
        protected ProductServiceContract $productService,
    ) {
    }

    public function index(IndexSchemaRequest $request): JsonResource
    {
        $schemas = Schema::search($request->validated())->sort($request->input('sort'));

        return SchemaResource::collection(
            $schemas->paginate(Config::get('pagination.per_page')),
        );
    }

    public function store(SchemaStoreRequest $request): JsonResource
    {
        $schema = Schema::create($request->validated());

        if ($request->has('options')) {
            $this->optionService->sync($schema, $request->input('options'));
            $schema->refresh();
        }

        if ($request->has('used_schemas')) {
            foreach ($request->input('used_schemas') as $input) {
                $used_schema = Schema::findOrFail($input);

                $schema->usedSchemas()->attach($used_schema);
            }

            $schema->refresh();
        }

        return SchemaResource::make($schema);
    }

    public function show(Schema $schema): JsonResource
    {
        return SchemaResource::make($schema);
    }

    public function update(SchemaStoreRequest $request, Schema $schema): JsonResource
    {
        $schema->update($request->validated());

        if ($request->has('options')) {
            $this->optionService->sync($schema, $request->input('options'));
            $schema->refresh();
        }

        if ($request->has('used_schemas')) {
            $schema->usedSchemas()->detach();

            foreach ($request->input('used_schemas') as $input) {
                $used_schema = Schema::findOrFail($input);

                $schema->usedSchemas()->attach($used_schema);
            }
        }

        $schema->products->each(
            fn (Product $product) => $this->productService->updateMinMaxPrices($product),
        );

        return SchemaResource::make($schema);
    }

    public function destroy(Schema $schema): JsonResponse
    {
        $products = $schema->products;
        $schema->delete();

        $products->each(
            fn (Product $product) => $this->productService->updateMinMaxPrices($product),
        );

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
