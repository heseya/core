<?php

declare(strict_types=1);

namespace Domain\App\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppDeleteRequest;
use App\Http\Resources\AppResource;
use Domain\App\Dtos\AppIndexDto;
use Domain\App\Dtos\AppInstallDto;
use Domain\App\Dtos\AppUpdatePermissionsDto;
use Domain\App\Models\App;
use Domain\App\Services\AppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

final class AppController extends Controller
{
    public function __construct(private AppService $appService) {}

    public function index(AppIndexDto $dto): JsonResource
    {
        $apps = App::searchByCriteria($dto->toArray())
            ->with('metadata');

        return AppResource::collection($apps->paginate(Config::get('pagination.per_page')));
    }

    public function show(App $app): JsonResource
    {
        return AppResource::make($app);
    }

    public function store(AppInstallDto $dto): JsonResource
    {
        return AppResource::make($this->appService->install($dto));
    }

    public function destroy(App $app, AppDeleteRequest $request): JsonResponse
    {
        $force = $request->has('force') && (!$request->filled('force') || $request->boolean('force'));
        $this->appService->uninstall($app, $force);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function updatePermissions(App $app, AppUpdatePermissionsDto $dto): JsonResource
    {
        $this->appService->updatePermissions($app, $dto);

        return AppResource::make($app);
    }
}
