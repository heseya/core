<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface ProfileResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="ProfileView",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Users name",
     *     example="terms-and-conditions",
     *   ),
     *   @OA\Property(
     *     property="avatar",
     *     type="string",
     *     description="Name displayed as a page header",
     *     example="Terms & Conditions",
     *   ),
     *   @OA\Property(
     *     property="permissions",
     *     type="array",
     *     description="Permission names",
     *     @OA\Items(
     *       type="string",
     *       example="roles.show_details",
     *     ),
     *   ),
     * )
     */
    public function base(Request $request): array;

    public function stripedPermissionPrefix(?string $prefix): self;
}
