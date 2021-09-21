<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface AppResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="App",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="url",
     *     type="string",
     *     description="Root url of the application",
     *     example="https://app.heseya.com",
     *   ),
     *   @OA\Property(
     *     property="microfrontend_url",
     *     type="string",
     *     description="Url of the applications microfrontend configuration page",
     *     example="https://microfront.app.heseya.com",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Name of the app",
     *     example="Super App",
     *   ),
     *   @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="Unique slugified name",
     *     example="super_app",
     *   ),
     *   @OA\Property(
     *     property="version",
     *     type="string",
     *     description="App version",
     *     example="^1.0.0",
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     description="Description of the app",
     *     example="App responsible for creating products page layout",
     *   ),
     *   @OA\Property(
     *     property="icon",
     *     type="string",
     *     description="App icon url",
     *     example="https://picsum.photos/200",
     *   ),
     *   @OA\Property(
     *     property="author",
     *     type="string",
     *     description="Name of an author or team",
     *     example="Adam Nowak",
     *   ),
     * )
     */
    public function base(Request $request): array;

    /**
     * @OA\Schema(
     *   schema="AppView",
     *   allOf={
     *     @OA\Schema(ref="#/components/schemas/App"),
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
