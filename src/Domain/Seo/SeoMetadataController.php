<?php

declare(strict_types=1);

namespace Domain\Seo;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeoKeywordsResource;
use App\Http\Resources\SeoMetadataResource;
use Domain\Seo\Dtos\SeoKeywordsDto;
use Domain\Seo\Dtos\SeoMetadataDto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

final class SeoMetadataController extends Controller
{
    public function __construct(
        private readonly SeoMetadataService $seoMetadataService,
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
