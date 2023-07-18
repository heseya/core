<?php

namespace App\Http\Controllers;

use App\DTO\SeoMetadata\SeoKeywordsDto;
use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Http\Resources\SeoKeywordsResource;
use App\Http\Resources\SeoMetadataResource;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

final class SeoMetadataController extends Controller
{
    public function __construct(
        private readonly SeoMetadataServiceContract $seoMetadataService,
    ) {}

    public function show(): JsonResponse
    {
        return SeoMetadataResource::make($this->seoMetadataService->show())
            ->response()
            ->setStatusCode(200);
    }

    public function createOrUpdate(SeoMetadataDto $dto): JsonResource
    {
        return SeoMetadataResource::make($this->seoMetadataService->createOrUpdate($dto));
    }

    public function checkKeywords(SeoKeywordsDto $dto): JsonResource
    {
        return SeoKeywordsResource::make($this->seoMetadataService->checkKeywords($dto));
    }
}
