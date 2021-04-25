<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\CountryControllerSwagger;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Resources\Json\JsonResource;

class CountriesController extends Controller implements CountryControllerSwagger
{
    public function index(): JsonResource
    {
        return CountryResource::collection(Country::all());
    }
}
