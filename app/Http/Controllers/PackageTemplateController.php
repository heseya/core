<?php

namespace App\Http\Controllers;

use App\Http\Requests\PackageTemplateIndexRequest;
use App\Http\Resources\PackageTemplateResource;
use App\Models\PackageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class PackageTemplateController extends Controller
{
    public function index(PackageTemplateIndexRequest $request): JsonResource
    {
        $packages = PackageTemplate::searchByCriteria($request->validated())
            ->with(['metadata']);

        return PackageTemplateResource::collection($packages->get());
    }

    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'depth' => 'required|integer',
        ]);

        $package = PackageTemplate::create($validated);

        return PackageTemplateResource::make($package);
    }

    public function update(PackageTemplate $package, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'weight' => 'numeric',
            'width' => 'integer',
            'height' => 'integer',
            'depth' => 'integer',
        ]);

        $package->update($validated);

        return PackageTemplateResource::make($package);
    }

    public function destroy(PackageTemplate $package): JsonResponse
    {
        $package->delete();

        return Response::json(null, 204);
    }
}
