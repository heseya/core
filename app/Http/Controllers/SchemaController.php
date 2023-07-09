<?php

namespace App\Http\Controllers;

use App\Dtos\SchemaDto;
use App\Exceptions\PublishingException;
use App\Http\Requests\IndexSchemaRequest;
use App\Http\Requests\SchemaStoreRequest;
use App\Http\Requests\SchemaUpdateRequest;
use App\Http\Resources\SchemaResource;
use App\Models\Schema;
use App\Services\Contracts\SchemaCrudServiceContract;
use App\Services\Contracts\OptionServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\TranslationServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SchemaController extends Controller
{
    public function __construct(
        private SchemaCrudServiceContract $schemaService,
        protected OptionServiceContract $optionService,
        protected ProductServiceContract $productService,
        protected TranslationServiceContract $translationService,
    ) {}

    public function index(IndexSchemaRequest $request): JsonResource
    {
        $schemas = Schema::searchByCriteria($request->validated())->sort($request->input('sort'));

        return SchemaResource::collection(
            $schemas->paginate(Config::get('pagination.per_page')),
        );
    }

    /**
     * @throws PublishingException
     */
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

    /**
     * @throws PublishingException
     */
    public function update(SchemaStoreRequest $request, Schema $schema): JsonResource
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
