<?php

namespace App\Exceptions;

use App\Http\Resources\ErrorResource;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Schema()
 */
final class Error
{
    /**
     * Http response code.
     *
     * @var int
     *
     * @OA\Property(
     *   example=500,
     * )
     */
    public int $code;

    /**
     * Error message.
     *
     * @var string
     *
     * @OA\Property(
     *   example="Some error message.",
     * )
     */
    public string $message;

    /**
     * Error key.
     *
     * @var string
     *
     * @OA\Property(
     *   example="NOT_FOUND",
     * )
     */
    public string $key;

    /**
     * Errors details
     *
     * @var array
     */
    public array $errors;

    public function __construct(
        string $message = 'Internal Server Error',
        int $code = 500,
        string $key = 'INTERNAL_SERVER_ERROR',
        array $errors = []
    ) {
        $this->message = $message;
        $this->code = $code;
        $this->key = $key;
        $this->errors = $errors;
    }

    /**
     * Return http error response.
     *
     * @deprecated
     */
    public static function abort(string $message = 'Internal Server Error', int $code = 500): JsonResponse
    {
        $error = new self(
            $message,
            $code,
        );

        return ErrorResource::make($error)
            ->response()
            ->setStatusCode($error->code);
    }
}
