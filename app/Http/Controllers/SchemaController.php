<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\SchemaControllerSwagger;
use App\Http\Requests\IndexSchemaRequest;
use App\Http\Requests\SchemaStoreRequest;
use App\Http\Resources\SchemaResource;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\OptionServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class SchemaController extends Controller implements SchemaControllerSwagger
{
    protected OptionServiceContract $optionService;

    public function __construct(OptionServiceContract $optionService)
    {
        $this->optionService = $optionService;
    }

    public function index(IndexSchemaRequest $request): JsonResource
    {
        $perPage = (int) config('services.pagination.per_page');
        $schemas = Schema::search($request->validated())->sort($request->input('sort'));

        return SchemaResource::collection($schemas->paginate($perPage));
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

        return SchemaResource::make($schema);
    }

    public function destroy(Schema $schema): JsonResponse
    {
        $schema->delete();

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function attach(Schema $schema, string $product): JsonResponse
    {
        $product = Product::findOrFail($product);

        $product->schemas()->attach($schema);

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function detach(Schema $schema, string $product): JsonResponse
    {
        $product = Product::findOrFail($product);

        $product->schemas()->detach($schema);

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
