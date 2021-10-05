<?php

namespace App\Http\Resources\Swagger;

use Illuminate\Http\Request;

interface OrderPublicResourceSwagger
{
    /**
     * @OA\Schema(
     *   schema="OrderSummary",
     *   @OA\Property(
     *     property="id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="code",
     *     type="string",
     *     description="Product's secondary identifier",
     *     example="G3K4DH",
     *   ),
     *   @OA\Property(
     *     property="status",
     *     ref="#/components/schemas/Status",
     *   ),
     *   @OA\Property(
     *     property="payed",
     *     type="boolean",
     *   ),
     *   @OA\Property(
     *     property="payable",
     *     type="boolean",
     *   ),
     *   @OA\Property(
     *     property="summary",
     *     type="number",
     *   ),
     *   @OA\Property(
     *     property="shipping_method_id",
     *     type="string",
     *     example="026bc5f6-8373-4aeb-972e-e78d72a67121",
     *   ),
     *   @OA\Property(
     *     property="created_at",
     *     type="datetime",
     *     example="2021-09-13T11:11",
     *   ),
     * )
     */
    public function base(Request $request): array;
}
