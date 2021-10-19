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
     *     example="PRODUCT_CREATED",
     *   ),
     *   @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Displayed event name",
     *     example="Product created",
     *   ),
     *   @OA\Property(
     *     property="description",
     *     type="string",
     *     description="Displayed event description",
     *     example="Event triggered when new products are created",
     *   ),
     *   @OA\Property(
     *     property="required_permissions",
     *     type="array",
     *     description="List of Event required permissions",
     *     @OA\Items(
     *         type="string",
     *         example="products.show",
     *     ),
     *   ),
     *   @OA\Property(
     *     property="required_hidden_permissions",
     *     type="array",
     *     description="List of Event required hidden permissions",
     *     @OA\Items(
     *         type="string",
     *         example="products.show_hidden",
     *     ),
     *   ),
     * )
     */
    public function base(Request $request): array;
}
