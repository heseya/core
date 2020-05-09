<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\DepositResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DepositController extends Controller
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
    public function index(): ResourceCollection
    {
        return DepositResource::collection(
            Deposit::paginate(12),
        );
    }

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
    public function view(Item $item): ResourceCollection
    {
        return DepositResource::collection(
            $item->deposits()->paginate(12),
        );
    }

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
     *     description="Success",
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
    public function create(Item $item, Request $request): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|numeric',
        ]);

        $deposit = $item->deposits()->create($request->all());

        return (new DepositResource($deposit))
            ->response()
            ->setStatusCode(201);
    }
}
