<?php

namespace App\Http\Controllers;

use App\Dtos\MetadataDto;
use App\Http\Resources\MetadataResource;
use App\Models\AttributeOption;
use App\Services\Contracts\MetadataServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

class MetadataController extends Controller
{
    public function __construct(private MetadataServiceContract $metadataService)
    {
    }

    public function updateOrCreate(int|string $modelId, Request $request): JsonResponse | JsonResource
    {
        $modelClass = $this->metadataService->returnModel($request->segments());
        if ($modelClass === null) {
            return Response::json(null, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        # Workaround for attribute option metadata
        $model = $modelClass instanceof AttributeOption ?
            $modelClass->findOrFail($request->route('option')) : $modelClass->findOrFail($modelId);

        $public = Collection::make($request->segments())->last() === 'metadata';
        foreach ($request->all() as $key => $value) {
            $dto = MetadataDto::manualInit(name: $key, value: $value, public: $public);

            $this->metadataService->updateOrCreate(
                $model,
                $dto
            );
        }

        $model->refresh();

        if ($public) {
            return MetadataResource::make($model->metadata);
        }

        return MetadataResource::make($model->metadataPrivate);
    }
}
