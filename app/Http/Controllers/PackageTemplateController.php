<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\PackageTemplateControllerSwagger;
use App\Http\Resources\PackageResource;
use App\Models\PackageTemplate;
use Illuminate\Http\Request;

class PackageTemplateController extends Controller implements PackageTemplateControllerSwagger
{
    public function index()
    {
        $packages = PackageTemplate::all();

        return PackageResource::collection($packages);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'width' => 'required|integer',
            'height' => 'required|integer',
            'depth' => 'required|integer',
        ]);

        $package = PackageTemplate::create($validated);

        return PackageResource::make($package)
            ->response()
            ->setStatusCode(201);
    }

    public function update(PackageTemplate $package, Request $request)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'weight' => 'numeric',
            'width' => 'integer',
            'height' => 'integer',
            'depth' => 'integer',
        ]);

        $package->update($validated);

        return PackageResource::make($package);
    }

    public function delete(PackageTemplate $package)
    {
        $package->delete();

        return response()->json(null, 204);
    }
}
