<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @OA\Info(
     *   title="Heseya Store API",
     *   version="1.0.0",
     * )
     */

    /**
     * @OA\SecurityScheme(
     *   type="oauth2",
     *   name="oauth",
     *   securityScheme="oauth",
     *   @OA\Flow(
     *     flow="implicit",
     *     authorizationUrl="https://depth.space/oauth",
     *     scopes={}
     *   )
     * )
     */
}
