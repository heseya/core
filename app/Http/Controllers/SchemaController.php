<?php

namespace App\Http\Controllers;

use App\Dtos\SchemaDto;
use App\Http\Requests\IndexSchemaRequest;
use App\Http\Requests\SchemaStoreRequest;
use App\Http\Requests\SchemaUpdateRequest;
use App\Http\Resources\SchemaResource;
use App\Models\Schema;
use App\Services\Contracts\SchemaCrudServiceContract;
use App\Traits\GetPublishedLanguageFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class SchemaController extends Controller
{
    use GetPublishedLanguageFilter;

    public function __construct(
        private readonly SchemaCrudServiceContract $schemaService,
    ) {}

    public function index(IndexSchemaRequest $request): JsonResource
    {
        $schemas = Schema::searchByCriteria($request->validated() + $this->getPublishedLanguageFilter('schemas'))
            ->sort($request->input('sort'));

        return SchemaResource::collection(
            $schemas->paginate(Config::get('pagination.per_page')),
        );
    }

    public function store(SchemaStoreRequest $request): JsonResource
    {
        return SchemaResource::make($this->schemaService->store(
            SchemaDto::instantiateFromRequest($request)
        ));
    }

    public function show(Schema $schema): JsonResource
    {
        return SchemaResource::make($schema);
    }

    public function update(SchemaUpdateRequest $request, Schema $schema): JsonResource
    {
        return SchemaResource::make($this->schemaService->update(
            $schema,
            SchemaDto::instantiateFromRequest($request)
        ));
    }

    public function destroy(Schema $schema): JsonResponse
    {
        $this->schemaService->destroy($schema);

        return Response::json(null, SymfonyResponse::HTTP_NO_CONTENT);
    }
}
