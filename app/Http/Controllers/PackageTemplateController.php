<?php

namespace App\Http\Controllers;

use App\Dtos\PackageTemplateDto;
use App\Http\Requests\PackageTemplateCreateRequest;
use App\Http\Requests\PackageTemplateIndexRequest;
use App\Http\Requests\PackageTemplateUpdateRequest;
use App\Http\Resources\PackageTemplateResource;
use App\Models\PackageTemplate;
use App\Services\Contracts\PackageTemplateServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class PackageTemplateController extends Controller
{
    public function __construct(private PackageTemplateServiceContract $packageTemplateService)
    {
    }

    public function index(PackageTemplateIndexRequest $request): JsonResource
    {
        $packages = PackageTemplate::searchByCriteria($request->validated())
            ->with(['metadata', 'metadataPrivate']);

        return PackageTemplateResource::collection($packages->get());
    }

    public function store(PackageTemplateCreateRequest $request): JsonResource
    {
        return PackageTemplateResource::make(
            $this->packageTemplateService->store(
                PackageTemplateDto::instantiateFromRequest($request)
            )
        );
    }

    public function update(PackageTemplate $package, PackageTemplateUpdateRequest $request): JsonResource
    {
        return PackageTemplateResource::make(
            $this->packageTemplateService->update(
                $package,
                PackageTemplateDto::instantiateFromRequest($request)
            )
        );
    }

    public function destroy(PackageTemplate $package): JsonResponse
    {
        $this->packageTemplateService->destroy($package);

        return Response::json(null, 204);
    }
}
