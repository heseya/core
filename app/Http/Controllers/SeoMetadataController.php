<?php

namespace App\Http\Controllers;

use App\Dtos\SeoKeywordsDto;
use App\Dtos\SeoMetadataDto;
use App\Http\Requests\SeoKeywordsRequest;
use App\Http\Requests\SeoMetadataRequest;
use App\Http\Resources\SeoKeywordsResource;
use App\Http\Resources\SeoMetadataResource;
use App\Models\SeoMetadata;
use App\Services\Contracts\SeoMetadataServiceContract;
use App\Services\SeoMetadataService;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoMetadataController extends Controller
{
    private SeoMetadataService $seoMetadataService;

    public function __construct(SeoMetadataServiceContract $seoMetadataService)
    {
        $this->seoMetadataService = $seoMetadataService;
    }

    public function show(): JsonResource
    {
        return SeoMetadataResource::make($this->seoMetadataService->show());
    }

    public function createOrUpdate(SeoMetadataRequest $request): JsonResource
    {
        $seo = SeoMetadataDto::fromFormRequest($request);
        return SeoMetadataResource::make($this->seoMetadataService->createOrUpdate($seo));
    }

    public function checkKeywords(SeoKeywordsRequest $request): JsonResource
    {
        $seo_list = $this->seoMetadataService->checkKeywords(SeoKeywordsDto::fromFormRequest($request));
        return SeoKeywordsResource::make($seo_list);
    }
}
