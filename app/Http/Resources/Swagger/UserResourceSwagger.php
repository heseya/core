<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface UserResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="User",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="email",
     *     type="string",
     *     description="User's email address",
     *     example="email@example.com",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="User's displayed name",
     *     example="Johny Mielony",
     *   ),
     *   @OA\Property(
     *     property="avatar",
     *     type="string",
     *     description="User's avatar url",
     *     example="//www.gravatar.com/avatar/example.jpg",
     *   ),
     *   @OA\Property(
     *     property="roles",
     *     type="array",
     *     description="User's assigned roless",
     *     @OA\Items(
     *       @OA\Schema(ref="#/components/schemas/Role"),
     *     ),
     *   ),
     * )
     */
    public function base(Request $request): array;

    /**
     * @OA\Schema(
     *   schema="UserView",
     *   allOf={
     *     @OA\Schema(ref="#/components/schemas/User"),
     *   },
     *   @OA\Property(
     *     property="permissions",
     *     type="array",
     *     description="User's assigned permissions",
     *     @OA\Items(
     *       type="string",
     *       example="roles.show_details",
     *     ),
     *   ),
     * )
     */
    public function view(Request $request): array;
}
