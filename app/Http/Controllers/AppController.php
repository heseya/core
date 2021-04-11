<?php

namespace App\Http\Controllers;

use App\Http\Resources\AppResource;
use App\Models\App;
use Illuminate\Http\Resources\Json\JsonResource;

class AppController extends Controller
{
    public function index(): JsonResource
    {
        return AppResource::collection(App::paginate(12));
    }
}
