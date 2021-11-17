<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface PermissionResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Permission",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Name of the permission",
     *     example="products.add",
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     description="Description of the permission",
     *     example="Permission to add products",
     *   ),
     *   @OA\Property(
     *     property="assignable",
     *     type="boolean",
     *     example=true,
     *     description="Whether the role assignable by current user.",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
