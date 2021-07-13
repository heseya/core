<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface ProductSetResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Page",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Name displayed to the user",
     *     example="AGD,
     *   ),
     *   @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="Name used in the URL path",
     *     example="agd",
     *   ),
     *   @OA\Property(
     *     property="public",
     *     type="boolean",
     *     example=true,
     *     description="Whether set is visible to unauthorized users",
     *   ),
     *   @OA\Property(
     *     property="hide_on_index",
     *     type="boolean",
     *     example=false,
     *     description="Whether set products should be hidden from the main page",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
