<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\DiscountCreateRequest;
use App\Http\Requests\DiscountIndexRequest;
use App\Http\Requests\DiscountUpdateRequest;
use App\Models\Discount;
use Illuminate\Http\Resources\Json\JsonResource;

interface DiscountControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/discounts",
     *   summary="get all discounts",
     *   tags={"Discounts"},
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="description",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="code",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Discount"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(DiscountIndexRequest $request): JsonResource;

    /**
     * @OA\Post(
     *   path="/discounts",
     *   summary="add new discount",
     *   tags={"Discounts"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Discount",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Discount",
     *       )
     *     )
     *   )
     * )
     */
    public function store(DiscountCreateRequest $request): JsonResource;

    /**
     * @OA\Patch(
     *   path="/discounts/id:{id}",
     *   summary="update discount",
     *   tags={"Discounts"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *       example="5b320ba6-d5ee-4870-bed2-1a101704c2c4",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Discount",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Updated",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Discount",
     *       )
     *     )
     *   )
     * )
     */
    public function update(Discount $discount, DiscountUpdateRequest $request): JsonResource;
}
