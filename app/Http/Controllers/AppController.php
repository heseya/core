<?php

namespace App\Http\Controllers;

use App\Dtos\AppInstallDto;
use App\Http\Requests\AppDeleteRequest;
use App\Http\Requests\AppIndexRequest;
use App\Http\Requests\AppStoreRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Services\Contracts\AppServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class AppController extends Controller
{
    public function __construct(private AppServiceContract $appService)
    {
    }

    public function index(AppIndexRequest $request): JsonResource
    {
        $apps = App::search($request->validated())
            ->with('metadata');

        return AppResource::collection($apps->paginate(Config::get('pagination.per_page')));
    }

    public function show(App $app): JsonResource
    {
        return AppResource::make($app);
    }

    public function store(AppStoreRequest $request): JsonResource
    {
        $app = $this->appService->install(
            AppInstallDto::fromAppStoreRequest($request),
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
