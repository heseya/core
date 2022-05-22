<?php

namespace App\Exceptions;

use App\Http\Resources\ErrorResource;
use Illuminate\Http\JsonResponse;

final class Error
{
    public function __construct(
        public string $message = 'Internal Server Error',
        public int $code = 500,
        public string $key = 'INTERNAL_SERVER_ERROR',
        public array $errors = [],
        public array $stack = []
    ) {
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

    public function setStack(array $stack): Error
    {
        $this->stack = $stack;

        return $this;
    }
}
