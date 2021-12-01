<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @OA\Info(
     *   title="Heseya Store API",
     *   description="IMPORTANT!!! Auth locks replaced with permissions:
     *   <https://escl24.atlassian.net/wiki/spaces/HES/pages/6476169217/Autoryzacja>.
     *   You need specific permissions to access each endpoint.",
     *   version="2.0.0",
     * )
     *
     * @OA\SecurityScheme(
     *   type="oauth2",
     *   name="oauth",
     *   securityScheme="oauth",
     *   @OA\Flow(
     *     flow="implicit",
     *     scopes={}
     *   )
     * )
     */
}
