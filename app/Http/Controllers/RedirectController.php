<?php

namespace App\Http\Controllers;

use App\Dtos\RedirectCreateDto;
use App\Dtos\RedirectUpdateDto;
use App\Http\Requests\RedirectCreateRequest;
use App\Http\Requests\RedirectIndexRequest;
use App\Http\Requests\RedirectUpdateRequest;
use App\Http\Resources\RedirectResource;
use App\Models\Redirect;
use App\Services\Contracts\RedirectServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class RedirectController extends Controller
{
    public function __construct(private readonly RedirectServiceContract $redirectService) {}

    public function index(RedirectIndexRequest $request): JsonResource
    {
        return RedirectResource::collection($this->redirectService->getPaginated());
    }

    public function store(RedirectCreateRequest $request): JsonResource
    {
        return RedirectResource::make(
            $this->redirectService->create(RedirectCreateDto::instantiateFromRequest($request))
        );
    }

    public function update(Redirect $redirect, RedirectUpdateRequest $request): JsonResource
    {
        return RedirectResource::make(
            $this->redirectService->update($redirect, RedirectUpdateDto::instantiateFromRequest($request))
        );
    }

    public function destroy(Redirect $redirect): JsonResponse
    {
        $this->redirectService->delete($redirect);

        return Response::json(null, 204);
    }
}
