<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\AppControllerSwagger;
use App\Http\Requests\AppDeleteRequest;
use App\Http\Requests\AppStoreRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Services\Contracts\AppServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class AppController extends Controller implements AppControllerSwagger
{
    public function __construct(private AppServiceContract $appService)
    {
    }

    public function index(): JsonResource
    {
        return AppResource::collection(App::paginate(12));
    }

    public function store(AppStoreRequest $request): JsonResource
    {
        $app = $this->appService->install(
            $request->input('url'),
            $request->input('allowed_permissions'),
            $request->input('name'),
            $request->input('licence_key'),
        );

        return AppResource::make($app);
    }

    public function destroy(App $app, AppDeleteRequest $request): JsonResponse
    {
        $force = $request->has('force') && (!$request->filled('force') || $request->boolean('force'));
        $this->appService->uninstall($app, $force);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
