<?php

namespace App\Http\Controllers;

use App\Dtos\ConsentDto;
use App\Http\Requests\ConsentStoreRequest;
use App\Http\Requests\ConsentUpdateRequest;
use App\Http\Resources\ConsentResource;
use App\Models\Consent;
use App\Services\Contracts\ConsentServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class ConsentController extends Controller
{
    public function __construct(private ConsentServiceContract $consentService)
    {
    }

    public function index(): JsonResource
    {
        return ConsentResource::collection(Consent::all());
    }

    public function store(ConsentStoreRequest $request): JsonResource
    {
        $consentDto = ConsentDto::instantiateFromRequest($request);
        $consent = $this->consentService->store($consentDto);

        return ConsentResource::make($consent);
    }

    public function update(Consent $consent, ConsentUpdateRequest $request): JsonResource
    {
        $consentDto = ConsentDto::instantiateFromRequest($request);
        $consent = $this->consentService->update($consent, $consentDto);

        return ConsentResource::make($consent);
    }

    public function destroy(Consent $consent): JsonResponse
    {
        $this->consentService->destroy($consent);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
