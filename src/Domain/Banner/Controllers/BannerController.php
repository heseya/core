<?php

declare(strict_types=1);

namespace Domain\Banner\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\BannerIndexRequest;
use Domain\Banner\Dtos\BannerCreateDto;
use Domain\Banner\Dtos\BannerUpdateDto;
use Domain\Banner\Models\Banner;
use Domain\Banner\Resources\BannerResource;
use Domain\Banner\Services\BannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

final class BannerController extends Controller
{
    public function __construct(private readonly BannerService $bannerService) {}

    public function index(BannerIndexRequest $request): JsonResource
    {
        $query = Banner::searchByCriteria($request->validated())
            ->with(['bannerMedia', 'bannerMedia.media', 'metadata', 'metadataPrivate']);

        return BannerResource::collection(
            $query->paginate(Config::get('pagination.per_page')),
        );
    }

    public function show(Banner $banner): JsonResource
    {
        $banner->load(['bannerMedia', 'bannerMedia.media', 'metadata', 'metadataPrivate']);

        return BannerResource::make($banner);
    }

    public function store(BannerCreateDto $dto): JsonResource
    {
        return BannerResource::make($this->bannerService->create($dto));
    }

    public function update(Banner $banner, BannerUpdateDto $dto): JsonResource
    {
        return BannerResource::make($this->bannerService->update($banner, $dto));
    }

    public function destroy(Banner $banner): JsonResponse
    {
        if ($this->bannerService->delete($banner)) {
            return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
        }

        return Response::json(null, JsonResponse::HTTP_CONFLICT);
    }
}
