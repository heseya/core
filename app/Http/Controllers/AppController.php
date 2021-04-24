<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Requests\CreateAppRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Services\Contracts\AppServiceContract;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class AppController extends Controller
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

    public function store(CreateAppRequest $request): JsonResponse
    {
        try {
            $app = $this->appService->register($request->input('url'));
        } catch (Exception $exception) {
            return Error::abort('App responded with error', 400);
        }

        return AppResource::make($app)->response();
    }
}
