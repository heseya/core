<?php

namespace App\Http\Controllers;

use App\Dtos\BannerDto;
use App\Http\Requests\BannerIndexRequest;
use App\Http\Requests\BannerStoreRequest;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use App\Services\Contracts\BannerServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

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
}
