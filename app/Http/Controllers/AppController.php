<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\AppControllerSwagger;
use App\Http\Requests\AppStoreRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Services\Contracts\AppServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class AppController extends Controller implements AppControllerSwagger
{
    private AppServiceContract $appService;

    public function __construct(AppServiceContract $appService)
    {
        $this->appService = $appService;
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
}
