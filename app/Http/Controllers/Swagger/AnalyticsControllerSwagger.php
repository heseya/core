<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\PaymentsAnalyticsRequest;
use Illuminate\Http\Resources\Json\JsonResource;

interface AnalyticsControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/analytics/payments/total",
     *   summary="summary of all payments over a period of time (default = last year)",
     *   description="returns total amount and count of payments",
     *   tags={"Analytics"},
     *   @OA\Parameter(
     *     name="from",
     *     in="path",
     *     description="required if 'to' is set",
     *     @OA\Schema(
     *       type="date",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="to",
     *     in="path",
     *     @OA\Schema(
     *       type="date",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(
     *           property="amount",
     *           description="total money amount",
     *           type="number",
     *           example=1234.57
     *         ),
     *         @OA\Property(
     *           property="count",
     *           description="total payment count",
     *           type="integer",
     *           example=13
     *         )
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function paymentsTotal(PaymentsAnalyticsRequest $request): JsonResource;
}
