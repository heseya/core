<?php

declare(strict_types=1);

namespace Domain\Consent\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConsentResource;
use Domain\Consent\Dtos\ConsentCreateDto;
use Domain\Consent\Dtos\ConsentUpdateDto;
use Domain\Consent\Models\Consent;
use Domain\Consent\Services\ConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

final class ConsentController extends Controller
{
    public function __construct(private readonly ConsentService $consentService) {}

    public function show(Consent $consent): JsonResource
    {
        return ConsentResource::make($consent);
    }

    public function index(): JsonResource
    {
        return ConsentResource::collection(Consent::all());
    }

    public function store(ConsentCreateDto $dto): JsonResource
    {
        $consent = $this->consentService->store($dto);

        return ConsentResource::make($consent);
    }

    public function update(Consent $consent, ConsentUpdateDto $dto): JsonResource
    {
        $consent = $this->consentService->update($consent, $dto);

        return ConsentResource::make($consent);
    }

    public function destroy(Consent $consent): JsonResponse
    {
        $this->consentService->destroy($consent);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
