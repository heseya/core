<?php

namespace App\Http\Controllers;

use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Resources\Json\JsonResource;

class CountriesController extends Controller
{
    public function index(): JsonResource
    {
        return CountryResource::collection(Country::all());
    }
}
