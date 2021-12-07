<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebHookCreateRequest;
use App\Http\Requests\WebHookIndexRequest;
use App\Http\Requests\WebHookUpdateRequest;
use App\Http\Resources\WebHookResource;
use App\Models\WebHook;
use App\Services\Contracts\WebHookServiceContract;
use App\Services\WebHookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class WebHookController extends Controller
{
    private WebHookService $webHookService;

    public function __construct(WebHookServiceContract $webHookService)
    {
        $this->webHookService = $webHookService;
    }

    public function index(WebHookIndexRequest $request): JsonResource
    {
        $webHooks = $this->webHookService->searchAll($request->validated(), $request->input('sort'));
        return WebHookResource::collection($webHooks);
    }

    public function show(WebHook $webHook): JsonResource
    {
        return WebHookResource::make($webHook);
    }

    public function store(WebHookCreateRequest $request): JsonResource
    {
        $attributes = $request->validated();
        Gate::inspect('create', [WebHook::class, $attributes]);

        $webHook = $this->webHookService->create($attributes);
        return WebHookResource::make($webHook);
    }

    public function update(WebHook $webHook, WebHookUpdateRequest $request): JsonResource
    {
        $attributes = $request->validated();
        Gate::inspect('update', [$webHook, $attributes]);

        $webHook = $this->webHookService->update($webHook, $attributes);
        return WebHookResource::make($webHook);
    }

    public function destroy(WebHook $webHook): JsonResponse
    {
        Gate::inspect('delete', $webHook);

        $this->webHookService->delete($webHook);
        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
