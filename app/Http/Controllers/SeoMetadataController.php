<?php

namespace App\Http\Controllers;

use App\Dtos\SeoKeywordsDto;
use App\Dtos\SeoMetadataDto;
use App\Http\Requests\SeoKeywordsRequest;
use App\Http\Requests\SeoRequest;
use App\Http\Resources\SeoKeywordsResource;
use App\Http\Resources\SeoMetadataResource;
use App\Models\SeoMetadata;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoMetadataController extends Controller
{
    public function __construct(
        private SeoMetadataServiceContract $seoMetadataService,
    ) {
    }

    public function show(SeoMetadata $seoMetadata): JsonResource
    {
        return SeoMetadataResource::make($this->seoMetadataService->show());
    }

    public function createOrUpdate(SeoRequest $request): JsonResource
    {
        $seo = SeoMetadataDto::instantiateFromRequest($request);
        return SeoMetadataResource::make($this->seoMetadataService->createOrUpdate($seo));
    }

    public function checkKeywords(SeoKeywordsRequest $request): JsonResource
    {
        $seo_list = $this->seoMetadataService->checkKeywords(SeoKeywordsDto::instantiateFromRequest($request));
        return SeoKeywordsResource::make($seo_list);
    }
}
