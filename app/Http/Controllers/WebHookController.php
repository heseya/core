<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\WebHookControllerSwagger;
use App\Http\Requests\WebHookIndexRequest;
use App\Http\Resources\WebHookResource;
use App\Models\WebHook;
use App\Services\Contracts\WebHookServiceContract;
use App\Services\WebHookService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebHookController extends Controller implements WebHookControllerSwagger
{
    private WebHookService $webHookService;

    public function __construct(WebHookServiceContract $webHookService)
    {
        $this->webHookService = $webHookService;
    }

    public function index(WebHookIndexRequest $request): JsonResource
    {
        $webHooks = $this->webHookService->searchAll($request->validated());
        return WebHookResource::collection($webHooks);
    }

    public function store(Request $request)
    {
//        return
    }

    public function update(Request $request, WebHook $webHook)
    {
//        return
    }

    public function destroy(WebHook $webHook)
    {
//        return
    }
}
