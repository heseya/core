<?php

namespace App\Http\Controllers;

use App\Dtos\SeoMetadataDto;
use App\Http\Controllers\Swagger\SeoMetadataControllerSwagger;
use App\Http\Requests\SeoMetadataRequest;
use App\Http\Resources\SeoMetadataResource;
use App\Models\SeoMetadata;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\SeoMetadataService;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoMetadataController extends Controller implements SeoMetadataControllerSwagger
{
    private SeoMetadataService $seoMetadataService;

    public function __construct(SeoMetadataServiceContract $seoMetadataService)
    {
        $this->seoMetadataService = $seoMetadataService;
    }

    public function show(SeoMetadata $seoMetadata): JsonResource
    {
        return SeoMetadataResource::make($this->seoMetadataService->show());
    }

    public function createOrUpdate(SeoMetadataRequest $request): JsonResource
    {
        $seo = SeoMetadataDto::fromFormRequest($request);
        return SeoMetadataResource::make($this->seoMetadataService->createOrUpdate($seo));
    }
}
