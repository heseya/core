<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\SchemaControllerSwagger;
use App\Http\Requests\IndexSchemaRequest;
use App\Http\Requests\SchemaStoreRequest;
use App\Http\Resources\SchemaResource;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class SchemaController extends Controller implements SchemaControllerSwagger
{
    public function index(IndexSchemaRequest $request): JsonResource
    {
        $schemas = Schema::search($request->validated())
            ->sort($request->input('sort'));

        return SchemaResource::collection(
            $schemas->paginate(12),
        );
    }

    public function store(SchemaStoreRequest $request): JsonResource
    {
        $schema = Schema::create($request->validated());

        if ($request->has('options')) {
            foreach ($request->input('options') as $input) {
                $option = $schema->options()->create($input);

                $option->items()->sync($input['items'] ?? []);
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
            foreach ($request->input('options') as $input) {
                if (!isset($input['id'])) {
                    $option = $schema->options()->create($input);
                } else {
                    $option = Option::findOrFail($input['id']);
                    $option->update($input);
                }

                $option->items()->sync($input['items'] ?? []);
            }
        }

        return SchemaResource::make($schema);
    }

    public function destroy(Schema $schema): JsonResponse
    {
        $schema->delete();

        return response()->json(null, 204);
    }

    public function attach(Schema $schema, string $product): JsonResponse
    {
        $product = Product::findOrFail($product);

        $product->schemas()->attach($schema);

        return response()->json(null, 204);
    }

    public function detach(Schema $schema, string $product): JsonResponse
    {
        $product = Product::findOrFail($product);

        $product->schemas()->detach($schema);

        return response()->json(null, 204);
    }
}
