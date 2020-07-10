<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Exceptions\Error;
use Illuminate\Http\Request;
use App\Http\Resources\PackageResource;
use App\Http\Controllers\Swagger\PackageControllerSwagger;

class PackageController extends Controller implements PackageControllerSwagger
{
    public function index()
    {
        $packages = Package::all();

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

        $package = Package::create($validated);

        return PackageResource::make($package)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Package $package, Request $request)
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

    public function delete(Package $package)
    {
        $package->delete();

        return response()->json(null, 204);
    }
}
