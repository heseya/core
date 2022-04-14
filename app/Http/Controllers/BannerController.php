<?php

namespace App\Http\Controllers;

use App\Dtos\BannerDto;
use App\Http\Requests\BannerIndexRequest;
use App\Http\Requests\BannerStoreRequest;
use App\Http\Requests\BannerUpdateRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\Contracts\BannerServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class BannerController extends Controller
{
    public function __construct(private BannerServiceContract $bannerService)
    {
    }

    public function index(BannerIndexRequest $request): JsonResource
    {
        $query = Banner::searchByCriteria($request->validated())
            ->with(['responsiveMedia', 'responsiveMedia.media']);

        return BannerResource::collection(
            $query->paginate(Config::get('pagination.per_page'))
        );
    }

    public function store(BannerStoreRequest $request): JsonResource
    {
        return BannerResource::make(
            $this->bannerService->create(
                BannerDto::instantiateFromRequest($request)
            )
        );
    }

    public function update(Banner $banner, BannerUpdateRequest $request): JsonResource
    {
        return BannerResource::make(
            $this->bannerService->update(
                $banner,
                BannerDto::instantiateFromRequest($request)
            )
        );
    }

    public function destroy(Banner $banner): JsonResponse
    {
        if ($this->bannerService->delete($banner)) {
            return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
        }

        return Response::json(null, JsonResponse::HTTP_CONFLICT);
    }
}
