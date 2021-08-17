<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface RoleResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Role",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Name of the role",
     *     example="white-socks",
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     description="Description of the role",
     *     example="White socks",
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

    /**
     * @OA\Schema(
     *   schema="RoleView",
     *   allOf={
     *     @OA\Schema(ref="#/components/schemas/Role"),
     *   },
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
    public function view(Request $request): array;
}
