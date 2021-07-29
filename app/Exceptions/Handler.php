<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class Handler extends ExceptionHandler
{
    protected array $errors = [
        AuthenticationException::class => [
            'message' => 'Unauthorized',
            'code' => JsonResponse::HTTP_UNAUTHORIZED,
        ],
        NotFoundHttpException::class => [
            'message' => 'Page not found',
            'code' => JsonResponse::HTTP_NOT_FOUND,
        ],
        ValidationException::class => [
            'code' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        ],
        StoreException::class => [
            'code' => JsonResponse::HTTP_BAD_REQUEST,
        ],
        AuthException::class => [
            'code' => JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        ],
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
        'token',
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception): JsonResponse | Response | SymfonyResponse
    {
        $class = $exception::class;

        if (isset($this->errors[$class])) {
            $error = new Error(
                $this->errors[$class]['message'] ?? $exception->getMessage(),
                $exception->getCode()
                    ?: ($this->errors[$class]['code'] ??
                    JsonResponse::HTTP_INTERNAL_SERVER_ERROR),
                method_exists($exception, 'errors') ? $exception->errors() : [],
            );
        } else {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($exception);
            }

            if (config('app.debug') === true) {
                return parent::render($request, $exception);
            }

            $error = new Error();
        }

        return ErrorResource::make($error)
            ->response()
            ->setStatusCode($error->code);
    }
}
