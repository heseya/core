<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\PackageTemplateControllerSwagger;
use App\Http\Resources\PackageTemplateResource;
use App\Models\PackageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageTemplateController extends Controller implements PackageTemplateControllerSwagger
{
    public function index(): JsonResource
    {
        $packages = PackageTemplate::all();

        return PackageTemplateResource::collection($packages);
    }

    public function store(Request $request)
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

    public function destroy(PackageTemplate $package)
    {
        $package->delete();

        return response()->json(null, 204);
    }
}
