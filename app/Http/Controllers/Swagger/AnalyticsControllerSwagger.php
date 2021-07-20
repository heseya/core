<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\AnalyticsPaymentsRequest;
use Illuminate\Http\Resources\Json\JsonResource;

interface AnalyticsControllerSwagger
{
    /**
     * @OA\Get(
     *   path="/analytics/payments",
     *   summary="Summary of all payments over a period of time",
     *   description="
     *       Returns total amount and count of payments. By default period is last year.
     *       Results are grouped in specified time frames.
     *   ",
     *   tags={"Analytics"},
     *   @OA\Parameter(
     *     name="from",
     *     in="path",
     *     description="date datetime or timestamp by default in UTC+0; required if 'to' is set",
     *     example="2021-03-23",
     *     @OA\Schema(
     *       type="date",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="to",
     *     in="path",
     *     description="date datetime or timestamp by default in UTC+0",
     *     example="2021-04-23 15:37",
     *     @OA\Schema(
     *       type="date",
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="group",
     *     in="path",
     *     example="total",
     *     description="total/yearly/monthly/daily/hourly",
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
     *         type="object",
     *         @OA\Property(
     *           property="$timeframe",
     *           description="
     *              formated time frame in UTC+0 eg.:
     *              for monthly - 'YYYY-MM', for hourly - 'YYYY-MM-DD HH', for total 'total'
     *           ",
     *           type="object",
     *           @OA\Property(
     *             property="amount",
     *             description="total money amount",
     *             type="number",
     *             example=1234.57
     *           ),
     *           @OA\Property(
     *             property="count",
     *             description="total payment count",
     *             type="integer",
     *             example=13
     *           )
     *         )
     *       )
     *     )
     *   ),
     *   security={
     *     {"oauth": {}}
     *   }
     * )
     */
    public function payments(AnalyticsPaymentsRequest $request): JsonResource;
}
