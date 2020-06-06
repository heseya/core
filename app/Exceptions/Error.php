<?php

namespace App\Exceptions;

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

        return ErrorResource::make($error)
            ->response()
            ->setStatusCode($error->code);
    }
}
