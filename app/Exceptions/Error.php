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
     * Errors details
     *
     * @var array
     */
    public array $errors;

    public function __construct(string $message = 'Internal Server Error', int $code = 500, array $errors = [])
    {
        $this->message = $message;
        $this->code = $code;
        $this->errors = $errors;
    }

    /**
     * Return http error response.
     *
     * @deprecated
     */
    public static function abort($message = 'Internal Server Error', $code = 500): JsonResponse
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
