<?php

namespace Domain\Redirect\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\RedirectResource;
use Domain\Redirect\Dtos\RedirectCreateDto;
use Domain\Redirect\Dtos\RedirectIndexDto;
use Domain\Redirect\Dtos\RedirectUpdateDto;
use Domain\Redirect\Models\Redirect;
use Domain\Redirect\Services\RedirectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class RedirectController extends Controller
{
    public function __construct(private readonly RedirectService $redirectService) {}

    public function index(RedirectIndexDto $dto): JsonResource
    {
        return RedirectResource::collection($this->redirectService->getPaginated($dto));
    }

    public function store(RedirectCreateDto $dto): JsonResource
    {
        return RedirectResource::make(
            $this->redirectService->create($dto)
        );
    }

    public function show(Redirect $redirect): JsonResource
    {
        return RedirectResource::make($redirect);
    }

    public function update(Redirect $redirect, RedirectUpdateDto $dto): JsonResource
    {
        return RedirectResource::make(
            $this->redirectService->update($redirect, $dto)
        );
    }

    public function destroy(Redirect $redirect): JsonResponse
    {
        $this->redirectService->delete($redirect);

        return Response::json(null, 204);
    }
}
