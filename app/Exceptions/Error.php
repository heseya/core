<?php

namespace App\Exceptions;

/**
 * @OA\Schema()
 */
final class Error
{
    /**
     * Http response code.
     *
     * @var int
     * @OA\Property(
     *   example=500,
     * )
     */
    public int $code;

    /**
     * Error message.
     *
     * @var string
     * @OA\Property(
     *   example="Some error message.",
     * )
     */
    public string $message;

    public function __construct(string $message = 'Internal Server Error', int $code = 500)
    {
        $this->message = $message;
        $this->code = $code;
    }

    /**
     * Return http error response.
     *
     * @param string $message
     * @param int $code Http code.
     *
     * @deprecated
     */
    public static function abort($message = 'Internal Server Error', $code = 500) {
        $error = new self(
            $message,
            $code,
        );

        return ErrorResource::make($error)
            ->response()
            ->setStatusCode($error->code);
    }
}
