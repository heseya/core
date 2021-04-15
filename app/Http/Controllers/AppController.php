<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppRequest;
use App\Http\Resources\AppResource;
use App\Models\App;
use App\Services\Contracts\AppServiceContract;
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

    public function store(CreateAppRequest $request): JsonResource
    {
        $app = $this->appService->add($request->input('url'));

        return AppResource::make($app);
    }
}
