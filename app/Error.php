<?php

namespace App;

use App\Http\Resources\ErrorResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema()
 */
class Error
{
    /**
     * Http response code.
     *
     * @var int
     * @OA\Property(
     *   example=500,
     * )
     */
    public $code;

    /**
     * Error message.
     *
     * @var string
     * @OA\Property(
     *   example="Some error message.",
     * )
     */
    public $message;

    /**
     * Return http error response.
     *
     * @param string $message
     * @param int $code Http code.
     */
    public static function abort($message = 'Internal Server Error', $code = 500) {
        $error = new self;
        $error->message = $message;
        $error->code = $code;

        return (new ErrorResource($error))
            ->response()
            ->setStatusCode($error->code);
    }
}
