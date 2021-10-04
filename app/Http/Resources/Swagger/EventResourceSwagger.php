<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface EventResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="Event",
     *   @OA\Property(
     *     property="key",
     *     type="string",
     *     description="Displayed event key",
     *     example="ORDER_CREATED",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Displayed event name",
     *     example="Order created",
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     description="Displayed event description",
     *     example="Event triggered when new orders are created",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
