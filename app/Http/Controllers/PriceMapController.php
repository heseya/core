<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceMapCreateRequest;
use App\Http\Requests\PriceMapUpdateRequest;
use App\Http\Resources\PriceMapResource;
use Domain\PriceMap\Dtos\PriceMapCreateDto;
use Domain\PriceMap\Dtos\PriceMapUpdateDto;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class PriceMapController extends Controller
{
    public function __construct(
        private PriceMapService $priceMapService,
    ) {}

    public function index(): JsonResource
    {
        return PriceMapResource::collection($this->priceMapService->list());
    }

    public function store(PriceMapCreateRequest $request): PriceMapResource
    {
        return PriceMapResource::make($this->priceMapService->create(PriceMapCreateDto::from($request)));
    }

    public function update(PriceMap $priceMap, PriceMapUpdateRequest $request): PriceMapResource
    {
        return PriceMapResource::make($this->priceMapService->update($priceMap, PriceMapUpdateDto::from($request)));
    }

    public function destroy(PriceMap $priceMap): HttpResponse
    {
        $this->priceMapService->delete($priceMap);

        return Response::noContent();
    }
}
