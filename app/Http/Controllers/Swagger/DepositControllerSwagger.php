<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\DepositCreateRequest;
use App\Models\Item;
use Illuminate\Http\Resources\Json\JsonResource;

interface DepositControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/deposits",
     *   summary="list deposits",
     *   tags={"Deposits"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Deposit"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index(): JsonResource;

    /**
     * @OA\Get(
     *   path="/items/id:{id}/deposits",
     *   summary="list item deposits",
     *   tags={"Deposits"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Deposit"
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function show(Item $item): JsonResource;

    /**
     * @OA\Post(
     *   path="/items/id:{id}/deposits",
     *   summary="add new deposit",
     *   tags={"Deposits"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Deposit",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Created",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Deposit",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function store(Item $item, DepositCreateRequest $request): JsonResource;
}
