<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use App\Http\Resources\StatusResource;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusController extends Controller
{
    /**
     * @OA\Get(
     *   path="/statuses",
     *   summary="list statuses",
     *   tags={"Statuses"},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Status"),
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function index()
    {
        $query = Status::select();

        return StatusResource::collection($query->get());
    }

    /**
     * @OA\Post(
     *   path="/statuses",
     *   summary="add new status",
     *   tags={"Statuses"},
     *   @OA\RequestBody(
     *     @OA\JsonContent(
     *       ref="#/components/schemas/Status",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Status",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function create(Request $request): JsonResource
    {
        $request->validate([
            'name' => 'required|string|max:60',
            'color' => 'required|string|size:6',
            'description' => 'string|max:255|nullable',
        ]);

        $status = Status::create($request->all());

        return StatusResource::make($status);
    }

    /**
     * @OA\Patch(
     *   path="/statuses/id:{id}",
     *   summary="update status",
     *   tags={"Statuses"},
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
     *       ref="#/components/schemas/Status",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Status",
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function update(Status $status, Request $request): JsonResource
    {
        $request->validate([
            'name' => 'string|max:60',
            'color' => 'string|size:6',
            'description' => 'string|max:255|nullable',
        ]);

        $status->update($request->all());

        return StatusResource::make($status);
    }

    /**
     * @OA\Delete(
     *   path="/statuses/id:{id}",
     *   summary="delete status",
     *   tags={"Statuses"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(
     *       type="integer",
     *     )
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Success",
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function delete(Status $status)
    {
        $status->delete();

        return response()->json(null, 204);
    }
}
